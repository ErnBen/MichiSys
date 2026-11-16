@extends('adminlte::page')

@section('title', 'Registrar venta')

@section('content_header')
    <h1>Registrar venta</h1>
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
                        <a href="{{ route('prices.index') }}" class="btn btn-sm btn-outline-secondary float-right">Consultar precios</a>
                    </div>
                    <div class="card-body">
                        <input
                            type="search"
                            id="product-search"
                            class="form-control"
                            placeholder="Buscar por nombre o código"
                            aria-label="Buscar productos"
                        >
                    </div>
                </div>

                <div class="row" id="product-catalog">
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
                            <div class="row">
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
                <div class="card sale-summary-card">
                    <div class="card-header"><h3 class="card-title">Carrito de venta</h3><span class="badge badge-primary float-right" id="cart-count">0</span></div>
                    <div class="card-body">
                        <div id="cart-empty" class="text-muted text-center py-3">Agrega productos o combos al carrito.</div>
                        <ul id="cart-items" class="list-group list-group-flush mb-3" aria-live="polite"></ul>
                        <div class="form-group">
                            <label for="client_id">Cliente</label>
                            <select name="client_id" id="client_id" class="form-control">
                                <option value="">Consumidor final</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>
                                        {{ $client->name }}{{ $client->phone ? ' ('.$client->phone.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
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
                        <button type="submit" id="confirm-sale" class="btn btn-success btn-lg btn-block py-3">Confirmar venta</button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-lg btn-block">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
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
            const checks = [...document.querySelectorAll('.sale-item-check')];
            const quantities = [...document.querySelectorAll('.sale-item-quantity')];
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

            const updateTotals = () => {
                let subtotal = 0;
                checks.forEach((checkbox) => {
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
                client.required = method === 'credit';
                updateTotals();
            };

            const quantityFor = checkbox => document.getElementById(
                checkbox.name.startsWith('combo_')
                    ? `combo-quantity-${checkbox.value}`
                    : `product-quantity-${checkbox.value}`
            );

            const renderCart = () => {
                cartList.replaceChildren();
                let count = 0;
                checks.forEach(checkbox => {
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
                    item.append(label, remove);
                    cartList.append(item);
                    count += Number(quantity.value || 0);
                });
                cartEmpty.hidden = cartList.children.length > 0;
                cartCount.textContent = String(count);
                updateTotals();
            };

            search.addEventListener('input', () => {
                const term = search.value.trim().toLocaleLowerCase();
                document.querySelectorAll('.catalog-item').forEach((item) => {
                    item.hidden = !item.dataset.search.includes(term);
                });
            });

            checks.forEach(checkbox => checkbox.addEventListener('change', () => {
                const button = document.querySelector(`[data-checkbox="${checkbox.id}"]`);
                if (button) button.textContent = checkbox.checked ? 'Quitar del carrito' : 'Agregar al carrito';
                renderCart();
            }));
            document.querySelectorAll('.sale-add-button').forEach(button => button.addEventListener('click', () => {
                const checkbox = document.getElementById(button.dataset.checkbox);
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            }));
            document.querySelectorAll('.quantity-step').forEach(button => button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const min = Number(input.min || 1);
                const max = Number(input.max || Number.MAX_SAFE_INTEGER);
                input.value = String(Math.min(max, Math.max(min, Number(input.value || min) + Number(button.dataset.step))));
                input.dispatchEvent(new Event('input', {bubbles: true}));
            }));
            cartList.addEventListener('click', event => {
                const button = event.target.closest('.cart-remove');
                if (!button) return;
                const checkbox = document.getElementById(button.dataset.checkbox);
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            });
            quantities.forEach(input => input.addEventListener('input', renderCart));
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
