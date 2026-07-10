@extends('adminlte::page')
@section('title', 'Crear Proveedor')
@section('content_header')
    <h1>Crear Proveedor</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('providers.store') }}" method="POST">
                @csrf
                @include('providers.form')
                <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                <a href="{{ route('providers.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
