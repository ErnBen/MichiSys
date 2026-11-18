<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Category;
use App\Models\Combo;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\SaleCombo;
use App\Models\SaleProduct;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $sales = Sale::with('client', 'user')
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

    public function create(Request $request)
    {
        $selectedProductIds = collect(old('product_ids', []))->filter()->unique()->values();
        $products = Product::whereIn('id', $selectedProductIds)
            ->where('active', true)
            ->where('kind', 'product')
            ->withSum(['lots as sellable_stock' => function (Builder $query): void {
                $query->where('available_quantity', '>', 0)
                    ->where(function (Builder $query): void {
                        $query->whereNull('expires_at')
                            ->orWhereDate('expires_at', '>=', now()->toDateString());
                    });
            }], 'available_quantity')
            ->get();
        $combos = Combo::where('active', true)
            ->with(['products' => fn ($query) => $query->withSum(['lots as sellable_stock' => fn (Builder $query) => $query
                ->where('available_quantity', '>', 0)
                ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))], 'available_quantity')])
            ->orderBy('name')
            ->get();
        $selectedClient = old('client_id')
            ? Client::whereKey(old('client_id'))->first()
            : null;
        $categories = Category::where('active', true)->orderBy('name')->get(['id', 'name']);

        $idempotencyKey = old('idempotency_key', (string) Str::uuid());

        return view('sales.create', compact('products', 'combos', 'selectedClient', 'categories', 'idempotencyKey'));
    }

    public function productSearch(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('business_id', $request->user()->business_id)],
        ]);
        $search = trim($filters['search'] ?? '');

        if ($search === '' && empty($filters['category_id'])) {
            return response()->json(['data' => []]);
        }

        $products = Product::query()
            ->where('active', true)
            ->where('kind', 'product')
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) =>
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->whereHas('lots', fn (Builder $query) => $query
                ->where('available_quantity', '>', 0)
                ->where(fn (Builder $query) => $query
                    ->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', today())
                ))
            ->withSum(['lots as sellable_stock' => fn (Builder $query) => $query
                ->where('available_quantity', '>', 0)
                ->where(fn (Builder $query) => $query
                    ->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', today())
                )], 'available_quantity')
            ->with('category:id,name')
            ->orderBy('name')
            ->limit(24)
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'unit'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'category' => $product->category?->name,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'unit_label' => $product->unit_label,
                'image_url' => $product->image ? asset('storage/'.$product->image) : null,
                'sellable_stock' => min((int) $product->stock, (int) $product->sellable_stock),
            ]);

        return response()->json(['data' => $products]);
    }

    public function clientSearch(Request $request)
    {
        $filters = $request->validate(['search' => 'required|string|min:2|max:100']);
        $search = trim($filters['search']);
        $clients = Client::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('document', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'document', 'phone'])
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'document' => $client->document,
                'phone' => $client->phone,
            ]);

        return response()->json(['data' => $clients]);
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'client_id' => ['nullable', Rule::exists('clients', 'id')->where('business_id', $request->user()->business_id)],
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer', 'qr', 'debit_card', 'credit_card', 'other', 'credit'])],
            'payment_reference' => 'nullable|string|max:120',
            'payment_entity' => 'nullable|string|max:120',
            'payment_paid_at' => 'nullable|date',
            'payment_notes' => 'nullable|string|max:2000',
            'cash_received' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'receivable_observations' => 'nullable|string|max:2000',
            'idempotency_key' => 'nullable|uuid',
            'product_ids' => 'nullable|array',
            'product_ids.*' => ['distinct', Rule::exists('products', 'id')->where('business_id', $request->user()->business_id)->where('kind', 'product')],
            'product_quantities' => 'nullable|array',
            'product_quantities.*' => 'integer|min:1',
            'combo_ids' => 'nullable|array',
            'combo_ids.*' => ['distinct', Rule::exists('combos', 'id')->where('business_id', $request->user()->business_id)],
            'combo_quantities' => 'nullable|array',
            'combo_quantities.*' => 'integer|min:1',
        ]);
        $paymentMethodProvided = array_key_exists('payment_method', $data);
        $data['payment_method'] ??= 'cash';

        if ($data['payment_method'] === 'credit' && empty($data['client_id'])) {
            throw ValidationException::withMessages([
                'client_id' => 'Seleccione un cliente registrado para crear una venta a crédito.',
            ]);
        }

        $idempotencyKey = $data['idempotency_key'] ?? (string) Str::uuid();
        $existingSale = Sale::where('business_id', $request->user()->business_id)
            ->where('user_id', $request->user()->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingSale) {
            return Redirect::route('sales.index')->with('success', 'Esta venta ya había sido registrada.');
        }

        try {
            DB::transaction(function () use ($data, $request, $inventory, $idempotencyKey, $paymentMethodProvided): void {
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
                ->orderBy('id')
                ->lockForUpdate()
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
                $componentsSnapshot = [];

                if ($combo->products->isEmpty()) {
                    throw ValidationException::withMessages([
                        'stock' => "El combo {$combo->name} no tiene productos o ingredientes configurados.",
                    ]);
                }

                foreach ($combo->products as $comboProduct) {
                    if ($comboProduct->business_id !== $businessId || ! in_array($comboProduct->kind, ['product', 'ingredient'], true)) {
                        throw ValidationException::withMessages([
                            'products' => "El combo {$combo->name} contiene un componente que no pertenece a este negocio.",
                        ]);
                    }

                    $componentQuantity = (int) $comboProduct->pivot->quantity;
                    $required = $componentQuantity * $quantity;
                    $stockAdjustments[$comboProduct->id] = ($stockAdjustments[$comboProduct->id] ?? 0) + $required;
                    $componentsSnapshot[] = [
                        'product_id' => $comboProduct->id,
                        'name' => $comboProduct->name,
                        'quantity' => $componentQuantity,
                        'unit_label' => $comboProduct->unit_label,
                    ];
                }

                $lineSubtotal = $combo->price * $quantity;
                $subtotal += $lineSubtotal;
                $comboItems[] = compact('combo', 'quantity', 'lineSubtotal', 'componentsSnapshot');
            }

            if (empty($productItems) && empty($comboItems)) {
                throw ValidationException::withMessages([
                    'products' => 'Debe agregar al menos un producto o combo a la venta.',
                ]);
            }

            $stockProducts = Product::query()
                ->where('business_id', $businessId)
                ->whereIn('id', array_keys($stockAdjustments))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $availableLots = InventoryLot::query()
                ->where('business_id', $businessId)
                ->whereIn('product_id', array_keys($stockAdjustments))
                ->where('available_quantity', '>', 0)
                ->where(fn (Builder $query) => $query
                    ->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', today()))
                ->orderBy('product_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->groupBy('product_id');
            $stockShortages = [];

            foreach ($stockAdjustments as $productId => $required) {
                $product = $stockProducts->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'products' => 'Uno de los productos o ingredientes ya no está disponible en este negocio.',
                    ]);
                }

                $lotStock = (int) ($availableLots->get($product->id)?->sum('available_quantity') ?? 0);
                $available = $product->active
                    ? min((int) $product->stock, $lotStock)
                    : 0;

                if ($required > $available) {
                    $unit = mb_strtolower($product->unit_label);
                    $formatQuantity = static fn (int $quantity): string => $quantity.' '.($unit === 'unidades'
                        ? ($quantity === 1 ? 'unidad' : 'unidades')
                        : $unit);
                    $missing = $required - $available;
                    $missingVerb = $missing === 1 ? 'falta' : 'faltan';
                    $stockShortages[] = "{$product->name}: se necesitan {$formatQuantity($required)}, hay {$formatQuantity($available)} y {$missingVerb} {$formatQuantity($missing)}.";
                }
            }

            if ($stockShortages !== []) {
                if ($productItems === [] && $comboItems !== []) {
                    $comboQuantity = array_sum(array_column($comboItems, 'quantity'));
                    $comboLabel = $comboQuantity === 1 ? 'combo' : 'combos';
                    array_unshift($stockShortages, "Stock insuficiente para vender {$comboQuantity} {$comboLabel}.");
                } else {
                    array_unshift($stockShortages, 'Stock insuficiente para completar la venta.');
                }

                throw ValidationException::withMessages(['stock' => $stockShortages]);
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax = 0;
            $total = round(max($subtotal - $discount, 0), 2);
            $cashReceived = null;
            if ($data['payment_method'] === 'cash') {
                if ($paymentMethodProvided && ! isset($data['cash_received'])) {
                    throw ValidationException::withMessages([
                        'cash_received' => 'Ingrese el dinero recibido en efectivo.',
                    ]);
                }

                $cashReceived = isset($data['cash_received'])
                    ? (float) $data['cash_received']
                    : $total;
            }

            if ($cashReceived !== null && $cashReceived < $total) {
                throw ValidationException::withMessages([
                    'cash_received' => 'El monto recibido no puede ser menor que el total de la venta.',
                ]);
            }

            $isElectronicPayment = in_array($data['payment_method'], [
                'bank_transfer',
                'qr',
                'debit_card',
                'credit_card',
                'other',
            ], true);
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'client_id' => $data['client_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $isElectronicPayment ? ($data['payment_reference'] ?? null) : null,
                'payment_entity' => $isElectronicPayment ? ($data['payment_entity'] ?? null) : null,
                'payment_paid_at' => $data['payment_method'] === 'credit'
                    ? null
                    : ($data['payment_paid_at'] ?? now()),
                'payment_notes' => $isElectronicPayment ? ($data['payment_notes'] ?? null) : null,
                'cash_received' => $cashReceived,
                'change_due' => $cashReceived !== null ? round($cashReceived - $total, 2) : null,
                'idempotency_key' => $idempotencyKey,
            ]);

            if ($data['payment_method'] === 'credit') {
                Receivable::create([
                    'sale_id' => $sale->id,
                    'client_id' => $data['client_id'],
                    'created_by' => $request->user()->id,
                    'original_amount' => $total,
                    'paid_amount' => 0,
                    'balance' => $total,
                    'due_date' => $data['due_date'] ?? null,
                    'observations' => $data['receivable_observations'] ?? null,
                    'status' => $total > 0 ? 'pending' : 'paid',
                ]);
            }

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
                    'components_snapshot' => [
                        'combo_name' => $combo->name,
                        'components' => $item['componentsSnapshot'],
                    ],
                ]);

            }
            }, 3);
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'sales.idempotency_key')) {
                throw $exception;
            }

            $duplicate = Sale::where('business_id', $request->user()->business_id)
                ->where('user_id', $request->user()->id)
                ->where('idempotency_key', $idempotencyKey)
                ->exists();

            if (! $duplicate) {
                throw $exception;
            }

            return Redirect::route('sales.index')->with('success', 'Esta venta ya había sido registrada.');
        }

        return Redirect::route('sales.index')->with('success', $data['payment_method'] === 'credit'
            ? 'Venta a crédito registrada y deuda creada correctamente.'
            : 'Venta registrada correctamente.');
    }

    public function show(Sale $sale)
    {
        $this->ensureSaleAccess($sale);
        $sale->load('products.product', 'combos.combo', 'client', 'user', 'receivable', 'canceller');

        return view('sales.show', compact('sale'));
    }

    public function receipt(Sale $sale)
    {
        $this->ensureSaleAccess($sale);
        $sale->load('products.product', 'combos.combo', 'client', 'user', 'receivable', 'canceller');

        return view('sales.receipt', compact('sale'));
    }

    public function receiptPdf(Sale $sale)
    {
        $this->ensureSaleAccess($sale);
        $sale->load('products.product', 'combos.combo', 'client', 'user', 'receivable', 'canceller');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.receipt', compact('sale'));

        return $pdf->download("comprobante_venta_{$sale->id}.pdf");
    }

    public function destroy(Sale $sale, InventoryService $inventory)
    {
        $this->ensureSaleAccess($sale);
        $data = request()->validate([
            'cancellation_reason' => 'required|string|min:5|max:2000',
        ]);

        DB::transaction(function () use ($sale, $inventory, $data): void {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($sale->cancelled_at) {
                throw ValidationException::withMessages([
                    'sale' => 'Esta venta ya fue anulada.',
                ]);
            }

            $receivable = $sale->receivable()->lockForUpdate()->first();
            $cancelledAt = now();
            $claimed = Sale::whereKey($sale->id)
                ->where('business_id', $sale->business_id)
                ->whereNull('cancelled_at')
                ->update([
                    'cancelled_at' => $cancelledAt,
                    'cancelled_by' => auth()->id(),
                    'cancellation_reason' => $data['cancellation_reason'],
                    'updated_at' => $cancelledAt,
                ]);

            if ($claimed !== 1) {
                throw ValidationException::withMessages([
                    'sale' => 'Esta venta ya fue anulada.',
                ]);
            }

            $movements = InventoryMovement::where('business_id', $sale->business_id)
                ->where('sale_id', $sale->id)
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
                    $snapshotComponents = $item->components_snapshot['components'] ?? null;

                    if (is_array($snapshotComponents)) {
                        foreach ($snapshotComponents as $component) {
                            $product = Product::query()
                                ->where('business_id', $sale->business_id)
                                ->findOrFail($component['product_id']);
                            $inventory->receive(
                                $product,
                                (int) $component['quantity'] * $item->quantity,
                                "Anulación de venta #{$sale->id}",
                                auth()->id()
                            );
                        }

                        continue;
                    }

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

            if ($receivable) {
                ReceivablePayment::where('receivable_id', $receivable->id)
                    ->where('business_id', $sale->business_id)
                    ->whereNull('reversed_at')
                    ->update([
                        'reversed_at' => $cancelledAt,
                        'reversed_by' => auth()->id(),
                        'reversal_reason' => "Anulación de venta #{$sale->id}: {$data['cancellation_reason']}",
                        'updated_at' => $cancelledAt,
                    ]);

                $receivable->update([
                    'status' => 'cancelled',
                    'paid_amount' => 0,
                    'balance' => 0,
                ]);
            }
        }, 3);

        return Redirect::route('sales.index')->with('success', 'Venta anulada y registrada en el historial.');
    }

    private function ensureSaleAccess(Sale $sale): void
    {
        abort_unless(
            (int) $sale->business_id === (int) auth()->user()->business_id
                && in_array(auth()->user()->role, ['dueno', 'admin', 'cajero', 'superadmin'], true),
            404
        );
    }
}
