@extends('adminlte::page')
@section('title', 'Inventario')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Inventario</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#inventoryModal">Registrar movimiento</button>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Stock actual</h3></div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Producto</th><th>Categoría</th><th>Stock</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                                    <td>{!! $product->stock <= 5 ? '<span class="badge badge-danger">' . $product->stock . '</span>' : $product->stock !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">{{ $products->links() }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Historial de movimientos</h3></div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Usuario</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                                <tr>
                                    <td>{{ $movement->product?->name ?? 'Eliminado' }}</td>
                                    <td>{{ strtoupper($movement->type) }}</td>
                                    <td>{{ $movement->quantity }}</td>
                                    <td>{{ $movement->user?->name ?? 'N/A' }}</td>
                                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix">{{ $movements->links() }}</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="inventoryModal" tabindex="-1" role="dialog" aria-labelledby="inventoryModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('inventory.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="inventoryModalLabel">Registrar movimiento</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Producto</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">Seleccionar producto</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} (Stock actual: {{ $product->stock }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cantidad</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="type" class="form-control" required>
                                <option value="in">Entrada</option>
                                <option value="out">Salida</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nota</label>
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
