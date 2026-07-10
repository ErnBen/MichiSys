@extends('adminlte::page')
@section('title', 'Reportes')
@section('content_header')
    <h1>Reportes</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="row">
        <div class="col-md-4"><a href="{{ route('reports.products') }}" target="_blank" class="btn btn-block btn-outline-primary mb-3">Reporte de Productos</a></div>
        <div class="col-md-4"><a href="{{ route('reports.sales') }}" target="_blank" class="btn btn-block btn-outline-success mb-3">Reporte de Ventas</a></div>
        <div class="col-md-4"><a href="{{ route('reports.inventory') }}" target="_blank" class="btn btn-block btn-outline-warning mb-3">Reporte de Inventario</a></div>
        <div class="col-md-4"><a href="{{ route('reports.clients') }}" target="_blank" class="btn btn-block btn-outline-info mb-3">Reporte de Clientes</a></div>
        <div class="col-md-4"><a href="{{ route('reports.providers') }}" target="_blank" class="btn btn-block btn-outline-danger mb-3">Reporte de Proveedores</a></div>
    </div>
@stop
