<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="name" value="{{ old('name', $combo?->name) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Descripción</label>
    <textarea name="description" class="form-control" rows="4">{{ old('description', $combo?->description) }}</textarea>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Precio</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', $combo?->price) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Productos</label>
            <div class="border rounded px-2 py-2" style="max-height: 320px; overflow-y: auto;">
                @foreach($products as $product)
                    <div class="form-group form-check">
                        <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input" id="product_{{ $product->id }}" @if(in_array($product->id, old('products', $combo?->products->pluck('id')->toArray() ?? []))) checked @endif>
                        <label class="form-check-label" for="product_{{ $product->id }}">{{ $product->name }} (Stock: {{ $product->stock }})</label>
                        <input type="number" min="1" name="quantities[{{ $product->id }}]" value="{{ old('quantities.' . $product->id, $combo?->products->find($product->id)?->pivot->quantity ?? 1) }}" class="form-control form-control-sm mt-1" placeholder="Cantidad">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="form-group">
    <label>Imagen</label>
    <input type="file" name="image" class="form-control-file">
    @if(isset($combo) && $combo->image)
        <div class="mt-2">
            <img src="{{ asset('storage/' . $combo->image) }}" alt="{{ $combo->name }}" class="img-thumbnail" style="max-width: 150px;">
        </div>
    @endif
</div>
<div class="form-check">
    <input type="checkbox" name="active" class="form-check-input" id="active" {{ old('active', $combo?->active, true) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Activo</label>
</div>
