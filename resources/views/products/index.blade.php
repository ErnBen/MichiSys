@extends('adminlte::page')
@section('title', 'Inventario')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Inventario</h1>
        <a href="{{ route('products.create') }}" class="btn btn-primary">Nuevo producto</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('products.index') }}" class="row">
                <div class="form-group col-12 col-md-4">
                    <label for="search">Nombre, código o descripción</label>
                    <input id="search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar en inventario">
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="category_id">Categoría</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">Todas</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="provider_id">Proveedor</label>
                    <select id="provider_id" name="provider_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($providers as $provider)
                            <option value="{{ $provider->id }}" @selected(($filters['provider_id'] ?? '') == $provider->id)>{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="status">Estado</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activo</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivo</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="stock">Disponibilidad</label>
                    <select id="stock" name="stock" class="form-control">
                        <option value="">Cualquier stock</option>
                        <option value="low" @selected(($filters['stock'] ?? '') === 'low')>Stock bajo</option>
                        <option value="available" @selected(($filters['stock'] ?? '') === 'available')>Disponible</option>
                        <option value="out" @selected(($filters['stock'] ?? '') === 'out')>Sin stock</option>
                    </select>
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="expires">Vencimiento</label>
                    <select id="expires" name="expires" class="form-control">
                        <option value="">Cualquiera</option>
                        <option value="soon" @selected(($filters['expires'] ?? '') === 'soon')>Próximo a vencer</option>
                        <option value="expired" @selected(($filters['expires'] ?? '') === 'expired')>Vencido</option>
                    </select>
                </div>
                <div class="form-group col-12 align-self-end">
                    <button class="btn btn-primary" type="submit">Buscar y filtrar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Limpiar</a>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table-mobile-cards table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
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
                                                        <td><div class="product-identity">
                                @if($product->image)<img src="{{ asset('storage/'.$product->image) }}" alt="" loading="lazy">@endif
                                <div><strong>{{ $product->name }}</strong><small class="text-muted">{{ $product->sku ?: 'Sin código' }}</small></div>
                            </div></td>
                            <td>{{ $product->kind === 'ingredient' ? 'Insumo' : 'Vendible' }} ({{ $product->unit_label }})</td>
                            <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                            <td>{{ number_format($product->price, 2) }}</td>
                                                        <td><span class="badge badge-{{ $product->stock <= $product->stock_minimo ? 'warning' : 'success' }}">{{ $product->stock }} {{ $product->unit_label }}</span>@if($product->stock <= $product->stock_minimo)<small class="d-block text-warning">Reponer stock</small>@endif</td>
                            <td>{!! $product->active ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td class="table-row-actions-cell">
                                <div class="table-row-actions">
                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-pen" aria-hidden="true"></i><span>Editar</span>
                                    </a>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span>Ver</span>
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('¿Eliminar producto?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fas fa-trash-alt" aria-hidden="true"></i><span>Eliminar</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No hay productos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $products->links() }}</div>
    </div>
@stop
