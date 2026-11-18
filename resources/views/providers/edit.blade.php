@extends('adminlte::page')
@section('title', 'Editar Proveedor')
@section('content_header')
    <h1>Editar Proveedor</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('providers.update', $provider) }}" method="POST">
                @csrf
                @method('PUT')
                @include('providers.form')
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                    <a href="{{ route('providers.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop
