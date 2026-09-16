@extends('adminlte::page')
@section('title', 'Detalle de Venta')
@section('content_header')
    <h1>Detalle de Venta #{{ $sale->id }}</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Cliente</dt>
                <dd class="col-sm-9">{{ $sale->client?->name ?? 'Consumidor Final' }}</dd>
                <dt class="col-sm-3">Usuario</dt>
                <dd class="col-sm-9">{{ $sale->user?->name ?? 'N/A' }}</dd>
                <dt class="col-sm-3">Fecha</dt>
                <dd class="col-sm-9">{{ $sale->created_at->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-3">Subtotal</dt>
                <dd class="col-sm-9">{{ number_format($sale->subtotal, 2) }}</dd>
                <dt class="col-sm-3">Descuento</dt>
                <dd class="col-sm-9">{{ number_format($sale->discount, 2) }}</dd>
                <dt class="col-sm-3">IVA</dt>
                <dd class="col-sm-9">{{ number_format($sale->tax, 2) }}</dd>
                <dt class="col-sm-3">Total</dt>
                <dd class="col-sm-9">{{ number_format($sale->total, 2) }}</dd>
            </dl>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Productos</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach($sale->products as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Combos</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover">
                <thead>
                    <tr><th>Combo</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach($sale->combos as $item)
                        <tr>
                            <td>{{ $item->combo?->name ?? 'Combo eliminado' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format($item->price, 2) }}</td>
                            <td>{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <a href="{{ route('sales.index') }}" class="btn btn-secondary">Volver</a>
    <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn btn-primary">Imprimir comprobante</a>
@stop
