@extends('adminlte::page')
@section('title', 'Clientes')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Clientes</h1>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">Nuevo Cliente</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('clients.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar clientes...">
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
                    @forelse($clients as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->address }}</td>
                            <td>
                                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-info">Ver</a>
                                <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar cliente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No hay clientes registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $clients->links() }}</div>
    </div>
@stop
