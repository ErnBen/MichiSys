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
    <div class="border rounded px-2 py-2" style="max-height: 300px; overflow-y: auto;">
        @foreach($products ?? [] as $product)
            <div class="form-check">
                <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input" id="provider_product_{{ $product->id }}" @checked(in_array($product->id, old('products', $provider?->products->pluck('id')->toArray() ?? [])))>
                <label class="form-check-label" for="provider_product_{{ $product->id }}">{{ $product->name }} @if($product->category) <small class="text-muted">({{ $product->category->name }})</small>@endif</label>
            </div>
        @endforeach
        @if(($products ?? collect())->isEmpty())
            <p class="text-muted mb-0">No hay productos disponibles para asociar.</p>
        @endif
    </div>
</div>
