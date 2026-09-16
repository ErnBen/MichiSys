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
    </div>

    <div style="margin-top:10px;">
        <strong>Cliente:</strong> {{ $sale->client?->name ?? 'Consumidor Final' }}<br>
        <strong>Cajero:</strong> {{ $sale->user?->name ?? 'N/A' }}
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
                <td class="right">{{ $item->quantity }}</td>
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
                <td>{{ $item->combo?->name ?? 'Combo eliminado' }}</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ number_format($item->price, 2) }}</td>
                <td class="right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 10px; width: 300px; float: right;">
        <table>
            <tr><td>Subtotal</td><td class="right">{{ number_format($sale->subtotal, 2) }}</td></tr>
            <tr><td>Descuento</td><td class="right">{{ number_format($sale->discount, 2) }}</td></tr>
            <tr><td>IVA</td><td class="right">{{ number_format($sale->tax, 2) }}</td></tr>
            <tr><th>Total</th><th class="right">{{ number_format($sale->total, 2) }}</th></tr>
        </table>
    </div>

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
