@extends('adminlte::page')
@section('title', 'Crear receta')
@section('content_header')
    <h1>Crear receta</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card"><div class="card-body">
        <form action="{{ route('recipes.store') }}" method="POST">
            @csrf
            @include('recipes.form')
            <button type="submit" class="btn btn-primary mt-3">Guardar receta</button>
            <a href="{{ route('recipes.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    </div></div>
@stop
