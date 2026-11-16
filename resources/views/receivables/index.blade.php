@extends('adminlte::page')
@section('title', 'Cuentas por cobrar')
@section('content_header')
    <h1>Cuentas por cobrar</h1>
@stop
@section('content')
    @include('shared.alerts')
    <div class="small-box bg-warning">
        <div class="inner">
            <h3>Gs. {{ number_format($pendingTotal, 0, ',', '.') }}</h3>
            <p>Saldo pendiente del negocio</p>
        </div>
        <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('receivables.index') }}" class="row">
                <div class="form-group col-12 col-md-4">
                    <label for="search">Cliente o número de venta</label>
                    <input id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Nombre, teléfono o venta">
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="status">Estado</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        @foreach(['pending' => 'Pendiente', 'partially_paid' => 'Parcialmente pagada', 'paid' => 'Pagada', 'overdue' => 'Vencida'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="from">Creada desde</label>
                    <input id="from" type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="to">Creada hasta</label>
                    <input id="to" type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="due_from">Vence desde</label>
                    <input id="due_from" type="date" name="due_from" class="form-control" value="{{ $filters['due_from'] ?? '' }}">
                </div>
                <div class="form-group col-6 col-md-2">
                    <label for="due_to">Vence hasta</label>
                    <input id="due_to" type="date" name="due_to" class="form-control" value="{{ $filters['due_to'] ?? '' }}">
                </div>
                <div class="form-group col-12">
                    <button class="btn btn-primary btn-lg" type="submit">Filtrar</button>
                    <a href="{{ route('receivables.index') }}" class="btn btn-outline-secondary btn-lg">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Venta</th><th>Cliente</th><th>Fecha</th><th>Vencimiento</th>
                            <th>Deuda original</th><th>Pagado</th><th>Saldo</th><th>Estado</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receivables as $receivable)
                            @php($state = $receivable->current_status)
                            <tr>
                                <td>#{{ $receivable->sale_id }}</td>
                                <td>{{ $receivable->client->name }}</td>
                                <td>{{ $receivable->created_at->format('d/m/Y') }}</td>
                                <td>{{ $receivable->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>Gs. {{ number_format($receivable->original_amount, 0, ',', '.') }}</td>
                                <td>Gs. {{ number_format($receivable->paid_amount, 0, ',', '.') }}</td>
                                <td class="font-weight-bold">Gs. {{ number_format($receivable->balance, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $state === 'paid' ? 'badge-success' : ($state === 'overdue' ? 'badge-danger' : 'badge-warning') }}">
                                        {{ ['pending' => 'Pendiente', 'partially_paid' => 'Parcialmente pagada', 'paid' => 'Pagada', 'overdue' => 'Vencida'][$state] }}
                                    </span>
                                </td>
                                <td><a href="{{ route('receivables.show', $receivable) }}" class="btn btn-primary btn-sm">Ver / registrar pago</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center">No hay cuentas por cobrar con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">{{ $receivables->links() }}</div>
    </div>
@stop
