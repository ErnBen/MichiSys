@extends('adminlte::page')
@section('title', 'Editar Combo')
@section('content_header')
    <h1>Editar Combo</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('combos.update', $combo) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('combos.form')
                <button type="submit" class="btn btn-primary mt-3">Actualizar</button>
                <a href="{{ route('combos.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
