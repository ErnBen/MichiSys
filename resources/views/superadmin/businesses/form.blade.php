@extends('adminlte::page')
@section('title', $business ? 'Editar negocio' : 'Crear negocio')
@section('content_header')
    <h1>{{ $business ? 'Editar negocio' : 'Crear negocio y asignar dueño' }}</h1>
@stop
@section('content')
    @include('shared.alerts')
    <form method="POST" action="{{ $business ? route('superadmin.businesses.update', $business) : route('superadmin.businesses.store') }}" enctype="multipart/form-data">
        @csrf
        @if($business) @method('PUT') @endif
        <div class="card">
            <div class="card-header"><h3 class="card-title">Datos del negocio</h3></div>
            <div class="card-body">
                <div class="form-group"><label for="name">Nombre comercial</label><input id="name" name="name" class="form-control" value="{{ old('name', $business?->name) }}" required></div>
                <div class="form-group"><label for="owner_name">Nombre del dueño responsable</label><input id="owner_name" name="owner_name" class="form-control" value="{{ old('owner_name', $business?->owner_name) }}" required></div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="email">Correo del negocio</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $business?->email) }}"></div>
                    <div class="form-group col-md-6"><label for="phone">Teléfono</label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $business?->phone) }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="address">Dirección</label><input id="address" name="address" class="form-control" value="{{ old('address', $business?->address) }}"></div>
                    <div class="form-group col-md-6"><label for="ruc">RUC (si corresponde)</label><input id="ruc" name="ruc" class="form-control" value="{{ old('ruc', $business?->ruc) }}"></div>
                </div>
                <div class="form-group"><label for="description">Descripción</label><textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $business?->description) }}</textarea></div>
                <div class="form-group"><label for="logo">Logotipo</label><input id="logo" type="file" name="logo" class="form-control-file" accept="image/*">@if($business?->logo)<small class="form-text"><a href="{{ Storage::disk('public')->url($business->logo) }}" target="_blank" rel="noopener">Ver logotipo actual</a></small>@endif</div>
                @if($business)
                    <div class="form-group form-check">
                        <input type="hidden" name="active" value="0">
                        <input id="active" type="checkbox" name="active" value="1" class="form-check-input" {{ old('active', $business->active) ? 'checked' : '' }}>
                        <label for="active" class="form-check-label">Negocio activo</label>
                    </div>
                @endif
            </div>
        </div>
        @unless($business)
            <div class="card">
                <div class="card-header"><h3 class="card-title">Cuenta inicial del dueño</h3></div>
                <div class="card-body">
                    <div class="form-group"><label for="owner_email">Correo de inicio de sesión</label><input id="owner_email" type="email" name="owner_email" class="form-control" value="{{ old('owner_email') }}" autocomplete="off" required></div>
                    <div class="form-group"><label for="owner_password">Contraseña inicial segura</label><input id="owner_password" type="password" name="owner_password" class="form-control" minlength="12" autocomplete="new-password" required><small class="form-text text-muted">Usa al menos 12 caracteres y entrega estas credenciales al dueño por un canal seguro. No se genera ni se muestra una contraseña predeterminada.</small></div>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header"><h3 class="card-title">Cuenta del dueño asignado</h3></div>
                <div class="card-body">
                    <div class="form-group"><label for="owner_email">Correo de inicio de sesión</label><input id="owner_email" type="email" name="owner_email" class="form-control" value="{{ old('owner_email', $owner?->email) }}" autocomplete="off" {{ $owner ? '' : 'required' }}></div>
                    <div class="form-group"><label for="owner_password">Contraseña inicial / nueva</label><input id="owner_password" type="password" name="owner_password" class="form-control" minlength="12" autocomplete="new-password" {{ $owner ? '' : 'required' }}><small class="form-text text-muted">@if($owner)Deja en blanco para conservar la contraseña actual.@elseNo existe una cuenta de dueño asociada; crea una con una contraseña segura de al menos 12 caracteres.@endif</small></div>
                </div>
            </div>
        @endunless
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
@stop
