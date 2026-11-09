@php
    $currentItems = old('items', isset($recipe) ? $recipe->items->map(fn ($item) => ['ingredient_id' => $item->ingredient_id, 'quantity' => $item->quantity])->all() : [['ingredient_id' => '', 'quantity' => 1]]);
@endphp
<div class="form-group">
    <label for="product_id">Producto final</label>
    <select id="product_id" name="product_id" class="form-control" required>
        <option value="">Seleccionar producto vendible</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" @selected(old('product_id', $recipe->product_id ?? '') == $product->id)>{{ $product->name }} ({{ $product->unit_label }})</option>
        @endforeach
    </select>
    @error('product_id')<small class="text-danger">{{ $message }}</small>@enderror
</div>
<div class="form-group">
    <label for="yield_quantity">Rendimiento en unidad base del producto final</label>
    <input id="yield_quantity" type="number" name="yield_quantity" class="form-control" min="1" step="1" value="{{ old('yield_quantity', $recipe->yield_quantity ?? 1) }}" required>
    <small class="form-text text-muted">Las cantidades de ingredientes se registran en g, ml o piezas. La producción debe respetar este rendimiento para evitar fracciones de unidad base.</small>
    @error('yield_quantity')<small class="text-danger">{{ $message }}</small>@enderror
</div>
<div class="form-group">
    <label for="instructions">Instrucciones (opcional)</label>
    <textarea id="instructions" name="instructions" class="form-control" rows="3">{{ old('instructions', $recipe->instructions ?? '') }}</textarea>
</div>
<h5>Insumos por rendimiento</h5>
<div id="recipe-items">
    @foreach($currentItems as $index => $item)
        <div class="form-row align-items-end recipe-item-row">
            <div class="form-group col-md-8">
                <label>Insumo</label>
                <select name="items[{{ $index }}][ingredient_id]" class="form-control" required>
                    <option value="">Seleccionar insumo</option>
                    @foreach($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}" @selected(($item['ingredient_id'] ?? '') == $ingredient->id)>{{ $ingredient->name }} ({{ $ingredient->unit_label }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Cantidad base</label>
                <input type="number" name="items[{{ $index }}][quantity]" class="form-control" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" required>
            </div>
            <div class="form-group col-md-1">
                <button type="button" class="btn btn-outline-danger remove-recipe-item" aria-label="Quitar insumo">&times;</button>
            </div>
        </div>
    @endforeach
</div>
<button id="add-recipe-item" type="button" class="btn btn-outline-primary">Agregar insumo</button>
@if($errors->has('items'))
    <div class="text-danger mt-2">{{ $errors->first('items') }}</div>
@endif
<template id="recipe-item-template">
    <div class="form-row align-items-end recipe-item-row">
        <div class="form-group col-md-8">
            <label>Insumo</label>
            <select name="items[__INDEX__][ingredient_id]" class="form-control" required>
                <option value="">Seleccionar insumo</option>
                @foreach($ingredients as $ingredient)
                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit_label }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-3">
            <label>Cantidad base</label>
            <input type="number" name="items[__INDEX__][quantity]" class="form-control" min="1" step="1" value="1" required>
        </div>
        <div class="form-group col-md-1">
            <button type="button" class="btn btn-outline-danger remove-recipe-item" aria-label="Quitar insumo">&times;</button>
        </div>
    </div>
</template>
@section('js')
    <script>
        (() => {
            const container = document.getElementById('recipe-items');
            const template = document.getElementById('recipe-item-template');
            const addButton = document.getElementById('add-recipe-item');
            if (!container || !template || !addButton) return;

            let nextIndex = Array.from(container.querySelectorAll('[name^="items["]'))
                .reduce((maximum, input) => {
                    const match = input.name.match(/^items\[(\d+)\]/);
                    return Math.max(maximum, match ? Number(match[1]) : -1);
                }, -1) + 1;

            addButton.addEventListener('click', () => {
                container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)));
            });
            container.addEventListener('click', event => {
                if (event.target.closest('.remove-recipe-item') && container.querySelectorAll('.recipe-item-row').length > 1) {
                    event.target.closest('.recipe-item-row').remove();
                }
            });
        })();
    </script>
@stop
