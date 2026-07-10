@extends('adminlte::page')
@section('title', 'Ver Cliente')
@section('content_header')
    <h1>Ver Cliente</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $client->name }}</dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $client->email }}</dd>
                <dt class="col-sm-3">Teléfono</dt>
                <dd class="col-sm-9">{{ $client->phone }}</dd>
                <dt class="col-sm-3">Dirección</dt>
                <dd class="col-sm-9">{{ $client->address }}</dd>
            </dl>
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
@stop
