<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante Venta #{{ $sale->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .header { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>MichiSys</h2>
        <div>Comprobante interno de venta</div>
        <div>Venta #{{ $sale->id }} — {{ $sale->created_at->format('d/m/Y H:i') }}</div>
        @if($sale->cancelled_at)
            <h2>ANULADA</h2>
            <div>{{ $sale->cancelled_at->format('d/m/Y H:i') }} — {{ $sale->cancellation_reason }}</div>
        @endif
    </div>

    <div style="margin-top:10px;">
        <strong>Cliente:</strong> {{ $sale->client?->name ?? 'Consumidor Final' }}<br>
        <strong>Cajero:</strong> {{ $sale->user?->name ?? 'N/A' }}<br>
        <strong>Método de pago:</strong> {{ $sale->payment_method_label }}<br>
        @if($sale->payment_reference)
            <strong>Referencia:</strong> {{ $sale->payment_reference }}<br>
        @endif
        @if($sale->payment_entity)
            <strong>Banco / plataforma:</strong> {{ $sale->payment_entity }}<br>
        @endif
        @if($sale->payment_paid_at)
            <strong>Fecha de pago:</strong> {{ $sale->payment_paid_at->format('d/m/Y H:i') }}<br>
        @endif
        @if($sale->cash_received !== null)
            <strong>Dinero recibido:</strong> Gs. {{ number_format($sale->cash_received, 0, ',', '.') }}<br>
            <strong>Vuelto:</strong> Gs. {{ number_format($sale->change_due, 0, ',', '.') }}<br>
        @endif
    </div>

    <h4>Productos</h4>
    <table>
        <thead>
            <tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            @foreach($sale->products as $item)
            <tr>
                <td>{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                <td class="right">{{ $item->quantity }} {{ $item->product?->unit_label ?? '' }}</td>
                <td class="right">{{ number_format($item->price, 2) }}</td>
                <td class="right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4>Combos</h4>
    <table>
        <thead>
            <tr><th>Combo</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
            @foreach($sale->combos as $item)
            <tr>
                <td>
                    {{ $item->components_snapshot['combo_name'] ?? $item->combo?->name ?? 'Combo eliminado' }}
                    @if($item->components_snapshot)
                        <br><small>Incluye:
                            @foreach($item->components_snapshot['components'] ?? $item->components_snapshot as $component)
                                {{ (int) $component['quantity'] * $item->quantity }} {{ $component['unit_label'] }} {{ $component['name'] }}{{ $loop->last ? '' : ' ·' }}
                            @endforeach
                        </small>
                    @endif
                </td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ number_format($item->price, 2) }}</td>
                <td class="right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 10px; width: 300px; float: right;">
        <table>
            <tr><td>Subtotal</td><td class="right">Gs. {{ number_format($sale->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td>Descuento</td><td class="right">Gs. {{ number_format($sale->discount, 0, ',', '.') }}</td></tr>
            @if((float) $sale->tax > 0)
                <tr><td>Impuesto registrado en venta histórica</td><td class="right">Gs. {{ number_format($sale->tax, 0, ',', '.') }}</td></tr>
            @endif
            <tr><th>Total (precio final)</th><th class="right">Gs. {{ number_format($sale->total, 0, ',', '.') }}</th></tr>
        </table>
    </div>
    @if($sale->payment_notes)
        <p><strong>Observaciones de pago:</strong> {{ $sale->payment_notes }}</p>
    @endif
    @if($sale->receivable)
        <p><strong>Saldo pendiente:</strong> Gs. {{ number_format($sale->receivable->balance, 0, ',', '.') }}</p>
    @endif

    <div style="clear: both; margin-top: 40px; text-align: center;">
        <small>Este comprobante es interno y no constituye factura electrónica.</small>
    </div>
</div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function(){
                try {
                    window.print();
                } catch (e) {
                    console.warn('Impresión automática falló:', e);
                }
            }, 300);
        });
        window.onafterprint = function() {
            try { window.close(); } catch(e) {}
        };
    </script>

</body>
</html>
