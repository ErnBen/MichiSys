@extends('adminlte::page')
@section('title', 'Productos')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Productos</h1>
        <a href="{{ route('products.create') }}" class="btn btn-primary">Nuevo Producto</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('products.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar productos...">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                            <td>{{ number_format($product->price, 2) }}</td>
                            <td>{{ $product->stock }}</td>
                            <td>{!! $product->active ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning">Editar</a>
                                <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-info">Ver</a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar producto?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No hay productos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $products->links() }}</div>
    </div>
@stop
