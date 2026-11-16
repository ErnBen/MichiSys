@extends('adminlte::page')
@section('title', 'Datos del negocio')
@section('content_header')
    <h1>Datos de {{ $business->name }}</h1>
@stop
@section('content')
    @include('shared.alerts')
    <form method="POST" action="{{ route('business.settings.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="form-group"><label for="name">Nombre comercial</label><input id="name" name="name" class="form-control" value="{{ old('name', $business->name) }}" required></div>
                <div class="form-group"><label for="owner_name">Responsable</label><input id="owner_name" name="owner_name" class="form-control" value="{{ old('owner_name', $business->owner_name) }}" required></div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="email">Correo</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $business->email) }}"></div>
                    <div class="form-group col-md-6"><label for="phone">Teléfono</label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $business->phone) }}"></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label for="address">Dirección</label><input id="address" name="address" class="form-control" value="{{ old('address', $business->address) }}"></div>
                    <div class="form-group col-md-6"><label for="ruc">RUC (si corresponde)</label><input id="ruc" name="ruc" class="form-control" value="{{ old('ruc', $business->ruc) }}"></div>
                </div>
                <div class="form-group"><label for="description">Descripción</label><textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $business->description) }}</textarea></div>
                <div class="form-group"><label for="logo">Logotipo</label><input id="logo" type="file" name="logo" class="form-control-file" accept="image/*">@if($business->logo)<small class="form-text"><a href="{{ Storage::disk('public')->url($business->logo) }}" target="_blank" rel="noopener">Ver logotipo actual</a></small>@endif</div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Guardar datos</button></div>
        </div>
    </form>
@stop
