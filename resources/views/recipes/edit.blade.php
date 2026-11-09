@extends('adminlte::page')
@section('title', 'Editar receta')
@section('content_header')
    <h1>Editar receta</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card"><div class="card-body">
        <form action="{{ route('recipes.update', $recipe) }}" method="POST">
            @csrf
            @method('PUT')
            @include('recipes.form')
            <button type="submit" class="btn btn-primary mt-3">Actualizar receta</button>
            <a href="{{ route('recipes.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    </div></div>
@stop
