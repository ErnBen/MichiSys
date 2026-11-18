<div class="form-group">
    <label for="combo-name">Nombre del combo</label>
    <input id="combo-name" type="text" name="name" value="{{ old('name', $combo?->name) }}" class="form-control" placeholder="Ej. Combo familiar" required>
</div>
<div class="form-group">
    <label for="combo-description">Descripción</label>
    <textarea id="combo-description" name="description" class="form-control" rows="3" placeholder="Describe brevemente qué incluye este combo">{{ old('description', $combo?->description) }}</textarea>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="combo-price">Precio</label>
            <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text">Gs.</span></div>
                <input id="combo-price" type="number" step="0.01" name="price" value="{{ old('price', $combo?->price) }}" class="form-control" min="0" placeholder="0" required>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <span class="combo-form-label">Productos del combo</span>
            <div class="product-selector combo-product-picker" data-product-selector data-mode="combo" data-active="1" data-search-url="{{ route('products.search') }}">
                <label for="combo-product-search" class="sr-only">Buscar productos</label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span></div>
                    <input id="combo-product-search" type="search" class="form-control" data-product-query placeholder="Buscar por nombre o código" autocomplete="off">
                </div>
                <div class="small text-muted mt-2 mb-2" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres. Se muestran hasta 20 resultados.</div>
                <div class="list-group combo-product-results mb-3" data-product-results></div>
                <div class="combo-selected-products" data-product-selected>
                    @forelse($products as $product)
                        <div class="product-selection-row" data-selected-product="{{ $product->id }}">
                            <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input" id="product_{{ $product->id }}" @checked(in_array($product->id, old('products', $combo?->products->pluck('id')->toArray() ?? [])))>
                            <label class="form-check-label" for="product_{{ $product->id }}">{{ $product->name }} <span class="text-muted">(Stock: {{ $product->stock }})</span></label>
                            <input id="quantity_{{ $product->id }}" type="number" min="1" name="quantities[{{ $product->id }}]" value="{{ old('quantities.' . $product->id, $combo?->products->find($product->id)?->pivot->quantity ?? 1) }}" class="form-control" aria-label="Cantidad de {{ $product->name }}">
                        </div>
                    @empty
                        <p class="combo-products-empty mb-0" data-product-empty>Los productos que agregues aparecerán aquí.</p>
                    @endforelse
            </div>
        </div>
    </div>
    @include('shared.product-selector-script')
</div>
<div class="form-group">
    <label for="combo-image">Imagen del combo</label>
    <input id="combo-image" type="file" name="image" class="form-control-file" accept="image/*">
    <small class="form-text text-muted">Opcional. Puedes cargar una imagen de hasta 2 MB.</small>
    @if(isset($combo) && $combo->image)
        <div class="mt-2">
            <img src="{{ asset('storage/' . $combo->image) }}" alt="{{ $combo->name }}" class="img-thumbnail" style="max-width: 150px;">
        </div>
    @endif
</div>
<div class="form-check">
    <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ old('active', $combo?->active, true) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Activo</label>
</div>
