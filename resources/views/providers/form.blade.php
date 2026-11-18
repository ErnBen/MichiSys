<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="name" value="{{ old('name', $provider?->name) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $provider?->email) }}" class="form-control">
</div>
<div class="form-group">
    <label>Teléfono</label>
    <input type="text" name="phone" value="{{ old('phone', $provider?->phone) }}" class="form-control">
</div>
<div class="form-group">
    <label>Dirección</label>
    <textarea name="address" class="form-control" rows="3">{{ old('address', $provider?->address) }}</textarea>
</div>
<div class="form-group">
    <label>Productos que proporciona</label>
    <div class="product-selector" data-product-selector data-mode="provider" data-search-url="{{ route('products.search') }}">
        <input type="search" class="form-control mb-2" data-product-query placeholder="Buscar por nombre o código" autocomplete="off">
        <div class="small text-muted mb-2" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres. Se muestran hasta 20 resultados.</div>
        <div class="list-group mb-2" data-product-results></div>
        <div class="border rounded px-2 py-2" data-product-selected>
        @foreach($products ?? [] as $product)
            <div class="form-check product-selection-row" data-selected-product="{{ $product->id }}">
                <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input" id="provider_product_{{ $product->id }}" @checked(in_array($product->id, old('products', $provider?->products->pluck('id')->toArray() ?? [])))>
                <label class="form-check-label" for="provider_product_{{ $product->id }}">{{ $product->name }} @if($product->category) <small class="text-muted">({{ $product->category->name }})</small>@endif</label>
            </div>
        @endforeach
            <p class="text-muted mb-0" data-product-empty @if(($products ?? collect())->isNotEmpty()) hidden @endif>No hay productos asociados todavía.</p>
        </div>
    </div>
</div>
@include('shared.product-selector-script')
