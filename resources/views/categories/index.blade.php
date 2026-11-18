@extends('adminlte::page')
@section('title', 'Categorías')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Categorías</h1>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">Nueva Categoría</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('categories.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar categorías...">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">Buscar</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table-mobile-cards table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ Str::limit($category->description, 80) }}</td>
                            <td>{!! $category->active ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td class="table-row-actions-cell">
                                <div class="table-row-actions">
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-pen" aria-hidden="true"></i><span>Editar</span>
                                    </a>
                                    <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span>Ver</span>
                                    </a>
                                    <form action="{{ route('categories.toggle', $category) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-sm btn-outline-{{ $category->active ? 'warning' : 'success' }}" type="submit">
                                            <i class="fas fa-{{ $category->active ? 'pause' : 'play' }}" aria-hidden="true"></i><span>{{ $category->active ? 'Desactivar' : 'Activar' }}</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('¿Eliminar categoría?');">
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
                        <tr><td colspan="4">No hay categorías registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $categories->links() }}</div>
    </div>
@stop
