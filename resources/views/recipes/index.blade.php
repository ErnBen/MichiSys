@extends('adminlte::page')
@section('title', 'Recetas')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Recetas</h1>
        <a href="{{ route('recipes.create') }}" class="btn btn-primary">Crear receta</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Producto final</th><th>Rendimiento</th><th>Insumos</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse($recipes as $recipe)
                        <tr>
                            <td>{{ $recipe->product->name }}</td>
                            <td>{{ $recipe->yield_quantity }} {{ $recipe->product->unit_label }}</td>
                            <td>{{ $recipe->items_count }}</td>
                            <td>{{ $recipe->active ? 'Activa' : 'Inactiva' }}</td>
                            <td><a href="{{ route('recipes.edit', $recipe) }}" class="btn btn-sm btn-info">Editar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Aún no hay recetas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $recipes->links() }}</div>
    </div>
@stop
