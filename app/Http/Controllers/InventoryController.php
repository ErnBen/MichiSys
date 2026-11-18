<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryLot;
use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('business_id', $request->user()->business_id)],
            'stock' => ['nullable', Rule::in(['low', 'available', 'out'])],
            'expires' => ['nullable', Rule::in(['soon', 'expired'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $matchesProduct = fn ($query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('sku', 'like', "%{$search}%");
        $products = Product::with('category')
            ->when($search !== '', fn ($query) => $query->where($matchesProduct))
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(($filters['stock'] ?? null) === 'low', fn ($query) => $query->whereColumn('stock', '<=', 'stock_minimo'))
            ->when(($filters['stock'] ?? null) === 'available', fn ($query) => $query->where('stock', '>', 0))
            ->when(($filters['stock'] ?? null) === 'out', fn ($query) => $query->where('stock', '<=', 0))
            ->when(($filters['expires'] ?? null) === 'soon', fn ($query) => $query->whereHas('lots', fn ($query) => $query
                ->where('available_quantity', '>', 0)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [today(), today()->addDays(config('michisys.expiration_warning_days', 30))])))
            ->when(($filters['expires'] ?? null) === 'expired', fn ($query) => $query->whereHas('lots', fn ($query) => $query
                ->where('available_quantity', '>', 0)
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<', today())))
            ->orderBy('name')
            ->paginate(10, ['*'], 'products_page')
            ->withQueryString();
        $movements = InventoryMovement::with('product', 'user')
            ->when($search !== '', fn ($query) => $query->whereHas('product', fn ($query) => $query->where($matchesProduct)))
            ->when(isset($filters['category_id']), fn ($query) => $query->whereHas('product', fn ($query) => $query->where('category_id', $filters['category_id'])))
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'movements_page')
            ->withQueryString();
        $lots = InventoryLot::with('product')
            ->where('available_quantity', '>', 0)
            ->when($search !== '', fn ($query) => $query->whereHas('product', fn ($query) => $query->where($matchesProduct)))
            ->when(isset($filters['category_id']), fn ($query) => $query->whereHas('product', fn ($query) => $query->where('category_id', $filters['category_id'])))
            ->when(($filters['expires'] ?? null) === 'soon', fn ($query) => $query->whereBetween('expires_at', [today(), today()->addDays(config('michisys.expiration_warning_days', 30))]))
            ->when(($filters['expires'] ?? null) === 'expired', fn ($query) => $query->whereDate('expires_at', '<', today()))
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->paginate(10, ['*'], 'lots_page')
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('inventory.index', compact('products', 'movements', 'lots', 'search', 'categories', 'filters'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)],
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:in,out,waste',
            'note' => 'nullable|string|max:1000',
            'lot_number' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date',
        ]);

        DB::transaction(function () use ($data, $request, $inventory): void {
            $product = Product::whereKey($data['product_id'])
                ->where('business_id', $request->user()->business_id);
            $product = $product->firstOrFail();

            if ($data['type'] === 'in') {
                $inventory->receive(
                    $product,
                    $data['quantity'],
                    $data['note'] ?? 'Entrada de inventario',
                    $request->user()->id,
                    'adjustment',
                    $data['lot_number'] ?? null,
                    $data['expires_at'] ?? null
                );

                return;
            }

            $inventory->consume(
                $product,
                $data['quantity'],
                $data['note'] ?? ($data['type'] === 'waste' ? 'Merma de inventario' : 'Salida de inventario'),
                $request->user()->id,
                allowExpired: true,
                movementType: $data['type']
            );
        }, 3);

        return Redirect::route('inventory.index')->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
