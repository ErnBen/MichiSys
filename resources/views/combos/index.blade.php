@extends('adminlte::page')
@section('title', 'Combos')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Combos</h1>
            <p class="page-subtitle mb-0">Paquetes de productos listos para ofrecer a tus clientes.</p>
        </div>
        <a href="{{ route('combos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-2" aria-hidden="true"></i>Nuevo combo
        </a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('combos.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar combos..." aria-label="Buscar combos">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit"><i class="fas fa-search mr-2" aria-hidden="true"></i>Buscar</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table-mobile-cards table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Productos</th>
                        <th>Precio</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($combos as $combo)
                        <tr>
                            <td>{{ $combo->name }}</td>
                            <td>
                                <div class="combo-product-list">
                                    @forelse($combo->products as $product)
                                        <span class="combo-product-chip">
                                            <span>{{ $product->name }}</span>
                                            <strong>× {{ $product->pivot->quantity }}</strong>
                                        </span>
                                    @empty
                                        <span class="combo-empty-label"><i class="fas fa-exclamation-circle mr-1" aria-hidden="true"></i>Sin productos</span>
                                        <small class="combo-empty-help">Agrega al menos un producto para poder activarlo.</small>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ number_format($combo->price, 2) }}</td>
                            <td>{!! $combo->active ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                            <td class="combo-actions-cell">
                                <div class="combo-actions">
                                    <a href="{{ route('combos.edit', $combo) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-pen" aria-hidden="true"></i><span>Editar</span>
                                    </a>
                                    <a href="{{ route('combos.show', $combo) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye" aria-hidden="true"></i><span>Ver</span>
                                    </a>
                                    <form action="{{ route('combos.toggle', $combo) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button
                                            class="btn btn-sm {{ $combo->active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            @disabled(! $combo->active && $combo->products->isEmpty())
                                            title="{{ ! $combo->active && $combo->products->isEmpty() ? 'Edítalo y agrega productos antes de activarlo' : '' }}"
                                        ><i class="fas {{ $combo->active ? 'fa-pause' : 'fa-check' }}" aria-hidden="true"></i><span>{{ $combo->active ? 'Desactivar' : 'Activar' }}</span></button>
                                    </form>
                                    @if($combo->sales_count > 0)
                                        <button class="btn btn-sm btn-outline-danger" disabled aria-label="Eliminar ({{ $combo->sales_count }} venta(s))" title="El combo tiene ventas asociadas y no se puede borrar">
                                            <i class="fas fa-lock" aria-hidden="true"></i><span>Eliminar</span>
                                        </button>
                                    @else
                                        <form action="{{ route('combos.destroy', $combo) }}" method="POST" onsubmit="return confirm('¿Eliminar combo? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i><span>Eliminar</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @if($combo->sales_count > 0)
                                    <small class="combo-actions-note"><i class="fas fa-info-circle mr-1" aria-hidden="true"></i>{{ $combo->sales_count }} venta(s). Se conserva para no alterar las ventas históricas.</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No hay combos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $combos->links() }}</div>
    </div>
@stop
