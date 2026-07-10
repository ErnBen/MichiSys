<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('name')->paginate(10);
        $movements = InventoryMovement::with('product', 'user')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('inventory.index', compact('products', 'movements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'type' => 'required|in:in,out',
            'note' => 'nullable|string|max:1000',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $quantity = $data['quantity'];

        if ($data['type'] === 'out' && $product->stock < $quantity) {
            return Redirect::back()->with('error', 'No hay stock suficiente para registrar la salida.');
        }

        $product->stock += $data['type'] === 'in' ? $quantity : -$quantity;
        $product->save();

        InventoryMovement::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return Redirect::route('inventory.index')->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
