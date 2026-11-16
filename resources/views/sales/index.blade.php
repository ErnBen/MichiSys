@extends('adminlte::page')
@section('title', 'Ventas')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Ventas</h1>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">Nueva Venta</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ route('sales.index') }}" class="form-inline">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Buscar ventas...">
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
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Usuario</th>
                        <th>Pago</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>{{ $sale->id }}</td>
                            <td>{{ $sale->client?->name ?? 'N/A' }}</td>
                            <td>{{ $sale->user?->name ?? 'N/A' }}</td>
                            <td>{{ $sale->payment_method_label }}</td>
                            <td>{{ number_format($sale->total, 2) }}</td>
                            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($sale->cancelled_at)
                                    <span class="badge badge-danger">Anulada</span>
                                @endif
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-info">Ver</a>
                                @unless($sale->cancelled_at)
                                    <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="const reason = prompt('Indique el motivo de la anulación (mínimo 5 caracteres):'); if (!reason || reason.trim().length &lt; 5) return false; this.elements.cancellation_reason.value = reason.trim(); return confirm('¿Confirmar la anulación de esta venta?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="cancellation_reason">
                                        <button class="btn btn-sm btn-danger">Anular</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No hay ventas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $sales->links() }}</div>
    </div>
@stop
