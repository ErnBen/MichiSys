@extends('adminlte::page')
@section('title', 'Ver Proveedor')
@section('content_header')
    <h1>Ver Proveedor</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $provider->name }}</dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $provider->email }}</dd>
                <dt class="col-sm-3">Teléfono</dt>
                <dd class="col-sm-9">{{ $provider->phone }}</dd>
                <dt class="col-sm-3">Dirección</dt>
                <dd class="col-sm-9">{{ $provider->address }}</dd>
            </dl>
            <a href="{{ route('providers.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
@stop
