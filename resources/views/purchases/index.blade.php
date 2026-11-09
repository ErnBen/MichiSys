@extends('adminlte::page')
@section('title', 'Compras')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Compras</h1>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary">Registrar compra</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Proveedor</th>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th>Registrada por</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->id }}</td>
                            <td>{{ $purchase->provider_name }}</td>
                            <td>{{ $purchase->invoice_number ?: '—' }}</td>
                            <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                            <td>{{ $purchase->user?->name ?? 'N/A' }}</td>
                            <td>{{ number_format($purchase->total, 2) }}</td>
                            <td><a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-info">Ver detalle</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No hay compras registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $purchases->links() }}</div>
    </div>
@stop
