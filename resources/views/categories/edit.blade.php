@extends('adminlte::page')
@section('title', 'Editar Categoría')
@section('content_header')
    <h1>Editar Categoría</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control" rows="4">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="active" class="form-check-input" id="active" {{ $category->active ? 'checked' : '' }}>
                    <label class="form-check-label" for="active">Activo</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Actualizar</button>
                <a href="{{ route('categories.index') }}" class="btn btn-secondary mt-3">Cancelar</a>
            </form>
        </div>
    </div>
@stop
