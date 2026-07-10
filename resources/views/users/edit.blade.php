@extends('adminlte::page')
@section('title', 'Editar Usuario')
@section('content_header')
    <h1>Editar Usuario</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                @include('users.form')
                <button type="submit" class="btn btn-primary mt-3">Actualizar</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
