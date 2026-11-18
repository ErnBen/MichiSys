@extends('adminlte::page')
@section('title', 'Registrar producción')
@section('content_header')
    <h1>Registrar producción</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card"><div class="card-body">
        <form action="{{ route('productions.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <div class="product-selector" data-product-selector data-mode="single" data-result-label="label" data-search-url="{{ route('productions.recipes.search') }}">
                    <label for="recipe-search">Buscar producto con receta</label>
                    <input id="recipe-search" type="search" class="form-control mb-2" data-product-query placeholder="Nombre o código" autocomplete="off">
                    <div class="small text-muted mb-2" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres.</div>
                    <div class="list-group mb-2" data-product-results></div>
                    <input type="hidden" id="recipe_id" name="recipe_id" value="{{ old('recipe_id', $recipes->first()?->id) }}" data-product-selected-id>
                    <div class="font-weight-bold" data-product-selected>
                    @if($recipes->isNotEmpty())
                        {{ $recipes->first()->product->name }} — rendimiento {{ $recipes->first()->yield_quantity }} {{ $recipes->first()->product->unit_label }}
                    @endif
                    </div>
                </div>
                @error('recipe_id')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
                <div class="form-group">
                    <label for="quantity">Cantidad final en unidad base</label>
                    <input id="quantity" type="number" name="quantity" class="form-control" min="1" step="1" value="{{ old('quantity', 1) }}" required>
                    <small class="form-text text-muted">Debe ser múltiplo del rendimiento indicado en la receta. Los insumos se descuentan primero de los lotes vigentes más próximos a vencer.</small>
                    @error('quantity')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="lot_number">Número de lote de producción (opcional)</label>
                        <input id="lot_number" type="text" name="lot_number" class="form-control" maxlength="100" value="{{ old('lot_number') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="expires_at">Vencimiento del producto (opcional)</label>
                        <input id="expires_at" type="date" name="expires_at" class="form-control" min="{{ now()->toDateString() }}" value="{{ old('expires_at') }}">
                        @error('expires_at')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Registrar producción</button>
                    <a href="{{ route('productions.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
        </form>
    </div></div>
    @include('shared.product-selector-script')
@stop
