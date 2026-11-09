<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Combo;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCombo;
use App\Models\SaleProduct;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $sales = Sale::with('client', 'user')
            ->when($request->user()->role === 'cajero', fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($search, fn ($query) => $query->where(fn ($query) =>
                $query->whereHas('client', fn ($query) =>
                    $query->where('name', 'like', "%{$search}%")
                )->orWhere('id', $search)
            ))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('sales.index', compact('sales', 'search'));
    }

    public function create()
    {
        $products = Product::where('active', true)
            ->where('kind', 'product')
            ->withSum(['lots as sellable_stock' => function (Builder $query): void {
                $query->where('available_quantity', '>', 0)
                    ->where(function (Builder $query): void {
                        $query->whereNull('expires_at')
                            ->orWhereDate('expires_at', '>=', now()->toDateString());
                    });
            }], 'available_quantity')
            ->orderBy('name')
            ->get();
        $combos = Combo::where('active', true)->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('sales.create', compact('products', 'combos', 'clients'));
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', $request->user()->business_id)],
            'discount' => 'nullable|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => ['distinct', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)->where('kind', 'product')],
            'product_quantities' => 'nullable|array',
            'product_quantities.*' => 'integer|min:1',
            'combo_ids' => 'nullable|array',
            'combo_ids.*' => ['distinct', Rule::exists('combos', 'id')->where('business_id', $request->user()->business_id)],
            'combo_quantities' => 'nullable|array',
            'combo_quantities.*' => 'integer|min:1',
        ]);

        DB::transaction(function () use ($data, $request, $inventory): void {
            $businessId = $request->user()->business_id;
            $productIds = $data['product_ids'] ?? [];
            $comboIds = $data['combo_ids'] ?? [];
            $products = Product::where('active', true)
                ->where('kind', 'product')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');
            $combos = Combo::with('products')
                ->where('active', true)
                ->whereIn('id', $comboIds)
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds) || $combos->count() !== count($comboIds)) {
                throw ValidationException::withMessages([
                    'products' => 'Uno o más productos o combos ya no están disponibles en este negocio.',
                ]);
            }

            $subtotal = 0;
            $productItems = [];
            $comboItems = [];
            $stockAdjustments = [];

            foreach ($productIds as $productId) {
                $product = $products->get($productId);
                $quantity = (int) ($data['product_quantities'][$productId] ?? 1);
                $lineSubtotal = $product->price * $quantity;
                $subtotal += $lineSubtotal;
                $productItems[] = compact('product', 'quantity', 'lineSubtotal');
                $stockAdjustments[$product->id] = ($stockAdjustments[$product->id] ?? 0) + $quantity;
            }

            foreach ($comboIds as $comboId) {
                $combo = $combos->get($comboId);
                $quantity = (int) ($data['combo_quantities'][$comboId] ?? 1);

                foreach ($combo->products as $comboProduct) {
                    if ($comboProduct->business_id !== $businessId || $comboProduct->kind !== 'product') {
                        throw ValidationException::withMessages([
                            'products' => 'El combo contiene un producto de otro negocio.',
                        ]);
                    }

                    $required = $comboProduct->pivot->quantity * $quantity;
                    $stockAdjustments[$comboProduct->id] = ($stockAdjustments[$comboProduct->id] ?? 0) + $required;
                }

                $lineSubtotal = $combo->price * $quantity;
                $subtotal += $lineSubtotal;
                $comboItems[] = compact('combo', 'quantity', 'lineSubtotal');
            }

            if (empty($productItems) && empty($comboItems)) {
                throw ValidationException::withMessages([
                    'products' => 'Debe agregar al menos un producto o combo a la venta.',
                ]);
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax = round(max($subtotal - $discount, 0) * 0.19, 2);
            $total = round(max($subtotal - $discount, 0) + $tax, 2);

            $sale = Sale::create([
                'user_id' => auth()->id(),
                'client_id' => $data['client_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($stockAdjustments as $productId => $quantity) {
                $product = $products->get($productId)
                    ?? Product::where('business_id', $businessId)->find($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'products' => 'Uno de los productos del combo ya no está disponible en este negocio.',
                    ]);
                }

                $inventory->consume(
                    $product,
                    $quantity,
                    "Venta #{$sale->id}",
                    $request->user()->id,
                    $sale->id
                );
            }

            foreach ($productItems as $item) {
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['product']->price,
                    'subtotal' => $item['lineSubtotal'],
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

            }
        }, 3);

        return Redirect::route('sales.index')->with('success', 'Venta registrada correctamente.');
    }

    public function show(Sale $sale)
    {
        $this->ensureSaleAccess($sale);
        $sale->load('products.product', 'combos.combo', 'client', 'user');

        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $this->ensureSaleAccess($sale);
        $sale->load('products.product', 'combos.combo', 'client', 'user');

        return view('sales.receipt', compact('sale'));
    }

    public function receiptPdf(Sale $sale)
    {
        $this->ensureSaleAccess($sale);
        $sale->load('products.product', 'combos.combo', 'client', 'user');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.receipt', compact('sale'));

        return $pdf->download("comprobante_venta_{$sale->id}.pdf");
    }

    public function destroy(Sale $sale, InventoryService $inventory)
    {
        DB::transaction(function () use ($sale, $inventory): void {
            $movements = InventoryMovement::where('sale_id', $sale->id)
                ->where('type', 'out')
                ->get();

            if ($movements->isNotEmpty()) {
                foreach ($movements as $movement) {
                    $inventory->restoreLotMovement(
                        $movement,
                        auth()->id(),
                        "Anulación de venta #{$sale->id}"
                    );
                }
            } else {
                foreach ($sale->products as $item) {
                    if ($item->product) {
                        $inventory->receive(
                            $item->product,
                            $item->quantity,
                            "Anulación de venta #{$sale->id}",
                            auth()->id()
                        );
                    }
                }

                foreach ($sale->combos as $item) {
                    foreach ($item->combo?->products ?? [] as $comboProduct) {
                        $inventory->receive(
                            $comboProduct,
                            $comboProduct->pivot->quantity * $item->quantity,
                            "Anulación de venta #{$sale->id}",
                            auth()->id()
                        );
                    }
                }
            }

            $sale->delete();
        }, 3);

        return Redirect::route('sales.index')->with('success', 'Venta eliminada correctamente.');
    }

    private function ensureSaleAccess(Sale $sale): void
    {
        abort_unless(
            auth()->user()->role === 'admin' || (int) $sale->user_id === (int) auth()->id(),
            404
        );
    }
}
