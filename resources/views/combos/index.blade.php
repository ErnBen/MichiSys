@extends('adminlte::page')
@section('title', 'Combos')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Combos</h1>
        <a href="{{ route('combos.create') }}" class="btn btn-primary">Nuevo Combo</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('combos.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar combos...">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Productos</th>
                        <th>Precio</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($combos as $combo)
                        <tr>
                            <td>{{ $combo->name }}</td>
                            <td>
                                @forelse($combo->products as $product)
                                    <div>{{ $product->name }} × {{ $product->pivot->quantity }}</div>
                                @empty
                                    <span class="text-warning">Sin productos</span>
                                    <div><small class="text-muted">Agrega al menos un producto para poder activarlo.</small></div>
                                @endforelse
                            </td>
                            <td>{{ number_format($combo->price, 2) }}</td>
                            <td>{!! $combo->active ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td>
                                <a href="{{ route('combos.edit', $combo) }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="{{ route('combos.show', $combo) }}" class="btn btn-sm btn-info">Ver</a>
                                <form action="{{ route('combos.toggle', $combo) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button
                                        class="btn btn-sm {{ $combo->active ? 'btn-secondary' : 'btn-success' }}"
                                        @disabled(! $combo->active && $combo->products->isEmpty())
                                        title="{{ ! $combo->active && $combo->products->isEmpty() ? 'Edítalo y agrega productos antes de activarlo' : '' }}"
                                    >{{ $combo->active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                @if($combo->sales_count > 0)
                                    <button class="btn btn-sm btn-danger" disabled title="El combo tiene ventas asociadas y no se puede borrar">
                                        Eliminar ({{ $combo->sales_count }} venta(s))
                                    </button>
                                    <div><small class="text-muted">Se conserva para no alterar las ventas históricas.</small></div>
                                @else
                                    <form action="{{ route('combos.destroy', $combo) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar combo? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No hay combos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $combos->links() }}</div>
    </div>
@stop
