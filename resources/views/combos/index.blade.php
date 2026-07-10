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
                        <th>Precio</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($combos as $combo)
                        <tr>
                            <td>{{ $combo->name }}</td>
                            <td>{{ number_format($combo->price, 2) }}</td>
                            <td>{!! $combo->active ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td>
                                <a href="{{ route('combos.edit', $combo) }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="{{ route('combos.show', $combo) }}" class="btn btn-sm btn-info">Ver</a>
                                <form action="{{ route('combos.destroy', $combo) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar combo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No hay combos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $combos->links() }}</div>
    </div>
@stop
