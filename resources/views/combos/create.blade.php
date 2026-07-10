@extends('adminlte::page')
@section('title', 'Crear Combo')
@section('content_header')
    <h1>Crear Combo</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('combos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('combos.form')
                <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                <a href="{{ route('combos.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
