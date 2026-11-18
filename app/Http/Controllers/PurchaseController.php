<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('provider', 'user')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $providers = Provider::orderBy('name')->get();
        $categories = Category::where('active', true)->orderBy('name')->get();
        $oldItems = old('items', []);
        $selectedProductIds = collect($oldItems)->pluck('product_id')->filter()->unique();
        $selectedProducts = Product::whereIn('id', $selectedProductIds)->get()->keyBy('id');
        $hasProducts = Product::where('active', true)->exists();

        return view('purchases.create', compact(
            'providers',
            'categories',
            'oldItems',
            'selectedProducts',
            'hasProducts'
        ));
    }

    public function productSearch(Request $request)
    {
        $businessId = $request->user()->business_id;
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('business_id', $businessId)],
            'provider_id' => ['nullable', Rule::exists('providers', 'id')->where('business_id', $businessId)],
        ]);
        $search = trim($filters['search'] ?? '');

        if ($search === '' && empty($filters['category_id']) && empty($filters['provider_id'])) {
            return response()->json(['data' => []]);
        }

        $products = Product::query()
            ->where('active', true)
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['provider_id']), fn ($query) => $query->whereHas('providers', fn ($query) =>
                $query->where('providers.id', $filters['provider_id'])
                    ->where('providers.business_id', $businessId)
            ))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->with('category:id,name')
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'category_id', 'name', 'sku', 'unit', 'stock', 'cost'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'unit_label' => $product->unit_label,
                'stock' => $product->stock,
                'cost' => $product->cost,
                'category' => $product->category?->name,
            ]);

        return response()->json(['data' => $products]);
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $businessId = $request->user()->business_id;
        $data = $request->validate([
            'provider_id' => ['required', Rule::exists('providers', 'id')->where('business_id', $businessId)],
            'purchase_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('business_id', $businessId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.001|decimal:0,3',
            'items.*.purchase_unit' => 'required|in:piece,g,kg,ml,l',
            'items.*.unit_cost' => 'required|numeric|min:0|decimal:0,2',
            'items.*.lot_number' => 'nullable|string|max:100',
            'items.*.expires_at' => 'nullable|date',
        ]);

        $purchase = DB::transaction(function () use ($data, $request, $businessId, $inventory): Purchase {
            $products = Product::where('business_id', $businessId)
                ->whereIn('id', collect($data['items'])->pluck('product_id'))
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'Uno o más productos ya no pertenecen a este negocio.',
                ]);
            }

            $provider = Provider::whereKey($data['provider_id'])
                ->where('business_id', $businessId)
                ->firstOrFail();
            $lines = [];
            $total = 0;

            foreach ($data['items'] as $index => $item) {
                $product = $products->get($item['product_id']);
                $purchaseUnit = $item['purchase_unit'];
                $factor = $product->purchaseUnitFactors()[$purchaseUnit] ?? null;

                if (! $factor) {
                    throw ValidationException::withMessages([
                        "items.{$index}.purchase_unit" => "La unidad {$purchaseUnit} no es compatible con {$product->unit_label}.",
                    ]);
                }

                $purchasedQuantity = (float) $item['quantity'];
                $baseQuantity = $purchasedQuantity * $factor;

                if (abs($baseQuantity - round($baseQuantity)) > 0.000001 || $baseQuantity < 1) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'La cantidad debe convertirse a una unidad base completa.',
                    ]);
                }

                $unitCost = round((float) $item['unit_cost'], 2);
                $baseUnitCost = round($unitCost / $factor, 6);
                $subtotal = round($unitCost * $purchasedQuantity, 2);
                $total += $subtotal;
                $lines[] = compact('product', 'item', 'unitCost', 'baseUnitCost', 'baseQuantity', 'purchasedQuantity', 'subtotal');
            }

            $purchase = Purchase::create([
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'user_id' => $request->user()->id,
                'purchase_date' => $data['purchase_date'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'total' => $total,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $quantity = (int) $line['baseQuantity'];
                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'base_unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_cost' => $line['unitCost'],
                    'purchased_quantity' => $line['purchasedQuantity'],
                    'purchase_unit' => $line['item']['purchase_unit'],
                    'base_unit_cost' => $line['baseUnitCost'],
                    'subtotal' => $line['subtotal'],
                    'lot_number' => $line['item']['lot_number'] ?? null,
                    'expires_at' => $line['item']['expires_at'] ?? null,
                ]);

                $inventory->receive(
                    $product,
                    $quantity,
                    "Compra #{$purchase->id}",
                    $request->user()->id,
                    'purchase',
                    $line['item']['lot_number'] ?? null,
                    $line['item']['expires_at'] ?? null,
                    $line['baseUnitCost'],
                    $purchase->id,
                    $purchaseItem->id
                );
            }

            return $purchase;
        }, 3);

        return Redirect::route('purchases.show', $purchase)
            ->with('success', 'Compra registrada y stock actualizado correctamente.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('provider', 'user', 'items.product');

        return view('purchases.show', compact('purchase'));
    }
}
