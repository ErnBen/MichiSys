@extends('adminlte::page')
@section('title', 'Detalle de producción')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Producción #{{ $production->id }}</h1>
        <a href="{{ route('productions.index') }}" class="btn btn-secondary">Volver a producción</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong>Producto:</strong> {{ $production->product->name }}</div>
                <div class="col-md-3"><strong>Cantidad:</strong> {{ $production->quantity }} {{ $production->product->unit_label }}</div>
                <div class="col-md-3"><strong>Lote:</strong> {{ $production->lot_number ?: '—' }}</div>
                <div class="col-md-3"><strong>Vencimiento:</strong> {{ $production->expires_at?->format('d/m/Y') ?? '—' }}</div>
                <div class="col-md-3"><strong>Fecha:</strong> {{ $production->produced_at->format('d/m/Y H:i') }}</div>
                <div class="col-md-3"><strong>Responsable:</strong> {{ $production->user?->name ?? '—' }}</div>
                <div class="col-md-3"><strong>Costo total:</strong> {{ number_format($production->total_cost, 2) }}</div>
                <div class="col-md-3"><strong>Costo por unidad:</strong> {{ number_format($production->total_cost / max($production->quantity, 1), 4) }}</div>
            </div>
            <h5>Insumos consumidos</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Insumo</th><th>Cantidad</th><th>Unidad</th><th>Costo por unidad base</th><th>Costo total</th></tr></thead>
                    <tbody>
                        @foreach($production->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->unit }}</td>
                                <td>{{ number_format($item->base_unit_cost, 6) }}</td>
                                <td>{{ number_format($item->total_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="4" class="text-right">Total</th><th>{{ number_format($production->total_cost, 2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
@stop
