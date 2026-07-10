@extends('adminlte::page')
@section('title', 'Editar Cliente')
@section('content_header')
    <h1>Editar Cliente</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('clients.update', $client) }}" method="POST">
                @csrf
                @method('PUT')
                @include('clients.form')
                <button type="submit" class="btn btn-primary mt-3">Actualizar</button>
                <a href="{{ route('clients.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
