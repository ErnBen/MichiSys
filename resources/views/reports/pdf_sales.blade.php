<h1>Reporte de Ventas</h1>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Usuario</th>
            <th>Subtotal</th>
            <th>Descuento</th>
            <th>IVA</th>
            <th>Total</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
        <tr>
            <td>{{ $sale->id }}</td>
            <td>{{ $sale->client?->name ?? 'Consumidor Final' }}</td>
            <td>{{ $sale->user?->name ?? 'N/A' }}</td>
            <td>{{ number_format($sale->subtotal, 2) }}</td>
            <td>{{ number_format($sale->discount, 2) }}</td>
            <td>{{ number_format($sale->tax, 2) }}</td>
            <td>{{ number_format($sale->total, 2) }}</td>
            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
