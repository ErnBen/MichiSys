@extends('adminlte::page')
@section('title', 'Crear Combo')
@section('content_header')
    <h1>Crear combo</h1>
    <p class="page-subtitle mb-0">Define el precio y selecciona los productos que incluirá.</p>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('combos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('combos.form')
                <div class="form-actions combo-form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2" aria-hidden="true"></i>Guardar combo</button>
                    <a href="{{ route('combos.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop
