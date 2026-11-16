@extends('adminlte::page')
@section('title', 'Cuenta por cobrar')
@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <h1>Cuenta por cobrar — Venta #{{ $receivable->sale_id }}</h1>
        <a href="{{ route('receivables.index') }}" class="btn btn-secondary">Volver a cuentas por cobrar</a>
    </div>
@stop
@section('content')
    @include('shared.alerts')
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @php($state = $receivable->current_status)
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-6 col-lg-3 mb-3"><strong>Cliente</strong><br>{{ $receivable->client->name }}</div>
                <div class="col-6 col-lg-3 mb-3"><strong>Creada</strong><br>{{ $receivable->created_at->format('d/m/Y H:i') }}</div>
                <div class="col-6 col-lg-3 mb-3"><strong>Vencimiento</strong><br>{{ $receivable->due_date?->format('d/m/Y') ?? 'Sin vencimiento' }}</div>
                <div class="col-6 col-lg-3 mb-3"><strong>Estado</strong><br>{{ ['pending' => 'Pendiente', 'partially_paid' => 'Parcialmente pagada', 'paid' => 'Pagada', 'overdue' => 'Vencida'][$state] }}</div>
                <div class="col-6 col-lg-4 mb-3"><strong>Deuda original</strong><br>Gs. {{ number_format($receivable->original_amount, 0, ',', '.') }}</div>
                <div class="col-6 col-lg-4 mb-3"><strong>Total pagado</strong><br>Gs. {{ number_format($receivable->paid_amount, 0, ',', '.') }}</div>
                <div class="col-12 col-lg-4 mb-3"><strong>Saldo pendiente</strong><br><span class="h4">Gs. {{ number_format($receivable->balance, 0, ',', '.') }}</span></div>
                @if($receivable->observations)
                    <div class="col-12"><strong>Observaciones:</strong> {{ $receivable->observations }}</div>
                @endif
            </div>
        </div>
    </div>

    @if((float) $receivable->balance > 0)
        <div class="card card-primary">
            <div class="card-header"><h2 class="card-title">Registrar pago</h2></div>
            <form method="POST" action="{{ route('receivables.payments.store', $receivable) }}">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ $paymentIdempotencyKey }}">
                <div class="card-body row">
                    <div class="form-group col-12 col-md-3">
                        <label for="amount">Importe a pagar (Gs.)</label>
                        <input id="amount" type="number" name="amount" min="1" max="{{ $receivable->balance }}" step="1" class="form-control form-control-lg" value="{{ old('amount', $receivable->balance) }}" required inputmode="numeric">
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="payment_method">Método de pago</label>
                        <select id="payment_method" name="payment_method" class="form-control form-control-lg" required>
                            @foreach(['cash' => 'Efectivo', 'bank_transfer' => 'Transferencia bancaria', 'qr' => 'Pago mediante QR', 'debit_card' => 'Tarjeta de débito', 'credit_card' => 'Tarjeta de crédito', 'other' => 'Otro'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="paid_at">Fecha y hora del pago</label>
                        <input id="paid_at" type="datetime-local" name="paid_at" class="form-control form-control-lg" value="{{ old('paid_at', now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="form-group col-12 col-md-3">
                        <label for="reference">Referencia / comprobante</label>
                        <input id="reference" name="reference" maxlength="120" class="form-control form-control-lg" value="{{ old('reference') }}">
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="entity">Banco o plataforma (opcional)</label>
                        <input id="entity" name="entity" maxlength="120" class="form-control" value="{{ old('entity') }}">
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="notes">Observaciones (opcional)</label>
                        <input id="notes" name="notes" maxlength="2000" class="form-control" value="{{ old('notes') }}">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg">Registrar pago</button>
                </div>
            </form>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h2 class="card-title">Pagos de esta deuda</h2></div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Fecha</th><th>Importe</th><th>Método</th><th>Referencia</th><th>Registrado por</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse($receivable->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                            <td>Gs. {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>{{ $payment->payment_method_label }}</td>
                            <td>{{ $payment->reference ?: '—' }}{{ $payment->entity ? ' · '.$payment->entity : '' }}</td>
                            <td>{{ $payment->user?->name ?? 'N/A' }}</td>
                            <td>{{ $payment->reversed_at ? 'Anulado: '.$payment->reversal_reason : 'Registrado' }}</td>
                            <td>
                                @can('manage-business')
                                    @if(!$payment->reversed_at)
                                        <form method="POST" action="{{ route('receivables.payments.reverse', [$receivable, $payment]) }}" class="d-flex flex-wrap" onsubmit="return confirm('¿Anular este pago? El movimiento quedará en el historial y el saldo se recalculará.');">
                                            @csrf
                                            @method('DELETE')
                                            <input name="reversal_reason" class="form-control form-control-sm mb-1" placeholder="Motivo obligatorio" required minlength="5">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Anular pago</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">Todavía no se registraron pagos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Historial de cuentas del cliente: {{ $receivable->client->name }}</h2></div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead><tr><th>Venta</th><th>Fecha</th><th>Importe original</th><th>Pagado</th><th>Saldo</th><th>Estado</th></tr></thead>
                <tbody>
                    @foreach($clientHistory as $history)
                        <tr>
                            <td><a href="{{ route('receivables.show', $history) }}">#{{ $history->sale_id }}</a></td>
                            <td>{{ $history->created_at->format('d/m/Y') }}</td>
                            <td>Gs. {{ number_format($history->original_amount, 0, ',', '.') }}</td>
                            <td>Gs. {{ number_format($history->paid_amount, 0, ',', '.') }}</td>
                            <td>Gs. {{ number_format($history->balance, 0, ',', '.') }}</td>
                            <td>{{ ['pending' => 'Pendiente', 'partially_paid' => 'Parcialmente pagada', 'paid' => 'Pagada', 'overdue' => 'Vencida'][$history->current_status] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
