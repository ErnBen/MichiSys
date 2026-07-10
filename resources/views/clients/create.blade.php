@extends('adminlte::page')
@section('title', 'Crear Cliente')
@section('content_header')
    <h1>Crear Cliente</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('clients.store') }}" method="POST">
                @csrf
                @include('clients.form')
                <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                <a href="{{ route('clients.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
