@extends('adminlte::page')
@section('title', 'Registrar compra')
@section('content_header')
    <h1>Registrar compra recibida</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            @if($providers->isEmpty() || !$hasProducts)
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
                        <div class="form-group col-12 col-md-5">
                            <label for="provider_id">Proveedor</label>
                            <select id="provider_id" name="provider_id" class="form-control" required>
                                <option value="">Seleccionar proveedor</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" @selected(old('provider_id') == $provider->id)>{{ $provider->name }}</option>
                                @endforeach
                            </select>
                            @error('provider_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group col-12 col-md-3">
                            <label for="purchase_date">Fecha de compra</label>
                            <input id="purchase_date" type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', now()->toDateString()) }}" required>
                            @error('purchase_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="form-group col-12 col-md-4">
                            <label for="invoice_number">N.º de factura (opcional)</label>
                            <input id="invoice_number" type="text" name="invoice_number" class="form-control" maxlength="100" value="{{ old('invoice_number') }}">
                            @error('invoice_number')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <section class="card card-outline card-info mt-3" aria-labelledby="purchase-product-search-heading">
                        <div class="card-header">
                            <h2 class="card-title" id="purchase-product-search-heading">Buscar productos para agregar</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-12 col-md-6">
                                    <label for="purchase-product-search">Nombre o código</label>
                                    <input id="purchase-product-search" type="search" class="form-control form-control-lg" placeholder="Escribe el nombre o SKU" autocomplete="off">
                                </div>
                                <div class="form-group col-6 col-md-3">
                                    <label for="purchase-category-filter">Categoría</label>
                                    <select id="purchase-category-filter" class="form-control form-control-lg">
                                        <option value="">Todas las categorías</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-6 col-md-3">
                                    <label for="purchase-provider-filter">Proveedor del producto</label>
                                    <select id="purchase-provider-filter" class="form-control form-control-lg">
                                        <option value="">Todos los proveedores</option>
                                        @foreach($providers as $provider)
                                            <option value="{{ $provider->id }}" @selected(old('provider_id') == $provider->id)>{{ $provider->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="purchase-search-status" class="text-muted mb-2" role="status" aria-live="polite">
                                Escribe para buscar productos. Se muestran hasta 20 resultados por consulta.
                            </div>
                            <div id="purchase-product-results" class="row" data-search-url="{{ route('purchases.products.search') }}"></div>
                        </div>
                    </section>

                    <h2 class="h5 mt-4">Productos recibidos</h2>
                    <p class="text-muted">Ingresa la cantidad física que llegó. El inventario se actualizará una sola vez por la cantidad convertida a la unidad base.</p>
                    <div id="purchase-items">
                        @foreach($oldItems as $index => $item)
                            @php($selectedProduct = $selectedProducts->get($item['product_id'] ?? ''))
                            <div class="purchase-item-row border rounded p-3 mb-3" data-base-cost="{{ $selectedProduct?->cost ?? 0 }}" data-base-unit="{{ $selectedProduct?->unit ?? 'piece' }}">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <strong class="purchase-product-name">{{ $selectedProduct?->name ?? 'Producto no disponible' }}</strong>
                                        <div class="small text-muted">{{ $selectedProduct?->sku ? 'Código: '.$selectedProduct->sku.' · ' : '' }}{{ $selectedProduct?->unit_label ?? '' }}</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-lg remove-purchase-item" aria-label="Quitar producto">&times;</button>
                                </div>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] ?? '' }}">
                                <div class="form-row">
                                    <div class="form-group col-6 col-lg-2">
                                        <label>Cantidad física recibida</label>
                                        <input type="number" name="items[{{ $index }}][quantity]" class="form-control form-control-lg" min="0.001" step="0.001" value="{{ $item['quantity'] ?? 1 }}" required>
                                    </div>
                                    <div class="form-group col-6 col-lg-2">
                                        <label>Unidad de compra</label>
                                        <select name="items[{{ $index }}][purchase_unit]" class="form-control form-control-lg purchase-unit" required>
                                            @foreach(['piece' => 'Unidades', 'g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'l' => 'l'] as $unit => $label)
                                                <option value="{{ $unit }}" @selected(($item['purchase_unit'] ?? 'piece') === $unit)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-12 col-lg-3">
                                        <label>Costo por unidad seleccionada</label>
                                        <input type="number" name="items[{{ $index }}][unit_cost]" class="form-control form-control-lg purchase-unit-cost" min="0" step="0.01" value="{{ $item['unit_cost'] ?? '' }}" required>
                                    </div>
                                    <div class="form-group col-6 col-lg-2">
                                        <label>Lote</label>
                                        <input type="text" name="items[{{ $index }}][lot_number]" class="form-control" maxlength="100" value="{{ $item['lot_number'] ?? '' }}">
                                    </div>
                                    <div class="form-group col-6 col-lg-3">
                                        <label>Vencimiento</label>
                                        <input type="date" name="items[{{ $index }}][expires_at]" class="form-control" value="{{ $item['expires_at'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('items')<small class="text-danger d-block mb-2">{{ $message }}</small>@enderror
                    <hr>
                    <div class="form-actions">
                        <button type="submit" id="submit-purchase" class="btn btn-primary">Registrar compra y actualizar stock</button>
                        <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@stop
@section('js')
    <script>
        (() => {
            const container = document.getElementById('purchase-items');
            const search = document.getElementById('purchase-product-search');
            const categoryFilter = document.getElementById('purchase-category-filter');
            const providerFilter = document.getElementById('purchase-provider-filter');
            const purchaseProvider = document.getElementById('provider_id');
            const results = document.getElementById('purchase-product-results');
            const status = document.getElementById('purchase-search-status');
            const form = container?.closest('form');

            if (!container || !search || !categoryFilter || !providerFilter || !results || !status || !form) return;

            const factors = {piece: 1, g: 1, kg: 1000, ml: 1, l: 1000};
            const units = {
                piece: [['piece', 'Unidades']],
                g: [['g', 'g'], ['kg', 'kg']],
                ml: [['ml', 'ml'], ['l', 'l']]
            };

            let nextIndex = Array.from(container.querySelectorAll('[name^="items["]'))
                .reduce((maximum, input) => {
                    const match = input.name.match(/^items\[(\d+)\]/);
                    return Math.max(maximum, match ? Number(match[1]) : -1);
                }, -1) + 1;

            const createField = (parent, labelText, name, type, value, wrapperClass = 'form-group col-6 col-lg-2') => {
                const wrapper = document.createElement('div');
                wrapper.className = wrapperClass;
                const label = document.createElement('label');
                label.textContent = labelText;
                const input = document.createElement('input');
                input.type = type;
                input.name = name;
                input.value = value;
                input.className = 'form-control';
                wrapper.append(label, input);
                parent.append(wrapper);
                return input;
            };

            const addProduct = product => {
                const existing = [...container.querySelectorAll('input[name$="[product_id]"]')]
                    .find(input => input.value === String(product.id));
                if (existing) {
                    status.textContent = `${product.name} ya está agregado. Modifica su cantidad en la lista.`;
                    existing.closest('.purchase-item-row').querySelector('input[name$="[quantity]"]').focus();
                    return;
                }

                const index = nextIndex++;
                const row = document.createElement('div');
                row.className = 'purchase-item-row border rounded p-3 mb-3';
                const heading = document.createElement('div');
                heading.className = 'd-flex justify-content-between align-items-start mb-3';
                const details = document.createElement('div');
                const productName = document.createElement('strong');
                productName.textContent = product.name;
                const productDetails = document.createElement('div');
                productDetails.className = 'small text-muted';
                productDetails.textContent = `${product.sku ? `Código: ${product.sku} · ` : ''}${product.unit_label} · Stock: ${product.stock} ${product.unit_label}`;
                details.append(productName, productDetails);
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn btn-outline-danger btn-lg remove-purchase-item';
                remove.textContent = 'Quitar';
                remove.setAttribute('aria-label', `Quitar ${product.name}`);
                heading.append(details, remove);
                row.append(heading);

                const productId = document.createElement('input');
                productId.type = 'hidden';
                productId.name = `items[${index}][product_id]`;
                productId.value = product.id;
                row.append(productId);

                const fields = document.createElement('div');
                fields.className = 'form-row';
                const quantity = createField(fields, 'Cantidad física recibida', `items[${index}][quantity]`, 'number', '1');
                quantity.classList.add('form-control-lg');
                quantity.min = '0.001';
                quantity.step = '0.001';

                const unitWrapper = document.createElement('div');
                unitWrapper.className = 'form-group col-6 col-lg-2';
                const unitLabel = document.createElement('label');
                unitLabel.textContent = 'Unidad de compra';
                const unitSelect = document.createElement('select');
                unitSelect.name = `items[${index}][purchase_unit]`;
                unitSelect.className = 'form-control form-control-lg purchase-unit';
                units[product.unit].forEach(([value, labelText]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = labelText;
                    unitSelect.append(option);
                });
                unitWrapper.append(unitLabel, unitSelect);
                fields.append(unitWrapper);

                const cost = createField(fields, 'Costo por unidad seleccionada', `items[${index}][unit_cost]`, 'number', '', 'form-group col-12 col-lg-3');
                cost.classList.add('form-control-lg', 'purchase-unit-cost');
                cost.min = '0';
                cost.step = '0.01';
                createField(fields, 'Lote', `items[${index}][lot_number]`, 'text', '', 'form-group col-6 col-lg-2')
                    .maxLength = 100;
                createField(fields, 'Vencimiento', `items[${index}][expires_at]`, 'date', '', 'form-group col-6 col-lg-3');
                row.append(fields);
                container.append(row);

                const updateCost = () => {
                    cost.value = (Number(product.cost || 0) * factors[unitSelect.value]).toFixed(2);
                };
                unitSelect.addEventListener('change', updateCost);
                updateCost();
                status.textContent = `${product.name} agregado a la compra.`;
            };

            const renderResults = products => {
                results.replaceChildren();
                products.forEach(product => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'col-12 col-md-6 col-lg-4 mb-2';
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-outline-primary btn-block text-left h-100';
                    button.textContent = `${product.name}${product.sku ? ` · ${product.sku}` : ''} · ${product.unit_label} · Stock: ${product.stock} · ${product.category || 'Sin categoría'}`;
                    button.addEventListener('click', () => addProduct(product));
                    wrapper.append(button);
                    results.append(wrapper);
                });
            };

            let timer;
            let activeRequest;
            const findProducts = () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(async () => {
                    activeRequest?.abort();
                    activeRequest = new AbortController();
                    status.textContent = 'Buscando productos…';
                    const params = new URLSearchParams({
                        search: search.value.trim(),
                        category_id: categoryFilter.value,
                        provider_id: providerFilter.value
                    });
                    try {
                        const response = await fetch(`${results.dataset.searchUrl}?${params}`, {
                            headers: {'Accept': 'application/json'},
                            signal: activeRequest.signal
                        });
                        if (!response.ok) throw new Error('No se pudo completar la búsqueda de productos.');
                        const payload = await response.json();
                        renderResults(payload.data);
                        status.textContent = payload.data.length
                            ? `${payload.data.length} resultado(s). Se muestran hasta 20 por búsqueda.`
                            : 'No se encontraron productos. Escribe un nombre/código o selecciona filtros.';
                    } catch (error) {
                        if (error.name === 'AbortError') return;
                        results.replaceChildren();
                        status.textContent = error.message || 'Ocurrió un error al buscar productos.';
                    }
                }, 250);
            };

            search.addEventListener('input', findProducts);
            categoryFilter.addEventListener('change', findProducts);
            providerFilter.addEventListener('change', findProducts);
            purchaseProvider.addEventListener('change', () => {
                providerFilter.value = purchaseProvider.value;
                findProducts();
            });
            container.addEventListener('click', event => {
                if (event.target.closest('.remove-purchase-item')) {
                    event.target.closest('.purchase-item-row').remove();
                }
            });

            form.addEventListener('submit', event => {
                const ids = [...container.querySelectorAll('input[name$="[product_id]"]')].map(input => input.value);
                if (!ids.length || new Set(ids).size !== ids.length) {
                    event.preventDefault();
                    status.textContent = ids.length
                        ? 'Cada producto debe aparecer una sola vez. Ajusta la cantidad en su fila.'
                        : 'Agrega al menos un producto antes de registrar la compra.';
                    return;
                }

                const submit = document.getElementById('submit-purchase');
                submit.disabled = true;
                submit.textContent = 'Registrando compra…';
            });
        })();
    </script>
@stop
