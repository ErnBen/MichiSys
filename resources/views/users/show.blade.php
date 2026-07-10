@extends('adminlte::page')
@section('title', 'Ver Usuario')
@section('content_header')
    <h1>Ver Usuario</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $user->name }}</dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>
                <dt class="col-sm-3">Registrado</dt>
                <dd class="col-sm-9">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
@stop
