@extends('adminlte::page')
@section('title', 'Ver Combo')
@section('content_header')
    <h1>Ver Combo</h1>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Nombre</dt>
                <dd class="col-sm-9">{{ $combo->name }}</dd>
                <dt class="col-sm-3">Precio</dt>
                <dd class="col-sm-9">{{ number_format($combo->price, 2) }}</dd>
                <dt class="col-sm-3">Activo</dt>
                <dd class="col-sm-9">{{ $combo->active ? 'Sí' : 'No' }}</dd>
                <dt class="col-sm-3">Descripción</dt>
                <dd class="col-sm-9">{{ $combo->description }}</dd>
                <dt class="col-sm-3">Productos</dt>
                <dd class="col-sm-9">
                    <ul>
                        @foreach($combo->products as $product)
                            <li>{{ $product->name }} x {{ $product->pivot->quantity }}</li>
                        @endforeach
                    </ul>
                </dd>
            </dl>
            @if($combo->image)
                <img src="{{ asset('storage/' . $combo->image) }}" alt="{{ $combo->name }}" class="img-fluid" style="max-width: 300px;">
            @endif
            <a href="{{ route('combos.index') }}" class="btn btn-secondary mt-3">Volver</a>
        </div>
    </div>
@stop
