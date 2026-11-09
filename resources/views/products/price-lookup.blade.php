@extends('adminlte::page')

@section('title', 'Consultar precios')

@section('content_header')
    <h1>Consultar precios</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form id="price-search-form" method="GET" action="{{ route('prices.index') }}" class="mb-4">
                <label for="price-search">Buscar por nombre o código</label>
                <div class="input-group">
                    <input
                        id="price-search"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Ej.: alimento, SKU-001"
                        autocomplete="off"
                    >
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span class="sr-only">Buscar</span>
                        </button>
                    </div>
                </div>
                <small class="form-text text-muted">Consulta precios de venta de productos disponibles. Este apartado no permite modificar precios.</small>
            </form>

            <div class="row">
                @forelse($products as $product)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <article class="card h-100">
                            @if($product->image)
                                <img
                                    src="{{ asset('storage/'.$product->image) }}"
                                    class="card-img-top"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                    style="height: 180px; object-fit: contain; background: #f8f9fa;"
                                >
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 180px;">
                                    <i class="fas fa-box-open fa-3x text-muted" aria-hidden="true"></i>
                                    <span class="sr-only">Producto sin imagen</span>
                                </div>
                            @endif
                            <div class="card-body">
                                <h2 class="h5 card-title">{{ $product->name }}</h2>
                                @if($product->sku)
                                    <p class="mb-1 text-muted">Código: {{ $product->sku }}</p>
                                @endif
                                <p class="mb-2">Categoría: {{ $product->category?->name ?? 'Sin categoría' }}</p>
                                <p class="h4 text-success mb-0" aria-label="Precio de venta en guaraníes">
                                    Gs. {{ number_format((float) $product->price, 0, ',', '.') }}
                                </p>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            {{ $search !== '' ? 'No se encontraron productos con esa búsqueda.' : 'No hay productos activos para consultar.' }}
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
        @if($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
@stop

@section('js')
    <script>
        (() => {
            const form = document.getElementById('price-search-form');
            const input = document.getElementById('price-search');
            if (!form || !input) return;

            let timer;
            input.addEventListener('input', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => form.requestSubmit(), 300);
            });
        })();
    </script>
@stop
