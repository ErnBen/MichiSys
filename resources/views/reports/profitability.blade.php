@extends('adminlte::page')
@section('title', 'Costos y rentabilidad')
@section('content_header')
    <h1>Costos, rentabilidad y punto de equilibrio</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.profitability') }}" class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label for="from">Desde</label>
                    <input id="from" type="date" name="from" value="{{ $from }}" class="form-control" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="to">Hasta</label>
                    <input id="to" type="date" name="to" value="{{ $to }}" class="form-control" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="fixed_costs">Costos fijos del período</label>
                    <input id="fixed_costs" type="number" name="fixed_costs" min="0" step="0.01" value="{{ $fixedCosts }}" class="form-control" placeholder="Ingresar para calcular equilibrio">
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-primary">Calcular</button>
                </div>
            </form>
            @if($salesWithoutCompleteCost > 0)
                <div class="alert alert-warning">
                    {{ $salesWithoutCompleteCost }} venta(s) del período no tienen un costo de lotes completo. Sus márgenes podrían estar subestimados.
                </div>
            @endif
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box bg-info"><div class="inner"><h3>{{ number_format($netSales, 2) }}</h3><p>Ventas netas</p></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-warning"><div class="inner"><h3>{{ number_format($costOfGoods, 2) }}</h3><p>Costo de lo vendido</p></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box {{ $grossProfit >= 0 ? 'bg-success' : 'bg-danger' }}"><div class="inner"><h3>{{ number_format($grossProfit, 2) }}</h3><p>Margen bruto</p></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box bg-primary"><div class="inner"><h3>{{ number_format($marginRatio * 100, 2) }}%</h3><p>Margen de contribución</p></div></div></div>
            </div>
            @if($fixedCosts !== null)
                <div class="alert alert-info">
                    @if($breakEvenSales !== null)
                        Punto de equilibrio estimado: <strong>{{ number_format($breakEvenSales, 2) }}</strong> en ventas netas
                        @if($breakEvenTransactions !== null)
                            (aproximadamente {{ $breakEvenTransactions }} ventas al promedio del período).
                        @endif
                    @else
                        No es posible calcular el punto de equilibrio: el período no tiene un margen de contribución positivo.
                    @endif
                    <div class="small">La estimación usa los costos fijos ingresados y el margen bruto calculado con costos de lotes.</div>
                </div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Ventas del período</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Venta</th><th>Fecha</th><th>Vendedor</th><th>Venta neta</th><th>Costo</th><th>Margen bruto</th></tr></thead>
                <tbody>
                    @forelse($saleRows as $row)
                        <tr>
                            <td>#{{ $row['sale']->id }}</td>
                            <td>{{ $row['sale']->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $row['sale']->user?->name ?? '—' }}</td>
                            <td>{{ number_format($row['net_sales'], 2) }}</td>
                            <td>{{ number_format($row['cost'], 2) }}</td>
                            <td>{{ number_format($row['profit'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No hay ventas en el período seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $sales->links() }}</div>
    </div>
@stop
