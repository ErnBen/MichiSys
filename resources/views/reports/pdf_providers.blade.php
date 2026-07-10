<h1>Reporte de Proveedores</h1>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Dirección</th></tr>
    </thead>
    <tbody>
        @foreach($providers as $provider)
        <tr>
            <td>{{ $provider->id }}</td>
            <td>{{ $provider->name }}</td>
            <td>{{ $provider->email }}</td>
            <td>{{ $provider->phone }}</td>
            <td>{{ $provider->address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
