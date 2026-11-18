@extends('adminlte::page')

@section('title', 'Panel Principal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Panel Principal</h1>
    </div>
@stop

@section('content')
    @include('shared.alerts')

    <section class="dashboard-intro mb-4" aria-label="Resumen del negocio">
        <div>
            <span class="dashboard-intro-label">RESUMEN DEL NEGOCIO</span>
            <h2>{{ $managedBusiness->name ?? 'MichiSys' }}</h2>
            <p>Revisa la actividad y administra las operaciones de tu negocio desde un solo lugar.</p>
        </div>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="fas fa-cash-register mr-2" aria-hidden="true"></i>Registrar venta
        </a>
    </section>

    <div class="row dashboard-stat-grid">
        <div class="col-lg-3 col-6">
            <a href="{{ route('products.index') }}" class="small-box dashboard-stat-card dashboard-stat-info" aria-label="Ver inventario">
                <div class="inner">
                    <h3>{{ $totalProducts }}</h3>
                    <p>Inventario</p>
                </div>
                <div class="icon"><i class="fas fa-box-open" aria-hidden="true"></i></div>
                <span class="small-box-footer">Ver inventario <i class="fas fa-arrow-circle-right" aria-hidden="true"></i></span>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('categories.index') }}" class="small-box dashboard-stat-card dashboard-stat-success" aria-label="Ver categorías">
                <div class="inner">
                    <h3>{{ $totalCategories }}</h3>
                    <p>Categorías</p>
                </div>
                <div class="icon"><i class="fas fa-tags" aria-hidden="true"></i></div>
                <span class="small-box-footer">Ver categorías <i class="fas fa-arrow-circle-right" aria-hidden="true"></i></span>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('combos.index') }}" class="small-box dashboard-stat-card dashboard-stat-warning" aria-label="Ver combos">
                <div class="inner">
                    <h3>{{ $totalCombos }}</h3>
                    <p>Combos</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group" aria-hidden="true"></i></div>
                <span class="small-box-footer">Ver combos <i class="fas fa-arrow-circle-right" aria-hidden="true"></i></span>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('clients.index') }}" class="small-box dashboard-stat-card dashboard-stat-danger" aria-label="Ver clientes">
                <div class="inner">
                    <h3>{{ $totalClients }}</h3>
                    <p>Clientes</p>
                </div>
                <div class="icon"><i class="fas fa-user-friends" aria-hidden="true"></i></div>
                <span class="small-box-footer">Ver clientes <i class="fas fa-arrow-circle-right" aria-hidden="true"></i></span>
            </a>
        </div>
    </div>

    <div class="row dashboard-stat-grid">
        <div class="col-lg-3 col-6">
            <a href="{{ route('sales.index') }}" class="small-box dashboard-stat-card dashboard-stat-primary" aria-label="Ver ventas">
                <div class="inner">
                    <h3>{{ $totalSales }}</h3>
                    <p>Ventas</p>
                </div>
                <div class="icon"><i class="fas fa-cash-register" aria-hidden="true"></i></div>
                <span class="small-box-footer">Ver ventas <i class="fas fa-arrow-circle-right" aria-hidden="true"></i></span>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{ route('inventory.index') }}" class="small-box dashboard-stat-card dashboard-stat-secondary" aria-label="Revisar inventario con bajo stock">
                <div class="inner">
                    <h3>{{ $lowStockProducts }}</h3>
                    <p>Productos bajo stock</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i></div>
                <span class="small-box-footer">Revisar inventario <i class="fas fa-arrow-circle-right" aria-hidden="true"></i></span>
            </a>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Gráfico de ventas</h3></div>
                <div class="card-body">
                    <canvas id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row dashboard-finances">
        <div class="col-12 col-md-6">
            <a href="{{ route('sales.index') }}" class="card metric-link">
                <div class="card-body"><span class="metric-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></span><span class="text-muted d-block">Ventas de hoy</span><strong class="metric-value">Gs. {{ number_format($todaySales, 0, ',', '.') }}</strong><small class="d-block text-muted">Ventas vigentes · IVA incluido</small></div>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a href="{{ route('receivables.index') }}" class="card metric-link">
                <div class="card-body"><span class="metric-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></span><span class="text-muted d-block">Cuentas por cobrar</span><strong class="metric-value">Gs. {{ number_format($pendingBalance, 0, ',', '.') }}</strong><small class="d-block text-muted">Saldo pendiente de clientes · Ver cuentas</small></div>
            </a>
        </div>
    </div>
    <div class="row dashboard-quick-actions">
        <div class="col-lg-3 col-6"><a href="{{ route('products.create') }}" class="btn btn-lg btn-block btn-outline-primary mb-3"><i class="fas fa-plus-circle mr-2" aria-hidden="true"></i>Nuevo producto</a></div>
        <div class="col-lg-3 col-6"><a href="{{ route('combos.create') }}" class="btn btn-lg btn-block btn-outline-primary mb-3"><i class="fas fa-layer-group mr-2" aria-hidden="true"></i>Nuevo combo</a></div>
        <div class="col-lg-3 col-6"><a href="{{ route('clients.index') }}" class="btn btn-lg btn-block btn-outline-primary mb-3"><i class="fas fa-user-friends mr-2" aria-hidden="true"></i>Ver clientes</a></div>
        <div class="col-lg-3 col-6"><a href="{{ route('reports.index') }}" class="btn btn-lg btn-block btn-outline-primary mb-3"><i class="fas fa-chart-line mr-2" aria-hidden="true"></i>Ver reportes</a></div>
    </div>
    
    @if(!empty($lowStockList) && $lowStockList->isNotEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Productos con stock bajo</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table-mobile-cards table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock actual</th>
                                <th>Stock mínimo</th>
                                <th>Recomendación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockList as $p)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td>{{ $p->sellable_stock ?? 0 }} {{ $p->unit_label }}</td>
                                    <td>{{ $p->stock_minimo }}</td>
                                    <td><span class="text-danger">Se recomienda reponer</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($expiringSoonProducts) && $expiringSoonProducts->isNotEmpty())
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Lotes próximos a vencer</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table-mobile-cards table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Lote</th>
                                <th>Disponible</th>
                                <th>Fecha de vencimiento</th>
                                <th>Días restantes</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringSoonProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->lot_number ?: 'Sin número' }}</td>
                                    <td>{{ $product->available_quantity }}</td>
                                    <td>{{ $product->fecha_vencimiento ? \Carbon\Carbon::parse($product->fecha_vencimiento)->format('d/m/Y') : 'Sin fecha' }}</td>
                                    <td>
                                        @if($product->days_remaining === 0)
                                            Hoy
                                        @else
                                            {{ abs($product->days_remaining) }} {{ $product->days_remaining < 0 ? 'días vencidos' : 'días' }}
                                        @endif
                                    </td>
                                    <td><span class="badge badge-{{ $product->expiration_badge }}">{{ $product->expiration_status }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(!empty($expiredProducts) && $expiredProducts->isNotEmpty())
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Lotes vencidos</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table-mobile-cards table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Lote</th>
                                <th>Disponible</th>
                                <th>Fecha de vencimiento</th>
                                <th>Días vencidos</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->lot_number ?: 'Sin número' }}</td>
                                    <td>{{ $product->available_quantity }}</td>
                                    <td>{{ \Carbon\Carbon::parse($product->fecha_vencimiento)->format('d/m/Y') }}</td>
                                    <td>{{ abs($product->days_remaining) }} días</td>
                                    <td><span class="badge badge-danger">Vencido</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
    <section class="card" aria-labelledby="recent-purchases-heading">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <h2 class="card-title" id="recent-purchases-heading">Compras recientes</h2>
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-primary btn-sm">Ver compras</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-mobile-cards mb-0">
                <thead><tr><th>Compra</th><th>Proveedor</th><th>Fecha</th><th>Total</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($recentPurchases as $purchase)
                        <tr><td>#{{ $purchase->id }}</td><td>{{ $purchase->provider_name }}</td><td>{{ $purchase->purchase_date->format('d/m/Y') }}</td><td>Gs. {{ number_format($purchase->total, 0, ',', '.') }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('purchases.show', $purchase) }}">Ver compra</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay compras registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const salesChart = document.getElementById('salesChart').getContext('2d');
        new Chart(salesChart, {
            type: 'line',
            data: {
                labels: @json($salesChartLabels),
                datasets: [{
                    label: 'Ventas',
                    data: @json($salesChartData),
                    backgroundColor: 'rgba(36,85,138,0.14)',
                    borderColor: 'rgba(36,85,138,1)',
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
@stop
