<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Combo;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCombo;
use App\Models\SaleProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $sales = Sale::with('client', 'user')
            ->when($search, fn ($query) =>
                $query->whereHas('client', fn ($query) =>
                    $query->where('name', 'like', "%{$search}%")
                )->orWhere('id', $search)
            )
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('sales.index', compact('sales', 'search'));
    }

    public function create()
    {
        $products = Product::where('active', true)->orderBy('name')->get();
        $combos = Combo::where('active', true)->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('sales.create', compact('products', 'combos', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'discount' => 'nullable|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_quantities' => 'nullable|array',
            'product_quantities.*' => 'integer|min:1',
            'combo_ids' => 'nullable|array',
            'combo_ids.*' => 'exists:combos,id',
            'combo_quantities' => 'nullable|array',
            'combo_quantities.*' => 'integer|min:1',
        ]);

        $discount = $request->input('discount', 0);
        $productIds = $request->input('product_ids', []);
        $productQuantities = $request->input('product_quantities', []);
        $comboIds = $request->input('combo_ids', []);
        $comboQuantities = $request->input('combo_quantities', []);

        $subtotal = 0;
        $productItems = [];
        $comboItems = [];
        $stockAdjustments = [];

        foreach ($productIds as $productId) {
            $quantity = intval($productQuantities[$productId] ?? 1);
            if ($quantity < 1) {
                continue;
            }
            $product = Product::find($productId);
            if (! $product) {
                continue;
            }
            if ($product->stock < $quantity) {
                return Redirect::back()->withInput()->with('error', "Stock insuficiente para el producto {$product->name}.");
            }

            $lineSubtotal = $product->price * $quantity;
            $subtotal += $lineSubtotal;
            $productItems[] = compact('product', 'quantity', 'lineSubtotal');
            $stockAdjustments[$product->id] = ($stockAdjustments[$product->id] ?? 0) + $quantity;
        }

        foreach ($comboIds as $comboId) {
            $quantity = intval($comboQuantities[$comboId] ?? 1);
            if ($quantity < 1) {
                continue;
            }
            $combo = Combo::with('products')->find($comboId);
            if (! $combo) {
                continue;
            }

            foreach ($combo->products as $comboProduct) {
                $required = $comboProduct->pivot->quantity * $quantity;
                $stockAdjustments[$comboProduct->id] = ($stockAdjustments[$comboProduct->id] ?? 0) + $required;
            }

            $lineSubtotal = $combo->price * $quantity;
            $subtotal += $lineSubtotal;
            $comboItems[] = compact('combo', 'quantity', 'lineSubtotal');
        }

        foreach ($stockAdjustments as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product && $product->stock < $quantity) {
                return Redirect::back()->withInput()->with('error', "Stock insuficiente para el producto {$product->name} en el combo.");
            }
        }

        if (empty($productItems) && empty($comboItems)) {
            return Redirect::back()->withInput()->with('error', 'Debe agregar al menos un producto o combo a la venta.');
        }

        $tax = round(max($subtotal - $discount, 0) * 0.19, 2);
        $total = round(max($subtotal - $discount, 0) + $tax, 2);

        DB::transaction(function () use ($data, $productItems, $comboItems, $stockAdjustments, $discount, $tax, $total, $subtotal) {
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'client_id' => $data['client_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($productItems as $item) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['product']->price,
                    'subtotal' => $item['lineSubtotal'],
                ]);

                $product = $item['product'];
                $product->decrement('stock', $item['quantity']);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'type' => 'out',
                    'note' => "Venta #{$sale->id}",
                    'user_id' => auth()->id(),
                ]);
            }

            foreach ($comboItems as $item) {
                $combo = $item['combo'];
                SaleCombo::create([
                    'sale_id' => $sale->id,
                    'combo_id' => $combo->id,
                    'quantity' => $item['quantity'],
                    'price' => $combo->price,
                    'subtotal' => $item['lineSubtotal'],
                ]);

                foreach ($combo->products as $comboProduct) {
                    $required = $comboProduct->pivot->quantity * $item['quantity'];
                    $product = $comboProduct;
                    $product->decrement('stock', $required);

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'quantity' => $required,
                        'type' => 'out',
                        'note' => "Venta de combo #{$sale->id}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        });

        return Redirect::route('sales.index')->with('success', 'Venta registrada correctamente.');
    }

    public function show(Sale $sale)
    {
        $sale->load('products.product', 'combos.combo', 'client', 'user');

        return view('sales.show', compact('sale'));
    }

    public function destroy(Sale $sale)
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->products as $item) {
                $product = $item->product;
                if ($product) {
                    $product->increment('stock', $item->quantity);
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'type' => 'in',
                        'note' => "Anulación de venta #{$sale->id}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            foreach ($sale->combos as $item) {
                if ($item->combo) {
                    foreach ($item->combo->products as $comboProduct) {
                        $quantity = $comboProduct->pivot->quantity * $item->quantity;
                        $product = $comboProduct;
                        $product->increment('stock', $quantity);

                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'type' => 'in',
                            'note' => "Anulación de venta combo #{$sale->id}",
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            $sale->delete();
        });

        return Redirect::route('sales.index')->with('success', 'Venta eliminada correctamente.');
    }
}
