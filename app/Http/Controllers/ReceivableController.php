<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => ['nullable', Rule::in(['pending', 'partially_paid', 'paid', 'overdue'])],
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'due_from' => 'nullable|date',
            'due_to' => 'nullable|date|after_or_equal:due_from',
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';

        $receivablesQuery = Receivable::query()
            ->where('status', '!=', 'cancelled')
            ->with(['client', 'sale', 'creator'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) =>
                $query->whereHas('client', fn ($query) =>
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                )->orWhere('sale_id', is_numeric($search) ? (int) $search : -1)
            ))
            ->when(isset($filters['from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when(isset($filters['due_from']), fn ($query) => $query->whereDate('due_date', '>=', $filters['due_from']))
            ->when(isset($filters['due_to']), fn ($query) => $query->whereDate('due_date', '<=', $filters['due_to']));

        if ($status === 'overdue') {
            $receivablesQuery->where('balance', '>', 0)->whereDate('due_date', '<', today());
        } elseif ($status === 'pending') {
            $receivablesQuery->where('status', 'pending')->where(fn ($query) =>
                $query->whereNull('due_date')->orWhereDate('due_date', '>=', today())
            );
        } elseif ($status === 'partially_paid') {
            $receivablesQuery->where('status', 'partially_paid')->where(fn ($query) =>
                $query->whereNull('due_date')->orWhereDate('due_date', '>=', today())
            );
        } elseif ($status === 'paid') {
            $receivablesQuery->where('status', 'paid');
        }

        $pendingTotal = (float) Receivable::where('balance', '>', 0)->sum('balance');
        $receivables = $receivablesQuery->orderByDesc('id')->paginate(20)->withQueryString();

        return view('receivables.index', compact('receivables', 'pendingTotal', 'filters', 'search', 'status'));
    }

    public function show(Receivable $receivable)
    {
        $receivable->load([
            'client',
            'sale',
            'creator',
            'payments' => fn ($query) => $query->with(['user', 'reversedBy'])->orderByDesc('paid_at'),
        ]);
        $clientHistory = $receivable->client
            ->receivables()
            ->with(['sale', 'payments.user'])
            ->orderByDesc('id')
            ->get();
        $paymentIdempotencyKey = old('idempotency_key', (string) Str::uuid());

        return view('receivables.show', compact('receivable', 'clientHistory', 'paymentIdempotencyKey'));
    }

    public function storePayment(Request $request, Receivable $receivable)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|decimal:0,2',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qr', 'debit_card', 'credit_card', 'other'])],
            'reference' => 'nullable|string|max:120',
            'entity' => 'nullable|string|max:120',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
            'idempotency_key' => 'nullable|uuid',
        ]);
        $amount = round((float) $data['amount'], 2);
        $idempotencyKey = $data['idempotency_key'] ?? (string) Str::uuid();
        $alreadyRecorded = ReceivablePayment::where('receivable_id', $receivable->id)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        if ($alreadyRecorded) {
            return Redirect::route('receivables.show', $receivable)
                ->with('success', 'Este pago ya había sido registrado.');
        }

        try {
            DB::transaction(function () use ($request, $receivable, $data, $amount, $idempotencyKey): void {
                $amountSql = number_format($amount, 2, '.', '');
                $updated = Receivable::whereKey($receivable->id)
                    ->where('business_id', $request->user()->business_id)
                    ->where('balance', '>=', $amount)
                    ->update([
                        'balance' => DB::raw("balance - {$amountSql}"),
                        'paid_amount' => DB::raw("paid_amount + {$amountSql}"),
                        'status' => DB::raw("CASE WHEN balance - {$amountSql} <= 0 THEN 'paid' ELSE 'partially_paid' END"),
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw ValidationException::withMessages([
                        'amount' => 'El pago no puede superar el saldo pendiente de la deuda.',
                    ]);
                }

                ReceivablePayment::create([
                    'receivable_id' => $receivable->id,
                    'user_id' => $request->user()->id,
                    'amount' => $amount,
                    'payment_method' => $data['payment_method'],
                    'reference' => $data['reference'] ?? null,
                    'entity' => $data['entity'] ?? null,
                    'paid_at' => $data['paid_at'],
                    'notes' => $data['notes'] ?? null,
                    'idempotency_key' => $idempotencyKey,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'receivable_payments.idempotency_key')) {
                throw $exception;
            }

            $duplicate = ReceivablePayment::where('receivable_id', $receivable->id)
                ->where('idempotency_key', $idempotencyKey)
                ->exists();

            if (! $duplicate) {
                throw $exception;
            }

            return Redirect::route('receivables.show', $receivable)
                ->with('success', 'Este pago ya había sido registrado.');
        }

        return Redirect::route('receivables.show', $receivable)
            ->with('success', 'Pago registrado y saldo actualizado correctamente.');
    }

    public function reversePayment(Request $request, Receivable $receivable, ReceivablePayment $payment)
    {
        $data = $request->validate([
            'reversal_reason' => 'required|string|min:5|max:2000',
        ]);

        abort_unless((int) $payment->receivable_id === (int) $receivable->id, 404);

        DB::transaction(function () use ($request, $receivable, $payment, $data): void {
            $paymentUpdated = ReceivablePayment::whereKey($payment->id)
                ->where('receivable_id', $receivable->id)
                ->whereNull('reversed_at')
                ->update([
                    'reversed_at' => now(),
                    'reversed_by' => $request->user()->id,
                    'reversal_reason' => $data['reversal_reason'],
                    'updated_at' => now(),
                ]);

            if ($paymentUpdated !== 1) {
                throw ValidationException::withMessages([
                    'payment' => 'Este pago ya fue anulado y no puede modificarse nuevamente.',
                ]);
            }

            $amountSql = number_format((float) $payment->amount, 2, '.', '');
            $receivableUpdated = Receivable::whereKey($receivable->id)
                ->where('business_id', $request->user()->business_id)
                ->where('paid_amount', '>=', $payment->amount)
                ->update([
                    'balance' => DB::raw("balance + {$amountSql}"),
                    'paid_amount' => DB::raw("paid_amount - {$amountSql}"),
                    'status' => DB::raw("CASE WHEN paid_amount - {$amountSql} <= 0 THEN 'pending' ELSE 'partially_paid' END"),
                    'updated_at' => now(),
                ]);

            if ($receivableUpdated !== 1) {
                throw ValidationException::withMessages([
                    'payment' => 'No se pudo recalcular el saldo de la deuda; no se aplicó la anulación.',
                ]);
            }
        }, 3);

        return Redirect::route('receivables.show', $receivable)
            ->with('success', 'Pago anulado con trazabilidad; el saldo de la deuda fue recalculado.');
    }
}
