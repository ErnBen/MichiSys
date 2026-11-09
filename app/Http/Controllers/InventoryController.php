<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\InventoryLot;
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
        $products = Product::orderBy('name')->paginate(10);
        $movements = InventoryMovement::with('product', 'user')
            ->orderByDesc('created_at')
            ->paginate(10);
        $lots = InventoryLot::with('product')
            ->where('available_quantity', '>', 0)
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->paginate(10, ['*'], 'lots_page');

        return view('inventory.index', compact('products', 'movements', 'lots'));
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
