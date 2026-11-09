@extends('adminlte::page')
@section('title', 'Registrar compra')
@section('content_header')
    <h1>Registrar compra recibida</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            @if($providers->isEmpty() || $products->isEmpty())
                <div class="alert alert-warning">
                    Registra al menos un proveedor y un producto activo antes de ingresar una compra.
                </div>
            @else
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('purchases.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="provider_id">Proveedor</label>
                            <select id="provider_id" name="provider_id" class="form-control" required>
                                <option value="">Seleccionar proveedor</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" @selected(old('provider_id') == $provider->id)>{{ $provider->name }}</option>
                                @endforeach
                            </select>
                            @error('provider_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group col-md-3">
                            <label for="purchase_date">Fecha de compra</label>
                            <input id="purchase_date" type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                            @error('purchase_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group col-md-4">
                            <label for="invoice_number">N.º de factura (opcional)</label>
                            <input id="invoice_number" type="text" name="invoice_number" class="form-control" maxlength="100" value="{{ old('invoice_number') }}">
                            @error('invoice_number')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <h5 class="mt-3">Productos recibidos</h5>
                    <div id="purchase-items">
                        @foreach(old('items', [['product_id' => '', 'quantity' => 1, 'purchase_unit' => 'piece', 'unit_cost' => '']]) as $index => $item)
                            <div class="form-row align-items-end purchase-item-row">
                                <div class="form-group col-md-4">
                                    <label>Producto</label>
                                    <select name="items[{{ $index }}][product_id]" class="form-control purchase-product" required>
                                        <option value="">Seleccionar producto</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-base-cost="{{ $product->cost }}" data-base-unit="{{ $product->unit }}" @selected(($item['product_id'] ?? '') == $product->id)>{{ $product->name }} (stock: {{ $product->stock }} {{ $product->unit_label }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Cantidad</label>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control" min="0.001" step="0.001" value="{{ $item['quantity'] ?? 1 }}" required>
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Unidad</label>
                                    <select name="items[{{ $index }}][purchase_unit]" class="form-control purchase-unit" required>
                                        @foreach(['piece' => 'Pza', 'g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'l' => 'l'] as $unit => $label)
                                            <option value="{{ $unit }}" @selected(($item['purchase_unit'] ?? 'piece') === $unit)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Costo unitario</label>
                                    <input type="number" name="items[{{ $index }}][unit_cost]" class="form-control purchase-unit-cost" min="0" step="0.01" value="{{ $item['unit_cost'] ?? '' }}" required>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Lote</label>
                                    <input type="text" name="items[{{ $index }}][lot_number]" class="form-control" maxlength="100" value="{{ $item['lot_number'] ?? '' }}">
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Vencimiento</label>
                                    <input type="date" name="items[{{ $index }}][expires_at]" class="form-control" value="{{ $item['expires_at'] ?? '' }}">
                                </div>
                                <div class="form-group col-md-1">
                                    <button type="button" class="btn btn-outline-danger remove-purchase-item" aria-label="Quitar producto">&times;</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button id="add-purchase-item" type="button" class="btn btn-outline-primary">Agregar producto</button>
                    <hr>
                    <button type="submit" class="btn btn-primary">Registrar compra y actualizar stock</button>
                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Cancelar</a>
                </form>

                <template id="purchase-item-template">
                    <div class="form-row align-items-end purchase-item-row">
                        <div class="form-group col-md-4">
                            <label>Producto</label>
                            <select name="items[__INDEX__][product_id]" class="form-control purchase-product" required>
                                <option value="">Seleccionar producto</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-base-cost="{{ $product->cost }}" data-base-unit="{{ $product->unit }}">{{ $product->name }} (stock: {{ $product->stock }} {{ $product->unit_label }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-1">
                            <label>Cantidad</label>
                            <input type="number" name="items[__INDEX__][quantity]" class="form-control" min="0.001" step="0.001" value="1" required>
                        </div>
                        <div class="form-group col-md-1">
                            <label>Unidad</label>
                            <select name="items[__INDEX__][purchase_unit]" class="form-control purchase-unit" required>
                                <option value="piece">Pza</option>
                                <option value="g">g</option>
                                <option value="kg">kg</option>
                                <option value="ml">ml</option>
                                <option value="l">l</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Costo unitario</label>
                            <input type="number" name="items[__INDEX__][unit_cost]" class="form-control purchase-unit-cost" min="0" step="0.01" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Lote</label>
                            <input type="text" name="items[__INDEX__][lot_number]" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group col-md-1">
                            <label>Vencimiento</label>
                            <input type="date" name="items[__INDEX__][expires_at]" class="form-control">
                        </div>
                        <div class="form-group col-md-1">
                            <button type="button" class="btn btn-outline-danger remove-purchase-item" aria-label="Quitar producto">&times;</button>
                        </div>
                    </div>
                </template>
            @endif
        </div>
    </div>
@stop
@section('js')
    <script>
        (() => {
            const container = document.getElementById('purchase-items');
            const template = document.getElementById('purchase-item-template');
            const addButton = document.getElementById('add-purchase-item');

            if (!container || !template || !addButton) return;

            const factors = {piece: 1, g: 1, kg: 1000, ml: 1, l: 1000};
            const updateUnitCost = row => {
                const product = row.querySelector('.purchase-product').selectedOptions[0];
                const unit = row.querySelector('.purchase-unit').value;
                const baseCost = Number(product.dataset.baseCost || 0);
                const multiplier = factors[unit];
                row.querySelector('.purchase-unit-cost').value =
                    product.value && multiplier ? (baseCost * multiplier).toFixed(2) : '';
            };

            let nextIndex = Array.from(container.querySelectorAll('[name^="items["]'))
                .reduce((maximum, input) => {
                    const match = input.name.match(/^items\[(\d+)\]/);
                    return Math.max(maximum, match ? Number(match[1]) : -1);
                }, -1) + 1;

            const addRow = () => {
                const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                container.insertAdjacentHTML('beforeend', html);
            };

            addButton.addEventListener('click', addRow);
            container.addEventListener('change', event => {
                if (event.target.matches('.purchase-product')) {
                    const row = event.target.closest('.purchase-item-row');
                    const selected = event.target.selectedOptions[0];
                    const unitSelect = row.querySelector('.purchase-unit');
                    if (selected.dataset.baseUnit) unitSelect.value = selected.dataset.baseUnit;
                    updateUnitCost(row);
                } else if (event.target.matches('.purchase-unit')) {
                    updateUnitCost(event.target.closest('.purchase-item-row'));
                }
            });
            container.addEventListener('click', event => {
                if (event.target.closest('.remove-purchase-item')) {
                    event.target.closest('.purchase-item-row').remove();
                }
            });

            addRow();
        })();
    </script>
@stop
