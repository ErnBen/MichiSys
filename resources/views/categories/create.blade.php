@extends('adminlte::page')
@section('title', 'Crear Categoría')
@section('content_header')
    <h1>Crear Categoría</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('categories.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="active" class="form-check-input" id="active" checked>
                    <label class="form-check-label" for="active">Activo</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                <a href="{{ route('categories.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
