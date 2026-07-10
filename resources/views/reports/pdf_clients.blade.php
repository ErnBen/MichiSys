<h1>Reporte de Clientes</h1>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Dirección</th></tr>
    </thead>
    <tbody>
        @foreach($clients as $client)
        <tr>
            <td>{{ $client->id }}</td>
            <td>{{ $client->name }}</td>
            <td>{{ $client->email }}</td>
            <td>{{ $client->phone }}</td>
            <td>{{ $client->address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
