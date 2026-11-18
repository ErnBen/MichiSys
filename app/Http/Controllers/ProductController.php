<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Provider;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('business_id', $request->user()->business_id)],
            'provider_id' => ['nullable', Rule::exists('providers', 'id')->where('business_id', $request->user()->business_id)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'stock' => ['nullable', Rule::in(['low', 'available', 'out'])],
            'expires' => ['nullable', Rule::in(['soon', 'expired'])],
        ]);
        $search = trim($filters['search'] ?? '');

        $products = Product::with('category')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ))
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['provider_id']), fn ($query) => $query->whereHas('providers', fn ($query) =>
                $query->where('providers.id', $filters['provider_id'])
            ))
            ->when(isset($filters['status']), fn ($query) => $query->where('active', $filters['status'] === 'active'))
            ->when(($filters['stock'] ?? null) === 'low', fn ($query) => $query->whereColumn('stock', '<=', 'stock_minimo'))
            ->when(($filters['stock'] ?? null) === 'available', fn ($query) => $query->where('stock', '>', 0))
            ->when(($filters['stock'] ?? null) === 'out', fn ($query) => $query->where('stock', '<=', 0))
            ->when(($filters['expires'] ?? null) === 'soon', fn ($query) => $query->whereHas('lots', fn ($query) =>
                $query->where('available_quantity', '>', 0)
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [today(), today()->addDays(config('michisys.expiration_warning_days', 30))])
            ))
            ->when(($filters['expires'] ?? null) === 'expired', fn ($query) => $query->whereHas('lots', fn ($query) =>
                $query->where('available_quantity', '>', 0)
                    ->whereNotNull('expires_at')
                    ->whereDate('expires_at', '<', today())
            ))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $providers = Provider::orderBy('name')->get(['id', 'name']);

        return view('products.index', compact('products', 'search', 'filters', 'categories', 'providers'));
    }

    public function search(Request $request)
    {
        $filters = $request->validate([
            'search' => 'required|string|min:2|max:100',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('business_id', $request->user()->business_id)],
            'provider_id' => ['nullable', Rule::exists('providers', 'id')->where('business_id', $request->user()->business_id)],
            'kind' => ['nullable', Rule::in(['product', 'ingredient'])],
            'active' => 'nullable|boolean',
            'without_recipe' => 'nullable|boolean',
            'except_recipe_id' => ['nullable', Rule::exists('recipes', 'id')->where('business_id', $request->user()->business_id)],
        ]);
        $search = trim($filters['search']);
        $products = Product::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"))
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['provider_id']), fn ($query) => $query->whereHas('providers', fn ($query) =>
                $query->where('providers.id', $filters['provider_id'])
            ))
            ->when(isset($filters['kind']), fn ($query) => $query->where('kind', $filters['kind']))
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('active', $filters['active']))
            ->when(($filters['without_recipe'] ?? false), fn ($query) => $query->whereDoesntHave('recipe', fn ($query) =>
                $query->when(isset($filters['except_recipe_id']), fn ($query) => $query->where('id', '!=', $filters['except_recipe_id']))
            ))
            ->with('category:id,name')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'category_id', 'name', 'sku', 'stock', 'price', 'unit', 'kind'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $product->stock,
                'price' => $product->price,
                'unit_label' => $product->unit_label,
                'kind' => $product->kind,
                'category' => $product->category?->name,
            ]);

        return response()->json(['data' => $products]);
    }

    public function priceLookup(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);
        $search = trim($filters['search'] ?? '');

        $products = Product::query()
            ->select(['id', 'category_id', 'name', 'sku', 'price', 'image'])
            ->with('category:id,name')
            ->where('active', true)
            ->where('kind', 'product')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('products.price-lookup', compact('products', 'search'));
    }

    public function create()
    {
        $categories = Category::where('active', true)->orderBy('name')->get();

        return view('products.create', compact('categories'))->with('product', null);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('business_id', $request->user()->business_id)
                    ->where('active', true),
            ],
            'kind' => 'required|in:product,ingredient',
            'unit' => 'required|in:piece,g,ml',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
        ]);

        $data['cost'] = $request->input('cost', 0);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['active'] = $request->has('active');

        $imagePath = $data['image'] ?? null;

        try {
            DB::transaction(fn () => Product::create($data), 3);
        } catch (Throwable $exception) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return Redirect::route('products.index')->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::where(fn ($query) => $query
            ->where('active', true)
            ->orWhere('id', $product->category_id))
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product, InventoryService $inventory)
    {
        $data = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('business_id', $request->user()->business_id)
                    ->where(fn ($query) => $query->where('active', true)->orWhere('id', $product->category_id)),
            ],
            'kind' => 'required|in:product,ingredient',
            'unit' => 'required|in:piece,g,ml',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'image' => 'nullable|image|max:2048',
            'active' => 'sometimes|boolean',
        ]);

        if ($product->kind !== $data['kind'] || $product->unit !== $data['unit']) {
            $hasHistory = DB::table('inventory_lots')->where('product_id', $product->id)->exists()
                || DB::table('sale_product')->where('product_id', $product->id)->exists()
                || DB::table('combo_product')->where('product_id', $product->id)->exists()
                || DB::table('purchase_items')->where('product_id', $product->id)->exists()
                || DB::table('recipe_items')->where('ingredient_id', $product->id)->exists()
                || DB::table('recipes')->where('product_id', $product->id)->exists();

            if ($hasHistory) {
                throw ValidationException::withMessages([
                    'unit' => 'No se puede cambiar el tipo o la unidad de un producto con historial de inventario o recetas.',
                ]);
            }
        }

        $previousImage = $product->image;
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['active'] = $request->has('active');
        $data['cost'] = $request->input('cost', 0);
        $data['stock_minimo'] = (int) $request->input('stock_minimo', 0);
        $data['fecha_vencimiento'] = $request->filled('fecha_vencimiento') ? $request->input('fecha_vencimiento') : null;
        $targetStock = (int) $data['stock'];
        unset($data['stock']);

        DB::transaction(function () use ($product, $data, $targetStock, $request, $inventory): void {
            $currentProduct = Product::whereKey($product->id)->firstOrFail();
            $stockDifference = $targetStock - $currentProduct->stock;

            if ($stockDifference > 0) {
                $inventory->receive(
                    $currentProduct,
                    $stockDifference,
                    'Ajuste de stock desde producto',
                    $request->user()->id,
                    'adjustment',
                    expiresAt: $data['fecha_vencimiento'],
                    unitCost: (float) $data['cost']
                );
            } elseif ($stockDifference < 0) {
                $inventory->consume(
                    $currentProduct,
                    abs($stockDifference),
                    'Ajuste de stock desde producto',
                    $request->user()->id,
                    allowExpired: true
                );
            }

            $currentProduct->update($data);
        }, 3);

        if ($request->hasFile('image') && $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return Redirect::route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        // Evitar eliminación si el producto está vinculado a ventas para mantener integridad histórica.
        $hasInventoryHistory = collect([
            'sale_product',
            'purchase_items',
            'inventory_lots',
            'inventory_movements',
            'combo_product',
        ])->contains(fn ($table) => DB::table($table)->where('product_id', $product->id)->exists());

        if ($hasInventoryHistory) {
            // En lugar de borrar, desactivar el producto para conservar historial de ventas.
            $product->update(['active' => false]);

            return Redirect::route('products.index')->with('warning', 'El producto tiene historial de ventas o inventario y no puede eliminarse; se ha desactivado en su lugar.');
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return Redirect::route('products.index')->with('success', 'Producto eliminado correctamente.');
    }

    public function toggle(Product $product)
    {
        $product->update(['active' => ! $product->active]);

        return Redirect::route('products.index')->with('success', 'Estado del producto actualizado correctamente.');
    }
}
