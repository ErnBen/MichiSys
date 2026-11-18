@extends('adminlte::page')
@section('title', 'Añadir nuevo proveedor')
@section('content_header')
    <h1>Añadir nuevo proveedor</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('providers.store') }}" method="POST">
                @csrf
                @include('providers.form')
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('providers.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop
