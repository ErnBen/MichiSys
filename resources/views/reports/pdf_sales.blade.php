<h1>Reporte de Ventas</h1>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Usuario</th>
            <th>Método de pago</th>
            <th>Subtotal</th>
            <th>Descuento</th>
            <th>Impuesto histórico</th>
            <th>Total</th>
            <th>Fecha</th>
            <th>Estado / anulación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
        <tr>
            <td>{{ $sale->id }}</td>
            <td>{{ $sale->client?->name ?? 'Consumidor Final' }}</td>
            <td>{{ $sale->user?->name ?? 'N/A' }}</td>
            <td>{{ $sale->payment_method_label }}</td>
            <td>{{ number_format($sale->subtotal, 2) }}</td>
            <td>{{ number_format($sale->discount, 2) }}</td>
            <td>{{ number_format($sale->tax, 2) }}</td>
            <td>{{ number_format($sale->total, 2) }}</td>
            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
            <td>
                @if($sale->cancelled_at)
                    Anulada {{ $sale->cancelled_at->format('d/m/Y H:i') }} por {{ $sale->canceller?->name ?? 'usuario no disponible' }}.
                    Motivo: {{ $sale->cancellation_reason }}
                @else
                    Vigente
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
