@extends('adminlte::page')
@section('title', 'Proveedores')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Proveedores</h1>
        <a href="{{ route('providers.create') }}" class="btn btn-primary">Nuevo Proveedor</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('providers.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar proveedores...">
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
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                        <tr>
                            <td>{{ $provider->name }}</td>
                            <td>{{ $provider->email }}</td>
                            <td>{{ $provider->phone }}</td>
                            <td>{{ $provider->address }}</td>
                            <td>
                                <a href="{{ route('providers.edit', $provider) }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="{{ route('providers.show', $provider) }}" class="btn btn-sm btn-info">Ver</a>
                                <form action="{{ route('providers.destroy', $provider) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar proveedor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No hay proveedores registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $providers->links() }}</div>
    </div>
@stop
