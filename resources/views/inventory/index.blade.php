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
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="GET" action="{{ route('inventory.index') }}" class="card card-body mb-3">
        <div class="row">
            <div class="form-group col-12 col-md-3">
                <label for="inventory-search">Nombre o código</label>
                <input id="inventory-search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Producto o SKU">
            </div>
            <div class="form-group col-6 col-md-3">
                <label for="inventory-category">Categoría</label>
                <select id="inventory-category" name="category_id" class="form-control">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-6 col-md-2">
                <label for="inventory-stock">Disponibilidad</label>
                <select id="inventory-stock" name="stock" class="form-control">
                    <option value="">Cualquier stock</option>
                    <option value="low" @selected(($filters['stock'] ?? '') === 'low')>Stock bajo</option>
                    <option value="available" @selected(($filters['stock'] ?? '') === 'available')>Disponible</option>
                    <option value="out" @selected(($filters['stock'] ?? '') === 'out')>Sin stock</option>
                </select>
            </div>
            <div class="form-group col-6 col-md-2">
                <label for="inventory-expiry">Vencimiento</label>
                <select id="inventory-expiry" name="expires" class="form-control">
                    <option value="">Cualquiera</option>
                    <option value="soon" @selected(($filters['expires'] ?? '') === 'soon')>Próximo a vencer</option>
                    <option value="expired" @selected(($filters['expires'] ?? '') === 'expired')>Vencido</option>
                </select>
            </div>
            <div class="form-group col-12 col-md-2 align-self-end">
                <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
            </div>
        </div>
    </form>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Stock actual</h3></div>
                <div class="card-body p-0 table-responsive">
                    <table class="table-mobile-cards table table-hover">
                        <thead>
                            <tr><th>Producto</th><th>Categoría</th><th>Stock</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                                    <td>{!! $product->stock <= 5 ? '<span class="badge badge-danger">' . $product->stock . '</span>' : $product->stock !!} {{ $product->unit_label }}</td>
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
                <div class="card-body p-0 table-responsive">
                    <table class="table-mobile-cards table table-hover">
                        <thead>
                            <tr><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Usuario</th><th>Fecha</th></tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                                <tr>
                                    <td>{{ $movement->product?->name ?? 'Eliminado' }}</td>
                                    <td>{{ ['in' => 'ENTRADA', 'out' => 'SALIDA', 'waste' => 'MERMA'][$movement->type] ?? strtoupper($movement->type) }}</td>
                                    <td>{{ $movement->quantity }} {{ $movement->product?->unit_label ?? '' }}</td>
                                    <td>{{ $movement->user?->name ?? 'N/A' }}</td>
                                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer clearfix inventory-movements-pagination">{{ $movements->links() }}</div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Lotes y vencimientos</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table-mobile-cards table table-hover">
                <thead>
                    <tr><th>Producto</th><th>Lote</th><th>Vencimiento</th><th>Disponible</th><th>Origen</th></tr>
                </thead>
                <tbody>
                    @forelse($lots as $lot)
                        <tr>
                            <td>{{ $lot->product?->name ?? 'Producto eliminado' }}</td>
                            <td>{{ $lot->lot_number ?: 'Sin número' }}</td>
                            <td>
                                @if($lot->expires_at)
                                    {{ $lot->expires_at->format('d/m/Y') }}
                                    @if($lot->expires_at->lt(today()))
                                        <span class="badge badge-danger">Vencido</span>
                                    @elseif($lot->expires_at->lte(today()->addDays(config('michisys.expiration_warning_days', 30))))
                                        <span class="badge badge-warning">Próximo a vencer</span>
                                    @endif
                                @else
                                    <span class="text-muted">Sin fecha</span>
                                @endif
                            </td>
                            <td>{{ $lot->available_quantity }} {{ $lot->product?->unit_label ?? '' }}</td>
                            <td>{{ ucfirst($lot->source) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No hay lotes con existencias.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">{{ $lots->links() }}</div>
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
                            <div class="product-selector" data-product-selector data-mode="single" data-search-url="{{ route('products.search') }}">
                                <input type="search" class="form-control mb-2" data-product-query placeholder="Buscar por nombre o código" autocomplete="off">
                                <div class="small text-muted mb-2" data-product-status role="status" aria-live="polite">Escribe al menos 2 caracteres. Se muestran hasta 20 resultados.</div>
                                <div class="list-group mb-2" data-product-results></div>
                                <input type="hidden" name="product_id" data-product-selected-id>
                                <div class="font-weight-bold" data-product-selected></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Cantidad (unidad base)</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="type" class="form-control" required>
                                <option value="in">Entrada</option>
                                <option value="out">Salida</option>
                                <option value="waste">Merma</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nota</label>
                            <textarea name="note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Número de lote (opcional)</label>
                            <input type="text" name="lot_number" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label>Vencimiento (opcional, para entradas)</label>
                            <input type="date" name="expires_at" class="form-control">
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
    @include('shared.product-selector-script')
@stop
