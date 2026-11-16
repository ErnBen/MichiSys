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
                @if($sale->cancelled_at)
                    <dt class="col-sm-3 text-danger">Estado</dt>
                    <dd class="col-sm-9 text-danger">Anulada el {{ $sale->cancelled_at->format('d/m/Y H:i') }} por {{ $sale->canceller?->name ?? 'usuario no disponible' }}</dd>
                    <dt class="col-sm-3">Motivo de anulación</dt>
                    <dd class="col-sm-9">{{ $sale->cancellation_reason }}</dd>
                @endif
                <dt class="col-sm-3">Método de pago</dt>
                <dd class="col-sm-9">{{ $sale->payment_method_label }}</dd>
                @if($sale->payment_reference)
                    <dt class="col-sm-3">Referencia</dt>
                    <dd class="col-sm-9">{{ $sale->payment_reference }}</dd>
                @endif
                @if($sale->payment_entity)
                    <dt class="col-sm-3">Banco / plataforma</dt>
                    <dd class="col-sm-9">{{ $sale->payment_entity }}</dd>
                @endif
                @if($sale->payment_paid_at)
                    <dt class="col-sm-3">Fecha de pago</dt>
                    <dd class="col-sm-9">{{ $sale->payment_paid_at->format('d/m/Y H:i') }}</dd>
                @endif
                @if($sale->cash_received !== null)
                    <dt class="col-sm-3">Recibido</dt>
                    <dd class="col-sm-9">Gs. {{ number_format($sale->cash_received, 0, ',', '.') }}</dd>
                    <dt class="col-sm-3">Vuelto</dt>
                    <dd class="col-sm-9">Gs. {{ number_format($sale->change_due, 0, ',', '.') }}</dd>
                @endif
                <dt class="col-sm-3">Subtotal</dt>
                <dd class="col-sm-9">Gs. {{ number_format($sale->subtotal, 0, ',', '.') }}</dd>
                <dt class="col-sm-3">Descuento</dt>
                <dd class="col-sm-9">Gs. {{ number_format($sale->discount, 0, ',', '.') }}</dd>
                @if((float) $sale->tax > 0)
                    <dt class="col-sm-3">Impuesto registrado en venta histórica</dt>
                    <dd class="col-sm-9">Gs. {{ number_format($sale->tax, 0, ',', '.') }}</dd>
                @endif
                <dt class="col-sm-3">Total</dt>
                <dd class="col-sm-9 font-weight-bold">Gs. {{ number_format($sale->total, 0, ',', '.') }}</dd>
                @if($sale->payment_notes)
                    <dt class="col-sm-3">Observaciones de pago</dt>
                    <dd class="col-sm-9">{{ $sale->payment_notes }}</dd>
                @endif
                @if($sale->receivable)
                    <dt class="col-sm-3">Saldo pendiente</dt>
                    <dd class="col-sm-9">Gs. {{ number_format($sale->receivable->balance, 0, ',', '.') }}
                        <a href="{{ route('receivables.show', $sale->receivable) }}">Ver deuda y pagos</a>
                    </dd>
                @endif
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
                            <td>{{ $item->quantity }} {{ $item->product?->unit_label ?? '' }}</td>
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
    <a href="{{ route('sales.receipt', $sale) }}" target="_blank" rel="noopener" class="btn btn-primary">Imprimir comprobante</a>
@stop
