<h1>Reporte de Productos</h1>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Activo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $product)
        <tr>
            <td>{{ $product->id }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
            <td>{{ number_format($product->price, 2) }}</td>
            <td>{{ $product->stock }}</td>
            <td>{{ $product->active ? 'Sí' : 'No' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
