@extends('adminlte::page')
@section('title', 'Detalle de compra')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Compra #{{ $purchase->id }}</h1>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Volver a compras</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong>Proveedor:</strong> {{ $purchase->provider_name }}</div>
                <div class="col-md-3"><strong>Factura:</strong> {{ $purchase->invoice_number ?: '—' }}</div>
                <div class="col-md-3"><strong>Fecha:</strong> {{ $purchase->purchase_date->format('d/m/Y') }}</div>
                <div class="col-md-3"><strong>Registrada por:</strong> {{ $purchase->user?->name ?? 'N/A' }}</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Producto</th><th>Lote</th><th>Vencimiento</th><th>Cantidad recibida</th><th>Costo unitario</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->lot_number ?: '—' }}</td>
                                <td>{{ $item->expires_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $item->purchased_quantity ?? $item->quantity }} {{ $item->purchase_unit ?? $item->base_unit ?? 'pieza(s)' }} <small class="text-muted">({{ $item->quantity }} {{ $item->base_unit ?? 'unidad(es)' }})</small></td>
                                <td>{{ number_format($item->unit_cost, 2) }}</td>
                                <td>{{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><th colspan="5" class="text-right">Total</th><th>{{ number_format($purchase->total, 2) }}</th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@stop
