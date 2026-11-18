@extends('adminlte::page')
@section('title', 'Reportes')
@section('content_header')
    <h1>Reportes</h1>
    <p class="page-subtitle mb-0 mt-2">Consulta los resultados y descarga la información de tu negocio.</p>
@stop
@section('content')
    @include('shared.alerts')
    <div class="row">
        @foreach([
            ['reports.products', 'fa-box-open', 'Productos', 'Catálogo, precios y disponibilidad.', true],
            ['reports.sales', 'fa-cash-register', 'Ventas', 'Operaciones y totales de venta.', true],
            ['reports.inventory', 'fa-warehouse', 'Inventario', 'Existencias para planificar la reposición.', true],
            ['reports.clients', 'fa-user-friends', 'Clientes', 'Información de tu cartera de clientes.', true],
            ['reports.providers', 'fa-truck', 'Proveedores', 'Contactos para las compras del negocio.', true],
            ['reports.profitability', 'fa-chart-line', 'Costos y rentabilidad', 'Ganancias y punto de equilibrio.', false],
        ] as [$reportRoute, $icon, $label, $description, $pdf])
            <div class="col-12 col-md-6 col-xl-4 mb-3">
                <a href="{{ route($reportRoute) }}" class="card report-tile h-100 mb-0" @if($pdf) target="_blank" rel="noopener" @endif>
                    <div class="card-body">
                        <span class="metric-icon mb-3"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
                        <h2 class="h5 font-weight-bold">{{ $label }}</h2>
                        <p class="text-muted">{{ $description }}</p>
                        <span class="report-tile-action">{{ $pdf ? 'Abrir PDF' : 'Ver reporte' }} <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i></span>
                        @if($pdf)<small class="d-block text-muted mt-1">Se abre en otra pestaña</small>@endif
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@stop