@extends('adminlte::page')

@section('title', 'Panel Principal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Panel Principal</h1>
    </div>
@stop

@section('content')
    @include('shared.alerts')

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalProducts }}</h3>
                    <p>Productos</p>
                </div>
                <div class="icon"><i class="fas fa-box-open"></i></div>
                <a href="{{ route('products.index') }}" class="small-box-footer">Ver productos <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalCategories }}</h3>
                    <p>Categorías</p>
                </div>
                <div class="icon"><i class="fas fa-tags"></i></div>
                <a href="{{ route('categories.index') }}" class="small-box-footer">Ver categorías <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalCombos }}</h3>
                    <p>Combos</p>
                </div>
                <div class="icon"><i class="fas fa-layer-group"></i></div>
                <a href="{{ route('combos.index') }}" class="small-box-footer">Ver combos <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $totalClients }}</h3>
                    <p>Clientes</p>
                </div>
                <div class="icon"><i class="fas fa-user-friends"></i></div>
                <a href="{{ route('clients.index') }}" class="small-box-footer">Ver clientes <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $totalSales }}</h3>
                    <p>Ventas</p>
                </div>
                <div class="icon"><i class="fas fa-cash-register"></i></div>
                <a href="{{ route('sales.index') }}" class="small-box-footer">Ver ventas <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $lowStockProducts }}</h3>
                    <p>Productos bajo stock</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                <a href="{{ route('inventory.index') }}" class="small-box-footer">Revisar inventario <i class="fas fa-arrow-circle-right"></i></a>
            </div>
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

    <div class="row">
        <div class="col-lg-3 col-6"><a href="{{ route('products.create') }}" class="btn btn-lg btn-block btn-outline-primary mb-3">Nuevo Producto</a></div>
        <div class="col-lg-3 col-6"><a href="{{ route('combos.create') }}" class="btn btn-lg btn-block btn-outline-success mb-3">Nuevo Combo</a></div>
        <div class="col-lg-3 col-6"><a href="{{ route('sales.create') }}" class="btn btn-lg btn-block btn-outline-warning mb-3">Registrar Venta</a></div>
        <div class="col-lg-3 col-6"><a href="{{ route('reports.index') }}" class="btn btn-lg btn-block btn-outline-danger mb-3">Ver reportes</a></div>
    </div>
    
    @if(!empty($lowStockList) && $lowStockList->isNotEmpty())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Productos con stock bajo</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-striped">
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
                                    <td>{{ $p->stock }}</td>
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
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    borderColor: 'rgba(60,141,188,1)',
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
