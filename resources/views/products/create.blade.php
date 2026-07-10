@extends('adminlte::page')
@section('title', 'Crear Producto')
@section('content_header')
    <h1>Crear Producto</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('products.form')
                <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                <a href="{{ route('products.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
