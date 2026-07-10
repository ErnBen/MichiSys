@extends('adminlte::page')
@section('title', 'Ver Categoría')
@section('content_header')
    <h1>Ver Categoría</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $category->name }}</dd>
                <dt class="col-sm-3">Slug</dt>
                <dd class="col-sm-9">{{ $category->slug }}</dd>
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $category->description }}</dd>
                <dt class="col-sm-3">Activo</dt>
                <dd class="col-sm-9">{{ $category->active ? 'Sí' : 'No' }}</dd>
            </dl>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>
@stop
