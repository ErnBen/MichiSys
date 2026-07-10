@extends('adminlte::page')
@section('title', 'Registrar Venta')
@section('content_header')
    <h1>Registrar Venta</h1>
@stop
@section('content')
    @include('shared.alerts')
    <form action="{{ route('sales.store') }}" method="POST">
        @csrf
        <div class="card">
            <div class="card-header"><h3 class="card-title">Cliente</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Cliente</label>
                    <select name="client_id" class="form-control">
                        <option value="">Consumidor Final</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @if(old('client_id') == $client->id) selected @endif>{{ $client->name }} ({{ $client->phone }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Productos</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @if(in_array($product->id, old('product_ids', []))) checked @endif></td>
                                <td>{{ $product->name }}</td>
                                <td>{{ number_format($product->price, 2) }}</td>
                                <td>{{ $product->stock }}</td>
                                <td><input type="number" name="product_quantities[{{ $product->id }}]" value="{{ old('product_quantities.' . $product->id, 1) }}" min="1" class="form-control"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Combos</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Combo</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($combos as $combo)
                            <tr>
                                <td><input type="checkbox" name="combo_ids[]" value="{{ $combo->id }}" @if(in_array($combo->id, old('combo_ids', []))) checked @endif></td>
                                <td>{{ $combo->name }}</td>
                                <td>{{ number_format($combo->price, 2) }}</td>
                                <td><input type="number" name="combo_quantities[{{ $combo->id }}]" value="{{ old('combo_quantities.' . $combo->id, 1) }}" min="1" class="form-control"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label>Descuento</label>
                    <input type="number" step="0.01" name="discount" value="{{ old('discount', 0) }}" class="form-control">
                </div>
                <button type="submit" class="btn btn-success">Registrar Venta</button>
                <a href="{{ route('sales.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </form>
@stop
