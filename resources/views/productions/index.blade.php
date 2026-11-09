@extends('adminlte::page')
@section('title', 'Producción')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Producción</h1>
        <a href="{{ route('productions.create') }}" class="btn btn-primary">Registrar producción</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Lote</th><th>Costo total</th><th>Responsable</th><th></th></tr></thead>
                <tbody>
                    @forelse($productions as $production)
                        <tr>
                            <td>{{ $production->produced_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $production->product->name }}</td>
                            <td>{{ $production->quantity }} {{ $production->product->unit_label }}</td>
                            <td>{{ $production->lot_number ?: '—' }}</td>
                            <td>{{ number_format($production->total_cost, 2) }}</td>
                            <td>{{ $production->user?->name ?? '—' }}</td>
                            <td><a href="{{ route('productions.show', $production) }}" class="btn btn-sm btn-info">Detalle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Aún no hay producciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $productions->links() }}</div>
    </div>
@stop
