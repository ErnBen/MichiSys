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
                            <div class="card h-100">
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
                                    <p class="mb-1">Precio: {{ number_format($product->price, 2, ',', '.') }}</p>
                                    <p class="text-muted mb-2">Disponible: {{ (int) ($product->sellable_stock ?? 0) }} {{ $product->unit_label }}</p>
                                    <div class="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            class="custom-control-input sale-item-check"
                                            id="product-{{ $product->id }}"
                                            name="product_ids[]"
                                            value="{{ $product->id }}"
                                            data-price="{{ $product->price }}"
                                            @disabled(($product->sellable_stock ?? 0) <= 0)
                                            @checked(in_array($product->id, (array) old('product_ids', []), true))
                                        >
                                        <label class="custom-control-label" for="product-{{ $product->id }}">Agregar</label>
                                    </div>
                                    <label for="product-quantity-{{ $product->id }}" class="mt-2">Cantidad ({{ $product->unit_label }})</label>
                                    <input
                                        type="number"
                                        id="product-quantity-{{ $product->id }}"
                                        name="product_quantities[{{ $product->id }}]"
                                        value="{{ old('product_quantities.'.$product->id, 1) }}"
                                        min="1"
                                        max="{{ max(1, (int) ($product->sellable_stock ?? 0)) }}"
                                        class="form-control sale-item-quantity"
                                    >
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
                                        <div class="card h-100">
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
                                                <p>Precio: {{ number_format($combo->price, 2, ',', '.') }}</p>
                                                <div class="custom-control custom-checkbox">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input sale-item-check"
                                                        id="combo-{{ $combo->id }}"
                                                        name="combo_ids[]"
                                                        value="{{ $combo->id }}"
                                                        data-price="{{ $combo->price }}"
                                                        @checked(in_array($combo->id, (array) old('combo_ids', []), true))
                                                    >
                                                    <label class="custom-control-label" for="combo-{{ $combo->id }}">Agregar</label>
                                                </div>
                                                <label for="combo-quantity-{{ $combo->id }}" class="mt-2">Cantidad</label>
                                                <input
                                                    type="number"
                                                    id="combo-quantity-{{ $combo->id }}"
                                                    name="combo_quantities[{{ $combo->id }}]"
                                                    value="{{ old('combo_quantities.'.$combo->id, 1) }}"
                                                    min="1"
                                                    class="form-control sale-item-quantity"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Venta actual</h3></div>
                    <div class="card-body">
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
                            <dt class="col-7">Impuesto</dt>
                            <dd class="col-5 text-right" id="sale-tax">0,00</dd>
                            <dt class="col-7">Total estimado</dt>
                            <dd class="col-5 text-right font-weight-bold" id="sale-total">0,00</dd>
                        </dl>
                        <button type="submit" class="btn btn-success btn-block">Confirmar venta</button>
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary btn-block">Cancelar</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        (() => {
            const search = document.getElementById('product-search');
            const checks = [...document.querySelectorAll('.sale-item-check')];
            const quantities = [...document.querySelectorAll('.sale-item-quantity')];
            const discount = document.getElementById('discount');
            const currency = new Intl.NumberFormat('es-PY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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
                const tax = Math.round(reducedSubtotal * 0.19 * 100) / 100;
                document.getElementById('sale-subtotal').textContent = currency.format(subtotal);
                document.getElementById('sale-tax').textContent = currency.format(tax);
                document.getElementById('sale-total').textContent = currency.format(reducedSubtotal + tax);
            };

            search.addEventListener('input', () => {
                const term = search.value.trim().toLocaleLowerCase();
                document.querySelectorAll('.catalog-item').forEach((item) => {
                    item.hidden = !item.dataset.search.includes(term);
                });
            });

            checks.forEach((checkbox) => checkbox.addEventListener('change', updateTotals));
            quantities.forEach((input) => input.addEventListener('input', updateTotals));
            discount.addEventListener('input', updateTotals);
            updateTotals();
        })();
    </script>
@stop
