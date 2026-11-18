@extends('adminlte::page')
@section('title', 'Editar Combo')
@section('content_header')
    <h1>Editar combo</h1>
    <p class="page-subtitle mb-0">Actualiza los datos, el precio y los productos incluidos.</p>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('combos.update', $combo) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('combos.form')
                <div class="form-actions combo-form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2" aria-hidden="true"></i>Guardar cambios</button>
                    <a href="{{ route('combos.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop
