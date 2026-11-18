@extends('adminlte::page')

@section('title', 'Registrar venta')

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <h1>Punto de venta</h1>
        <a href="#sale-summary" class="btn btn-success">Ver carrito y cobrar</a>
    </div>
@stop

@section('content')
    @include('shared.alerts')

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST" id="sale-form">
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Buscar productos</h3>
                        <div class="d-flex flex-wrap justify-content-end">
                            <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary mr-2">Historial de ventas</a>
                            <a href="{{ route('receivables.index') }}" class="btn btn-sm btn-outline-secondary mr-2">Cuentas por cobrar</a>
                            <a href="{{ route('prices.index') }}" class="btn btn-sm btn-outline-secondary">Consultar precios</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <input
                            type="search"
                            id="product-search"
                            class="form-control"
                            placeholder="Buscar por nombre o código"
                            aria-label="Buscar productos"
                        >
                        <div class="form-group mt-3 mb-2">
                            <label for="product-category-filter">Categoría</label>
                            <select id="product-category-filter" class="form-control">
                                <option value="">Todas las categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="product-search-status" class="text-muted" role="status" aria-live="polite">
                            Escribe el nombre o código para buscar hasta 24 productos.
                        </div>
                    </div>
                </div>

                <div class="row" id="product-catalog" data-search-url="{{ route('sales.products.search') }}">
                    @foreach($products as $product)
                        <div
                            class="col-sm-6 col-xl-4 catalog-item"
                            data-search="{{ mb_strtolower($product->name.' '.$product->sku) }}"
                        >
                            <div class="card h-100 sale-product-card">
                                @if($product->image)
                                    <img
                                        src="{{ asset('storage/'.$product->image) }}"
                                        class="card-img-top"
                                        alt="{{ $product->name }}"
                                        style="height: 150px; object-fit: cover;"
                                    >
                                @else
                                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 150px;">
                                        <i class="fas fa-box-open fa-3x text-muted" aria-hidden="true"></i>
                                        <span class="sr-only">Sin imagen</span>
                                    </div>
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title">{{ $product->name }}</h5>
                                    <p class="h5 mb-1">Gs. {{ number_format($product->price, 0, ',', '.') }}</p>
                                    <p class="text-muted mb-2">Disponible: {{ (int) ($product->sellable_stock ?? 0) }} {{ $product->unit_label }}</p>
                                    <div class="d-none">
                                        <input
                                            type="checkbox"
                                            class="sale-item-check"
                                            id="product-{{ $product->id }}"
                                            name="product_ids[]"
                                            value="{{ $product->id }}"
                                            data-price="{{ $product->price }}"
                                            data-stock="{{ min((int) $product->stock, (int) ($product->sellable_stock ?? 0)) }}"
                                            @disabled(($product->sellable_stock ?? 0) <= 0)
                                            @checked(in_array($product->id, (array) old('product_ids', []), true))
                                        >
                                    </div>
                                    <div class="input-group input-group-lg quantity-control mt-3">
                                        <div class="input-group-prepend">
                                            <button type="button" class="btn btn-outline-secondary quantity-step" data-target="product-quantity-{{ $product->id }}" data-step="-1" aria-label="Disminuir cantidad">−</button>
                                        </div>
                                        <input
                                            type="number"
                                            id="product-quantity-{{ $product->id }}"
                                            name="product_quantities[{{ $product->id }}]"
                                            value="{{ old('product_quantities.'.$product->id, 1) }}"
                                            min="1"
                                            max="{{ max(1, (int) ($product->sellable_stock ?? 0)) }}"
                                            class="form-control sale-item-quantity text-center"
                                            aria-label="Cantidad en {{ $product->unit_label }}"
                                        >
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary quantity-step" data-target="product-quantity-{{ $product->id }}" data-step="1" aria-label="Aumentar cantidad">+</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-success btn-lg btn-block mt-2 sale-add-button" data-checkbox="product-{{ $product->id }}" @disabled(($product->sellable_stock ?? 0) <= 0)>
                                        {{ in_array($product->id, (array) old('product_ids', []), true) ? 'Quitar del carrito' : 'Agregar al carrito' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($combos->isNotEmpty())
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Combos</h3></div>
                        <div class="card-body">
                                <div class="row" id="combo-catalog">
                                @foreach($combos as $combo)
                                    <div class="col-md-6 catalog-item" data-search="{{ mb_strtolower($combo->name) }}">
                                        <div class="card h-100 sale-product-card">
                                            @if($combo->image)
                                                <img
                                                    src="{{ asset('storage/'.$combo->image) }}"
                                                    class="card-img-top"
                                                    alt="{{ $combo->name }}"
                                                    style="height: 150px; object-fit: cover;"
                                                >
                                            @endif
                                            <div class="card-body">
                                                <h5 class="card-title">{{ $combo->name }}</h5>
                                                <p class="h5">Gs. {{ number_format($combo->price, 0, ',', '.') }}</p>
                                                <div class="d-none">
                                                    <input
                                                        type="checkbox"
                                                        class="sale-item-check"
                                                        id="combo-{{ $combo->id }}"
                                                        name="combo_ids[]"
                                                        value="{{ $combo->id }}"
                                                        data-price="{{ $combo->price }}"
                                                        data-components="{{ $combo->products->map(fn ($product) => [
                                                            'id' => $product->id,
                                                            'name' => $product->name,
                                                            'quantity' => (int) $product->pivot->quantity,
                                                            'unit' => mb_strtolower($product->unit_label),
                                                            'available' => $product->active
                                                                ? min((int) $product->stock, (int) ($product->sellable_stock ?? 0))
                                                                : 0,
                                                        ])->values()->toJson() }}"
                                                        @checked(in_array($combo->id, (array) old('combo_ids', []), true))
                                                    >
                                                </div>
                                                <div class="input-group input-group-lg quantity-control mt-3">
                                                    <div class="input-group-prepend">
                                                        <button type="button" class="btn btn-outline-secondary quantity-step" data-target="combo-quantity-{{ $combo->id }}" data-step="-1" aria-label="Disminuir cantidad">−</button>
                                                    </div>
                                                    <input
                                                        type="number"
                                                        id="combo-quantity-{{ $combo->id }}"
                                                        name="combo_quantities[{{ $combo->id }}]"
                                                        value="{{ old('combo_quantities.'.$combo->id, 1) }}"
                                                        min="1"
                                                        class="form-control sale-item-quantity text-center"
                                                        aria-label="Cantidad del combo"
                                                    >
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-outline-secondary quantity-step" data-target="combo-quantity-{{ $combo->id }}" data-step="1" aria-label="Aumentar cantidad">+</button>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-success btn-lg btn-block mt-2 sale-add-button" data-checkbox="combo-{{ $combo->id }}">
                                                    {{ in_array($combo->id, (array) old('combo_ids', []), true) ? 'Quitar del carrito' : 'Agregar al carrito' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-12 col-lg-4">
                <div class="card sale-summary-card" id="sale-summary">
                    <div class="card-header"><h3 class="card-title">Carrito de venta</h3><span class="badge badge-primary float-right" id="cart-count">0</span></div>
                    <div class="card-body">
                        <div id="cart-empty" class="text-muted text-center py-3">Agrega productos o combos al carrito.</div>
                        <ul id="cart-items" class="list-group list-group-flush mb-3" aria-live="polite"></ul>
                        <div id="cart-stock-status" class="alert alert-danger py-2" role="status" aria-live="polite" hidden></div>
                        <div class="form-group">
                            <label for="client-search">Buscar cliente por nombre, documento o teléfono</label>
                            <input id="client-search" type="search" class="form-control" placeholder="Escribe al menos 2 caracteres" autocomplete="off">
                            <div id="client-search-status" class="small text-muted mt-1" role="status" aria-live="polite"></div>
                            <div id="client-search-results" class="list-group mt-2" data-search-url="{{ route('sales.clients.search') }}"></div>
                            <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id', $selectedClient?->id) }}">
                            <div id="selected-client" class="mt-2">
                                @if($selectedClient)
                                    <span class="badge badge-info">Cliente: {{ $selectedClient->name }}{{ $selectedClient->document ? ' · '.$selectedClient->document : '' }}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary clear-client">Quitar</button>
                                @else
                                    <span class="text-muted">Consumidor final</span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Método de pago</label>
                            <select name="payment_method" id="payment_method" class="form-control" required>
                                @foreach([
                                    'cash' => 'Efectivo',
                                    'bank_transfer' => 'Transferencia bancaria',
                                    'qr' => 'Pago mediante QR',
                                    'debit_card' => 'Tarjeta de débito',
                                    'credit_card' => 'Tarjeta de crédito',
                                    'other' => 'Otro método de pago',
                                    'credit' => 'Venta a crédito (deuda del cliente)',
                                ] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div id="cash-payment-fields">
                            <div class="form-group">
                                <label for="cash_received">Dinero recibido (Gs.)</label>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    name="cash_received"
                                    id="cash_received"
                                    value="{{ old('cash_received') }}"
                                    class="form-control form-control-lg"
                                    inputmode="numeric"
                                >
                                @error('cash_received')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="alert alert-success py-2" role="status">
                                Vuelto: <strong id="change-due">Gs. 0</strong>
                            </div>
                        </div>
                        <div id="electronic-payment-fields" hidden>
                            <div class="form-group">
                                <label for="payment_reference">N.º de comprobante o referencia</label>
                                <input type="text" name="payment_reference" id="payment_reference" maxlength="120" value="{{ old('payment_reference') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="payment_entity">Banco o plataforma (opcional)</label>
                                <input type="text" name="payment_entity" id="payment_entity" maxlength="120" value="{{ old('payment_entity') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="payment_paid_at">Fecha y hora del pago</label>
                                <input type="datetime-local" name="payment_paid_at" id="payment_paid_at" value="{{ old('payment_paid_at', now()->format('Y-m-d\TH:i')) }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="payment_notes">Observaciones (opcional)</label>
                                <textarea name="payment_notes" id="payment_notes" rows="2" class="form-control">{{ old('payment_notes') }}</textarea>
                            </div>
                        </div>
                        <div id="credit-fields" hidden>
                            <div class="form-group">
                                <label for="due_date">Fecha de vencimiento (opcional)</label>
                                <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="receivable_observations">Observaciones de la deuda</label>
                                <textarea name="receivable_observations" id="receivable_observations" rows="2" class="form-control">{{ old('receivable_observations') }}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="discount">Descuento</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="discount"
                                id="discount"
                                value="{{ old('discount', 0) }}"
                                class="form-control"
                            >
                        </div>
                        <dl class="row">
                            <dt class="col-7">Subtotal</dt>
                            <dd class="col-5 text-right" id="sale-subtotal">0,00</dd>
                            <dt class="col-7">Descuento</dt>
                            <dd class="col-5 text-right" id="sale-discount">0</dd>
                            <dt class="col-7 h4">Total</dt>
                            <dd class="col-5 text-right font-weight-bold h4" id="sale-total">Gs. 0</dd>
                        </dl>
                        <button type="submit" id="confirm-sale" class="btn btn-success btn-lg btn-block py-3" aria-label="Confirmar venta">Cobrar</button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-lg btn-block">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
        <aside class="pos-mobile-summary" aria-label="Resumen del carrito">
            <div><small class="text-muted"><span id="mobile-cart-count">0</span> unidades</small><strong id="mobile-cart-total">Gs. 0</strong></div>
            <a href="#sale-summary" class="btn btn-success btn-lg">Ver carrito y cobrar <i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></a>
        </aside>
    </form>
@stop

@section('css')
    <style>
        .sale-product-card .card-img-top { height: 150px; object-fit: cover; }
        .quantity-control .btn { min-width: 48px; font-size: 1.35rem; }
        .sale-add-button { min-height: 48px; }
        .sale-summary-card { position: sticky; top: 1rem; }
        #product-search { min-height: 54px; font-size: 1.1rem; }
        @media (max-width: 991.98px) {
            .sale-summary-card { position: static; }
        }
    </style>
@stop

@section('js')
    <script>
        (() => {
            const search = document.getElementById('product-search');
            const productCatalog = document.getElementById('product-catalog');
            const productCategory = document.getElementById('product-category-filter');
            const productSearchStatus = document.getElementById('product-search-status');
            const comboCatalog = document.getElementById('combo-catalog');
            const clientSearch = document.getElementById('client-search');
            const clientSearchResults = document.getElementById('client-search-results');
            const clientSearchStatus = document.getElementById('client-search-status');
            const selectedClient = document.getElementById('selected-client');
            const checks = () => [...document.querySelectorAll('.sale-item-check')];
            const discount = document.getElementById('discount');
            const currency = new Intl.NumberFormat('es-PY', { maximumFractionDigits: 0 });
            const paymentMethod = document.getElementById('payment_method');
            const cashFields = document.getElementById('cash-payment-fields');
            const electronicFields = document.getElementById('electronic-payment-fields');
            const creditFields = document.getElementById('credit-fields');
            const cashReceived = document.getElementById('cash_received');
            const client = document.getElementById('client_id');
            const form = document.getElementById('sale-form');
            const confirmButton = document.getElementById('confirm-sale');
            const cartList = document.getElementById('cart-items');
            const cartEmpty = document.getElementById('cart-empty');
            const cartCount = document.getElementById('cart-count');
            const cartStockStatus = document.getElementById('cart-stock-status');

            const updateTotals = () => {
                let subtotal = 0;
                checks().forEach((checkbox) => {
                    if (!checkbox.checked) return;
                    const quantity = document.getElementById(
                        checkbox.name.startsWith('combo_')
                            ? `combo-quantity-${checkbox.value}`
                            : `product-quantity-${checkbox.value}`
                    );
                    subtotal += Number(checkbox.dataset.price) * Number(quantity.value || 0);
                });
                const reducedSubtotal = Math.max(subtotal - Number(discount.value || 0), 0);
                document.getElementById('sale-subtotal').textContent = currency.format(subtotal);
                document.getElementById('sale-discount').textContent = currency.format(Number(discount.value || 0));
                document.getElementById('sale-total').textContent = `Gs. ${currency.format(reducedSubtotal)}`;
                document.getElementById('mobile-cart-total').textContent = `Gs. ${currency.format(reducedSubtotal)}`;
                const received = Number(cashReceived.value || 0);
                document.getElementById('change-due').textContent =
                    `Gs. ${currency.format(Math.max(received - reducedSubtotal, 0))}`;
            };

            const updatePaymentFields = () => {
                const method = paymentMethod.value;
                cashFields.hidden = method !== 'cash';
                electronicFields.hidden = !['bank_transfer', 'qr', 'debit_card', 'credit_card', 'other'].includes(method);
                creditFields.hidden = method !== 'credit';
                cashReceived.required = method === 'cash';
                updateTotals();
            };

            const quantityFor = checkbox => document.getElementById(
                checkbox.name.startsWith('combo_')
                    ? `combo-quantity-${checkbox.value}`
                    : `product-quantity-${checkbox.value}`
            );

            const validateCartStock = () => {
                const requirements = new Map();
                let comboQuantity = 0;
                let hasIndividualProducts = false;

                const addRequirement = (id, name, unit, quantity, available) => {
                    const key = String(id);
                    const requirement = requirements.get(key) || {name, unit, quantity: 0, available};
                    requirement.quantity += quantity;
                    requirement.available = available;
                    requirements.set(key, requirement);
                };

                checks().forEach(checkbox => {
                    if (!checkbox.checked) return;
                    const quantity = Number(quantityFor(checkbox).value || 0);

                    if (checkbox.name.startsWith('combo_')) {
                        comboQuantity += quantity;
                        JSON.parse(checkbox.dataset.components || '[]').forEach(component => {
                            addRequirement(
                                component.id,
                                component.name,
                                component.unit,
                                Number(component.quantity) * quantity,
                                Number(component.available)
                            );
                        });
                        return;
                    }

                    hasIndividualProducts = true;
                    const card = checkbox.closest('.catalog-item');
                    addRequirement(
                        checkbox.value,
                        card.querySelector('.card-title')?.textContent?.trim() || 'Producto',
                        card.querySelector('.sale-item-quantity')?.getAttribute('aria-label')?.replace('Cantidad en ', '') || 'unidades',
                        quantity,
                        Number(checkbox.dataset.stock || 0)
                    );
                });

                const shortages = [...requirements.values()]
                    .filter(item => item.quantity > item.available);
                cartStockStatus.replaceChildren();

                if (shortages.length) {
                    const formatQuantity = (quantity, unit) =>
                        `${quantity} ${unit === 'unidades' ? (quantity === 1 ? 'unidad' : 'unidades') : unit}`;
                    const heading = document.createElement('strong');
                    heading.textContent = !hasIndividualProducts && comboQuantity > 0
                        ? `Stock insuficiente para vender ${comboQuantity} ${comboQuantity === 1 ? 'combo' : 'combos'}.`
                        : 'Stock insuficiente para confirmar la venta.';
                    const list = document.createElement('ul');
                    list.className = 'mb-0 mt-1 pl-3';
                    shortages.forEach(item => {
                        const detail = document.createElement('li');
                        const missing = item.quantity - item.available;
                        detail.textContent = `${item.name}: se necesitan ${formatQuantity(item.quantity, item.unit)}; disponibles ${formatQuantity(item.available, item.unit)}; ${missing === 1 ? 'falta' : 'faltan'} ${formatQuantity(missing, item.unit)}.`;
                        list.append(detail);
                    });
                    cartStockStatus.append(heading, list);
                }

                cartStockStatus.hidden = shortages.length === 0;
                return shortages.length === 0;
            };

            const renderCart = () => {
                cartList.replaceChildren();
                let count = 0;
                checks().forEach(checkbox => {
                    if (!checkbox.checked) return;
                    const quantity = quantityFor(checkbox);
                    const card = checkbox.closest('.catalog-item');
                    const name = card.querySelector('.card-title')?.textContent?.trim() || 'Artículo';
                    const lineTotal = Number(checkbox.dataset.price) * Number(quantity.value || 0);
                    const item = document.createElement('li');
                    item.className = 'list-group-item px-0 d-flex justify-content-between align-items-center';
                    const label = document.createElement('span');
                    label.textContent = `${name} × ${quantity.value} — Gs. ${currency.format(lineTotal)}`;
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'btn btn-sm btn-outline-danger cart-remove';
                    remove.dataset.checkbox = checkbox.id;
                    remove.textContent = 'Quitar';
                    label.className = 'cart-line-name';
                    const controls = document.createElement('div');
                    controls.className = 'cart-line-controls';
                    [-1, 1].forEach(step => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-outline-secondary quantity-step';
                        button.dataset.target = quantity.id;
                        button.dataset.step = String(step);
                        button.setAttribute('aria-label', `${step > 0 ? 'Aumentar' : 'Disminuir'} cantidad de ${name}`);
                        button.textContent = step > 0 ? '+' : '−';
                        controls.append(button);
                    });
                    controls.append(remove);
                    item.append(label, controls);
                    cartList.append(item);
                    count += Number(quantity.value || 0);
                });
                cartEmpty.hidden = cartList.children.length > 0;
                cartCount.textContent = String(count);
                document.getElementById('mobile-cart-count').textContent = String(count);
                confirmButton.disabled = count === 0 || !validateCartStock();
                checks().forEach(checkbox => {
                    const button = document.querySelector(`[data-checkbox="${checkbox.id}"]`);
                    button?.setAttribute('aria-pressed', String(checkbox.checked));
                });
                updateTotals();
            };

            const makeProductCard = product => {
                const wrapper = document.createElement('div');
                wrapper.className = 'col-sm-6 col-xl-4 catalog-item';
                wrapper.dataset.search = `${product.name} ${product.sku || ''}`.toLocaleLowerCase();

                const card = document.createElement('div');
                card.className = 'card h-100 sale-product-card';
                const body = document.createElement('div');
                body.className = 'card-body';
                const name = document.createElement('h5');
                name.className = 'card-title';
                name.textContent = product.name;
                const code = document.createElement('p');
                code.className = 'text-muted mb-1';
                code.textContent = `${product.sku ? `Código: ${product.sku} · ` : ''}${product.category || 'Sin categoría'}`;
                const price = document.createElement('p');
                price.className = 'h5 mb-1';
                price.textContent = `Gs. ${currency.format(Number(product.price))}`;
                const stock = document.createElement('p');
                stock.className = 'text-muted mb-2';
                stock.textContent = `Disponible: ${product.sellable_stock} ${product.unit_label}`;

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'sale-item-check d-none';
                checkbox.id = `product-${product.id}`;
                checkbox.name = 'product_ids[]';
                checkbox.value = product.id;
                checkbox.dataset.price = product.price;

                const quantityRow = document.createElement('div');
                quantityRow.className = 'input-group input-group-lg quantity-control mt-3';
                const decrementWrap = document.createElement('div');
                decrementWrap.className = 'input-group-prepend';
                const decrement = document.createElement('button');
                decrement.type = 'button';
                decrement.className = 'btn btn-outline-secondary quantity-step';
                decrement.dataset.target = `product-quantity-${product.id}`;
                decrement.dataset.step = '-1';
                decrement.setAttribute('aria-label', 'Disminuir cantidad');
                decrement.textContent = '−';
                decrementWrap.append(decrement);

                const quantity = document.createElement('input');
                quantity.type = 'number';
                quantity.id = `product-quantity-${product.id}`;
                quantity.name = `product_quantities[${product.id}]`;
                quantity.value = '1';
                quantity.min = '1';
                quantity.max = String(product.sellable_stock);
                quantity.className = 'form-control sale-item-quantity text-center';
                quantity.setAttribute('aria-label', `Cantidad en ${product.unit_label}`);

                const incrementWrap = document.createElement('div');
                incrementWrap.className = 'input-group-append';
                const increment = document.createElement('button');
                increment.type = 'button';
                increment.className = 'btn btn-outline-secondary quantity-step';
                increment.dataset.target = `product-quantity-${product.id}`;
                increment.dataset.step = '1';
                increment.setAttribute('aria-label', 'Aumentar cantidad');
                increment.textContent = '+';
                incrementWrap.append(increment);
                quantityRow.append(decrementWrap, quantity, incrementWrap);

                const add = document.createElement('button');
                add.type = 'button';
                add.className = 'btn btn-success btn-lg btn-block mt-2 sale-add-button';
                add.dataset.checkbox = checkbox.id;
                add.textContent = 'Agregar al carrito';

                body.append(name, code, price, stock, checkbox, quantityRow, add);
                if (product.image_url) {
                    const image = document.createElement('img');
                    image.src = product.image_url;
                    image.alt = product.name;
                    image.loading = 'lazy';
                    image.className = 'card-img-top';
                    card.append(image);
                }
                card.append(body);
                wrapper.append(card);
                return wrapper;
            };

            let searchTimer;
            let activeSearch;
            const findProducts = () => {
                window.clearTimeout(searchTimer);
                const term = search.value.trim();
                const categoryId = productCategory.value;
                comboCatalog?.querySelectorAll('.catalog-item').forEach(item => {
                    item.hidden = !item.dataset.search.includes(term.toLocaleLowerCase());
                });
                searchTimer = window.setTimeout(async () => {
                    activeSearch?.abort();
                    activeSearch = new AbortController();
                    productSearchStatus.textContent = 'Buscando productos…';
                    const params = new URLSearchParams({search: term, category_id: categoryId});
                    try {
                        const response = await fetch(`${productCatalog.dataset.searchUrl}?${params}`, {
                            headers: {'Accept': 'application/json'},
                            signal: activeSearch.signal
                        });
                        if (!response.ok) throw new Error('No se pudo completar la búsqueda de productos.');
                        const payload = await response.json();
                        const selected = [...productCatalog.querySelectorAll('.catalog-item')]
                            .filter(item => item.querySelector('.sale-item-check')?.checked);
                        productCatalog.replaceChildren(...selected);
                        payload.data.forEach(product => {
                            if (!document.getElementById(`product-${product.id}`)) {
                                productCatalog.append(makeProductCard(product));
                            }
                        });
                        productSearchStatus.textContent = payload.data.length
                            ? `${payload.data.length} producto(s). Se muestran hasta 24 resultados.`
                            : (selected.length ? 'No hay más resultados. Se conservaron los productos del carrito.' : 'No se encontraron productos disponibles.');
                    } catch (error) {
                        if (error.name === 'AbortError') return;
                        productSearchStatus.textContent = error.message || 'Ocurrió un error al buscar productos.';
                    }
                }, 250);
            };

            search.addEventListener('input', findProducts);
            productCategory.addEventListener('change', findProducts);
            let clientTimer;
            let activeClientSearch;
            clientSearch.addEventListener('input', () => {
                window.clearTimeout(clientTimer);
                clientTimer = window.setTimeout(async () => {
                    activeClientSearch?.abort();
                    const term = clientSearch.value.trim();
                    clientSearchResults.replaceChildren();
                    if (term.length < 2) {
                        clientSearchStatus.textContent = 'Escribe al menos 2 caracteres para buscar.';
                        return;
                    }

                    activeClientSearch = new AbortController();
                    clientSearchStatus.textContent = 'Buscando clientes…';
                    try {
                        const params = new URLSearchParams({search: term});
                        const response = await fetch(`${clientSearchResults.dataset.searchUrl}?${params}`, {
                            headers: {'Accept': 'application/json'},
                            signal: activeClientSearch.signal
                        });
                        if (!response.ok) throw new Error('No se pudo completar la búsqueda de clientes.');
                        const payload = await response.json();
                        payload.data.forEach(clientOption => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'list-group-item list-group-item-action';
                            button.dataset.clientId = clientOption.id;
                            button.dataset.clientName = clientOption.name;
                            button.dataset.clientDocument = clientOption.document || '';
                            button.textContent = `${clientOption.name}${clientOption.document ? ` · ${clientOption.document}` : ''}${clientOption.phone ? ` · ${clientOption.phone}` : ''}`;
                            clientSearchResults.append(button);
                        });
                        clientSearchStatus.textContent = payload.data.length
                            ? `${payload.data.length} resultado(s). Se muestran hasta 20.`
                            : 'No se encontraron clientes.';
                    } catch (error) {
                        if (error.name === 'AbortError') return;
                        clientSearchStatus.textContent = error.message || 'Ocurrió un error al buscar clientes.';
                    }
                }, 250);
            });
            clientSearchResults.addEventListener('click', event => {
                const button = event.target.closest('[data-client-id]');
                if (!button) return;
                client.value = button.dataset.clientId;
                selectedClient.replaceChildren();
                const label = document.createElement('span');
                label.className = 'badge badge-info';
                label.textContent = `Cliente: ${button.dataset.clientName}${button.dataset.clientDocument ? ` · ${button.dataset.clientDocument}` : ''}`;
                const clear = document.createElement('button');
                clear.type = 'button';
                clear.className = 'btn btn-sm btn-outline-secondary clear-client ml-2';
                clear.textContent = 'Quitar';
                selectedClient.append(label, clear);
                clientSearchResults.replaceChildren();
                clientSearchStatus.textContent = 'Cliente seleccionado.';
            });
            selectedClient.addEventListener('click', event => {
                if (!event.target.closest('.clear-client')) return;
                client.value = '';
                selectedClient.replaceChildren();
                const label = document.createElement('span');
                label.className = 'text-muted';
                label.textContent = 'Consumidor final';
                selectedClient.append(label);
            });
            form.addEventListener('change', event => {
                if (!event.target.matches('.sale-item-check')) return;
                const button = document.querySelector(`[data-checkbox="${event.target.id}"]`);
                if (button) button.textContent = event.target.checked ? 'Quitar del carrito' : 'Agregar al carrito';
                renderCart();
            });
            form.addEventListener('input', event => {
                if (event.target.matches('.sale-item-quantity')) renderCart();
            });
            form.addEventListener('click', event => {
                const addButton = event.target.closest('.sale-add-button');
                if (addButton) {
                    const checkbox = document.getElementById(addButton.dataset.checkbox);
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', {bubbles: true}));
                    return;
                }

                const stepButton = event.target.closest('.quantity-step');
                if (stepButton) {
                    const input = document.getElementById(stepButton.dataset.target);
                    const min = Number(input.min || 1);
                    const max = Number(input.max || Number.MAX_SAFE_INTEGER);
                    input.value = String(Math.min(max, Math.max(min, Number(input.value || min) + Number(stepButton.dataset.step))));
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                }
            });
            cartList.addEventListener('click', event => {
                const button = event.target.closest('.cart-remove');
                if (!button) return;
                const checkbox = document.getElementById(button.dataset.checkbox);
                if (!checkbox) return;
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            });
            discount.addEventListener('input', updateTotals);
            cashReceived.addEventListener('input', updateTotals);
            paymentMethod.addEventListener('change', updatePaymentFields);
            form.addEventListener('submit', event => {
                if (confirmButton.disabled) {
                    event.preventDefault();
                    return;
                }

                if (!window.confirm(paymentMethod.value === 'credit'
                    ? '¿Confirmas registrar esta venta a crédito y crear la deuda del cliente?'
                    : '¿Confirmas registrar la venta?')) {
                    event.preventDefault();
                    return;
                }

                confirmButton.disabled = true;
                confirmButton.textContent = 'Registrando venta…';
            });
            updatePaymentFields();
            renderCart();
        })();
    </script>
@stop
