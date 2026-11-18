@extends('adminlte::page')
@section('title', 'Usuarios del negocio')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Usuarios: {{ $business->name }}</h1>
        <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-secondary">Volver a negocios</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table-mobile-cards table table-hover mb-0">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td><td>{{ $user->email }}</td>
                            <td>{{ ['dueno' => 'Dueño', 'admin' => 'Dueño (compatibilidad)', 'cajero' => 'Cajero'][$user->role] ?? $user->role }}</td>
                            <td>{{ $user->active ? 'Activo' : 'Inactivo' }}</td>
                            <td>
                                <form method="POST" action="{{ route('superadmin.businesses.users.toggle', [$business, $user]) }}">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="active" value="{{ $user->active ? 0 : 1 }}">
                                    <button class="btn btn-sm btn-{{ $user->active ? 'warning' : 'success' }}">{{ $user->active ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Este negocio todavía no tiene usuarios asociados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $users->links() }}</div>
    </div>
@stop
