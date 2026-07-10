@extends('adminlte::page')
@section('title', 'Ver Producto')
@section('content_header')
    <h1>Ver Producto</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $product->name }}</dd>
                <dt class="col-sm-3">Categoría</dt>
                <dd class="col-sm-9">{{ $product->category?->name ?? 'Sin categoría' }}</dd>
                <dt class="col-sm-3">Precio</dt>
                <dd class="col-sm-9">{{ number_format($product->price, 2) }}</dd>
                <dt class="col-sm-3">Costo</dt>
                <dd class="col-sm-9">{{ number_format($product->cost, 2) }}</dd>
                <dt class="col-sm-3">Stock</dt>
                <dd class="col-sm-9">{{ $product->stock }}</dd>
                <dt class="col-sm-3">Activo</dt>
                <dd class="col-sm-9">{{ $product->active ? 'Sí' : 'No' }}</dd>
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $product->description }}</dd>
            </dl>
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="img-fluid" style="max-width: 300px;">
            @endif
            <div class="mt-3">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Volver</a>
            </div>
        </div>
    </div>
@stop
