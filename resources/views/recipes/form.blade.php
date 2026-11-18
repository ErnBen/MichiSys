@php
    $currentItems = old('items', isset($recipe) ? $recipe->items->map(fn ($item) => ['ingredient_id' => $item->ingredient_id, 'quantity' => $item->quantity])->all() : [['ingredient_id' => '', 'quantity' => 1]]);
    $selectedProduct = $products->firstWhere('id', old('product_id', $recipe->product_id ?? null));
@endphp
<div class="form-group product-selector" data-product-selector data-mode="single" data-kind="product" data-active="1" data-without-recipe="1" data-except-recipe-id="{{ $recipe->id ?? '' }}" data-search-url="{{ route('products.search') }}">
    <label for="product-search">Producto final</label>
    <input id="product-search" type="search" class="form-control mb-2" data-product-query placeholder="Buscar por nombre o código" autocomplete="off">
    <div class="small text-muted mb-2" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres.</div>
    <div class="list-group mb-2" data-product-results></div>
    <input type="hidden" id="product_id" name="product_id" value="{{ old('product_id', $recipe->product_id ?? '') }}" data-product-selected-id>
    <div class="font-weight-bold" data-product-selected>{{ $selectedProduct?->name }}{{ $selectedProduct ? ' ('.$selectedProduct->unit_label.')' : '' }}</div>
    @error('product_id')<small class="text-danger">{{ $message }}</small>@enderror
</div>
<div class="form-group">
    <label for="yield_quantity">Rendimiento en unidad base del producto final</label>
    <input id="yield_quantity" type="number" name="yield_quantity" class="form-control" min="1" step="1" value="{{ old('yield_quantity', $recipe->yield_quantity ?? 1) }}" required>
    <small class="form-text text-muted">Las cantidades de ingredientes se registran en g, ml o unidades. La producción debe respetar este rendimiento para evitar fracciones de unidad base.</small>
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
            @php($selectedIngredient = $ingredients->firstWhere('id', $item['ingredient_id'] ?? null))
            <div class="form-group col-md-8 product-selector" data-product-selector data-mode="single" data-kind="ingredient" data-active="1" data-search-url="{{ route('products.search') }}">
                <label>Insumo</label>
                <input type="search" class="form-control mb-2" data-product-query placeholder="Buscar insumo por nombre o código" autocomplete="off">
                <div class="small text-muted" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres.</div>
                <div class="list-group" data-product-results></div>
                <input type="hidden" name="items[{{ $index }}][ingredient_id]" value="{{ $item['ingredient_id'] ?? '' }}" data-product-selected-id>
                <div class="font-weight-bold" data-product-selected>{{ $selectedIngredient?->name }}{{ $selectedIngredient ? ' ('.$selectedIngredient->unit_label.')' : '' }}</div>
                @error("items.$index.ingredient_id")<small class="text-danger">{{ $message }}</small>@enderror
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
        <div class="form-group col-md-8 product-selector" data-product-selector data-mode="single" data-kind="ingredient" data-active="1" data-search-url="{{ route('products.search') }}">
            <label>Insumo</label>
            <input type="search" class="form-control mb-2" data-product-query placeholder="Buscar insumo por nombre o código" autocomplete="off">
            <div class="small text-muted" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres.</div>
            <div class="list-group" data-product-results></div>
            <input type="hidden" name="items[__INDEX__][ingredient_id]" data-product-selected-id>
            <div class="font-weight-bold" data-product-selected></div>
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
@include('shared.product-selector-script')
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
                window.initializeProductSelectors(container.lastElementChild);
            });
            container.addEventListener('click', event => {
                if (event.target.closest('.remove-recipe-item') && container.querySelectorAll('.recipe-item-row').length > 1) {
                    event.target.closest('.recipe-item-row').remove();
                }
            });
        })();
    </script>
@stop
