@extends('adminlte::page')
@section('title', 'Administración de plataforma')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Negocios de MichiSys</h1>
        <a href="{{ route('superadmin.businesses.create') }}" class="btn btn-primary">Crear negocio y dueño</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    @if(session('managed_business_id'))
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            Está seleccionado un negocio para administración operativa.
            <a class="btn btn-sm btn-info" href="{{ route('products.index') }}">Abrir sus módulos</a>
        </div>
    @endif
    <div class="row">
        <div class="col-md-4"><div class="small-box bg-info"><div class="inner"><h3>{{ $statistics['businesses'] }}</h3><p>Negocios registrados</p></div></div></div>
        <div class="col-md-4"><div class="small-box bg-success"><div class="inner"><h3>{{ $statistics['active'] }}</h3><p>Negocios activos</p></div></div></div>
        <div class="col-md-4"><div class="small-box bg-primary"><div class="inner"><h3>{{ $statistics['users'] }}</h3><p>Usuarios asociados</p></div></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('superadmin.businesses.index') }}" class="form-inline">
                <input name="search" value="{{ $search }}" class="form-control mr-2" placeholder="Buscar por negocio, dueño o correo" aria-label="Buscar negocios">
                <button class="btn btn-secondary">Buscar</button>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Negocio</th><th>Dueño</th><th>Contacto</th><th>Usuarios</th><th>Estado</th><th>Administración</th></tr></thead>
                <tbody>
                    @forelse($businesses as $business)
                        <tr>
                            <td>{{ $business->name }}</td>
                            <td>{{ $business->owner_name }}</td>
                            <td>{{ $business->email ?? '—' }}<br>{{ $business->phone ?? '' }}</td>
                            <td>{{ $business->users_count }}</td>
                            <td><span class="badge badge-{{ $business->active ? 'success' : 'secondary' }}">{{ $business->active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-nowrap">
                                <form method="POST" action="{{ route('superadmin.businesses.select', $business) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-info">Seleccionar</button>
                                </form>
                                <a href="{{ route('superadmin.businesses.edit', $business) }}" class="btn btn-sm btn-primary">Editar</a>
                                <a href="{{ route('superadmin.businesses.users', $business) }}" class="btn btn-sm btn-secondary">Usuarios</a>
                                <form method="POST" action="{{ route('superadmin.businesses.toggle', $business) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="active" value="{{ $business->active ? 0 : 1 }}">
                                    <button class="btn btn-sm btn-{{ $business->active ? 'warning' : 'success' }}">{{ $business->active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No hay negocios que coincidan con la búsqueda.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $businesses->links() }}</div>
    </div>
@stop
