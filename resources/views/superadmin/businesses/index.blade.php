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
    @if($selectedBusiness)
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            Negocio seleccionado para administración: <strong>{{ $selectedBusiness->name }}</strong>.
            <a class="btn btn-sm btn-info" href="{{ route('products.index') }}">Abrir sus módulos</a>
        </div>
    @endif
    <div class="row">
        <div class="col-6 col-xl-3"><div class="small-box"><div class="inner"><h3>{{ $statistics['businesses'] }}</h3><p>Negocios registrados</p></div></div></div>
        <div class="col-6 col-xl-3"><div class="small-box"><div class="inner"><h3>{{ $statistics['active'] }}</h3><p>Negocios activos</p></div></div></div>
        <div class="col-6 col-xl-3"><div class="small-box"><div class="inner"><h3>{{ $statistics['inactive'] }}</h3><p>Negocios inactivos</p></div></div></div>
        <div class="col-6 col-xl-3"><div class="small-box"><div class="inner"><h3>{{ $statistics['owners'] }}</h3><p>Dueños registrados</p></div></div></div>
        <div class="col-6 col-xl-3"><div class="small-box"><div class="inner"><h3>{{ $statistics['users'] }}</h3><p>Usuarios registrados</p></div></div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('superadmin.businesses.index') }}" class="row align-items-end">
                <div class="form-group col-12 col-md-6 mb-md-0">
                    <label class="sr-only" for="business-search">Buscar negocios</label>
                    <input id="business-search" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar por negocio, dueño o correo" aria-label="Buscar negocios">
                </div>
                <div class="form-group col-8 col-md-3 mb-md-0">
                    <label class="sr-only" for="business-status">Estado</label>
                    <select id="business-status" name="status" class="form-control" aria-label="Filtrar por estado">
                        <option value="">Todos los estados</option>
                        <option value="active" @selected($status === 'active')>Activos</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                    </select>
                </div>
                <div class="col-4 col-md-3">
                    <button class="btn btn-primary btn-block" type="submit">Buscar</button>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table-mobile-cards table table-hover mb-0">
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
