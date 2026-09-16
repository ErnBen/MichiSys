<div class="form-group">
    <label>Categoría</label>
    <select name="category_id" class="form-control">
        <option value="">Sin categoría</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @if(old('category_id', isset($product) ? $product->category_id : null) == $category->id) selected @endif>{{ $category->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Nombre</label>
    <input type="text" name="name" value="{{ old('name', isset($product) ? $product->name : null) }}" class="form-control" required>
</div>
<div class="form-group">
    <label>SKU</label>
    <input type="text" name="sku" value="{{ old('sku', isset($product) ? $product->sku : null) }}" class="form-control">
</div>
<div class="form-group">
    <label>Descripción</label>
    <textarea name="description" class="form-control" rows="4">{{ old('description', isset($product) ? $product->description : null) }}</textarea>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Precio</label>
            <input type="number" step="0.01" name="price" value="{{ old('price', isset($product) ? $product->price : null) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Costo</label>
            <input type="number" step="0.01" name="cost" value="{{ old('cost', isset($product) ? $product->cost : null) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" value="{{ old('stock', isset($product) ? $product->stock : 0) }}" class="form-control" required>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Stock mínimo</label>
            <input type="number" name="stock_minimo" value="{{ old('stock_minimo', isset($product) ? $product->stock_minimo : 0) }}" class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Fecha de vencimiento</label>
            <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', isset($product) ? $product->fecha_vencimiento : '') }}" class="form-control">
        </div>
    </div>
</div>
<div class="form-group">
    <label>Imagen</label>
    <input type="file" name="image" class="form-control-file">
    @if(isset($product) && $product->image)
        <div class="mt-2">
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-width: 150px;">
        </div>
    @endif
</div>
<div class="form-check">
    <input type="checkbox" name="active" class="form-check-input" id="active" {{ old('active', isset($product) ? $product->active : true) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Activo</label>
</div>
