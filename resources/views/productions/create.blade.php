@extends('adminlte::page')
@section('title', 'Registrar producción')
@section('content_header')
    <h1>Registrar producción</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card"><div class="card-body">
        @if($recipes->isEmpty())
            <div class="alert alert-warning">Primero crea una receta activa con al menos un insumo.</div>
        @else
            <form action="{{ route('productions.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="recipe_id">Receta</label>
                    <select id="recipe_id" name="recipe_id" class="form-control" required>
                        <option value="">Seleccionar receta</option>
                        @foreach($recipes as $recipe)
                            <option value="{{ $recipe->id }}" @selected(old('recipe_id') == $recipe->id)>
                                {{ $recipe->product->name }} — rendimiento {{ $recipe->yield_quantity }} {{ $recipe->product->unit_label }}
                            </option>
                        @endforeach
                    </select>
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
                <button type="submit" class="btn btn-primary">Registrar producción</button>
                <a href="{{ route('productions.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        @endif
    </div></div>
@stop
