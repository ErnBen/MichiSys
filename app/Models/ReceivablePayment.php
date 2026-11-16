<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivablePayment extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'receivable_id',
        'user_id',
        'amount',
        'payment_method',
        'reference',
        'entity',
        'paid_at',
        'notes',
        'idempotency_key',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return [
            'cash' => 'Efectivo',
            'bank_transfer' => 'Transferencia bancaria',
            'qr' => 'Pago mediante QR',
            'debit_card' => 'Tarjeta de débito',
            'credit_card' => 'Tarjeta de crédito',
            'other' => 'Otro método de pago',
        ][$this->payment_method] ?? 'Otro';
    }
}
