<div class="form-group">
    <label>Categoría</label>
    <select name="category_id" class="form-control" required>
        <option value="">Seleccione una categoría</option>
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
    <textarea name="description" class="form-control" rows="4" placeholder="Opcional">{{ old('description', isset($product) ? $product->description : null) }}</textarea>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="kind">Tipo de artículo</label>
            <select id="kind" name="kind" class="form-control" required>
                <option value="product" @selected(old('kind', $product->kind ?? 'product') === 'product')>Producto vendible</option>
                <option value="ingredient" @selected(old('kind', $product->kind ?? 'product') === 'ingredient')>Insumo</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="unit">Unidad base</label>
            <select id="unit" name="unit" class="form-control" required>
                <option value="piece" @selected(old('unit', $product->unit ?? 'piece') === 'piece')>Pieza(s)</option>
                <option value="g" @selected(old('unit', $product->unit ?? 'piece') === 'g')>Gramos (g)</option>
                <option value="ml" @selected(old('unit', $product->unit ?? 'piece') === 'ml')>Mililitros (ml)</option>
            </select>
            <small class="form-text text-muted">Las unidades no se pueden cambiar después de registrar inventario o recetas.</small>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Precio</label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', isset($product) ? $product->price : null) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Costo por unidad base</label>
            <input type="number" step="0.01" min="0" name="cost" value="{{ old('cost', isset($product) ? $product->cost : 0) }}" class="form-control" placeholder="0.00">
            <small class="form-text text-muted">Usado para stock inicial y ajustes manuales. Las compras guardan su costo real y lo convierten a la unidad base.</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Stock</label>
            <input type="number" min="0" name="stock" value="{{ old('stock', isset($product) ? $product->stock : 0) }}" class="form-control" required>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Stock mínimo</label>
            <input type="number" min="0" name="stock_minimo" value="{{ old('stock_minimo', isset($product) ? $product->stock_minimo : 0) }}" class="form-control" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Vencimiento para stock inicial o nuevo ajuste</label>
            <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', isset($product) ? $product->fecha_vencimiento : '') }}" class="form-control">
            <small class="form-text text-muted">Las fechas de cada lote se administran desde compras o inventario; este dato no modifica lotes existentes.</small>
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
    <input type="hidden" name="active" value="0">
    <input type="checkbox" name="active" value="1" class="form-check-input" id="active" {{ old('active', isset($product) ? $product->active : true) ? 'checked' : '' }}>
    <label class="form-check-label" for="active">Activo</label>
</div>
