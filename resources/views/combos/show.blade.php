@extends('adminlte::page')
@section('title', 'Ver Combo')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Detalle del combo</h1>
            <p class="page-subtitle mb-0">Información y productos incluidos.</p>
        </div>
        <a href="{{ route('combos.edit', $combo) }}" class="btn btn-outline-primary">
            <i class="fas fa-pen mr-2" aria-hidden="true"></i>Editar
        </a>
    </div>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row combo-detail-list">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $combo->name }}</dd>
                <dt class="col-sm-3">Precio</dt>
                <dd class="col-sm-9"><span class="combo-detail-price">Gs. {{ number_format($combo->price, 2) }}</span></dd>
                <dt class="col-sm-3">Activo</dt>
                <dd class="col-sm-9"><span class="badge badge-{{ $combo->active ? 'success' : 'secondary' }}">{{ $combo->active ? 'Activo' : 'Inactivo' }}</span></dd>
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $combo->description }}</dd>
                <dt class="col-sm-3">Productos</dt>
                <dd class="col-sm-9">
                    <ul class="combo-detail-products">
                        @foreach($combo->products as $product)
                            <li><span>{{ $product->name }}</span><strong>× {{ $product->pivot->quantity }}</strong></li>
                        @endforeach
                    </ul>
                </dd>
            </dl>
            @if($combo->image)
                <img src="{{ asset('storage/' . $combo->image) }}" alt="{{ $combo->name }}" class="img-fluid combo-detail-image">
            @endif
            <a href="{{ route('combos.index') }}" class="btn btn-light mt-3"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver a combos</a>
        </div>
    </div>
@stop
