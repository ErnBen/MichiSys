@extends('adminlte::page')
@section('title', 'Proveedores')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Proveedores</h1>
        <a href="{{ route('providers.create') }}" class="btn btn-primary">Añadir nuevo proveedor</a>
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
            <table class="table-mobile-cards table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Productos</th>
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
                                @forelse($provider->products as $product)
                                    <span class="badge badge-light">{{ $product->name }}</span>
                                @empty
                                    <span class="text-muted">Sin productos</span>
                                @endforelse
                            </td>
                            <td class="table-row-actions-cell">
                                <div class="table-row-actions">
                                    <a href="{{ route('providers.edit', $provider) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-pen" aria-hidden="true"></i><span>Editar</span>
                                    </a>
                                    <a href="{{ route('providers.show', $provider) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span>Ver</span>
                                    </a>
                                    <form action="{{ route('providers.destroy', $provider) }}" method="POST" onsubmit="return confirm('¿Eliminar proveedor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i><span>Eliminar</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No hay proveedores registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $providers->links() }}</div>
    </div>
@stop
