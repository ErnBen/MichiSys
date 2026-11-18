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
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar receta</button>
                <a href="{{ route('recipes.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div></div>
@stop
