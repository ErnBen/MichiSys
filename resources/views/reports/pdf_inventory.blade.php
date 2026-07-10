<h1>Reporte de Inventario</h1>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Producto</th>
            <th>Stock</th>
            <th>Último movimiento</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        <tr>
            <td>{{ $product->id }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->stock }}</td>
            <td>{{ $product->updated_at->format('d/m/Y H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
