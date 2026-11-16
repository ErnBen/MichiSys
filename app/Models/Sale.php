<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    use BelongsToBusiness;

    protected $fillable = [
        'user_id',
        'client_id',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'payment_reference',
        'payment_entity',
        'payment_paid_at',
        'payment_notes',
        'cash_received',
        'change_due',
        'idempotency_key',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_paid_at' => 'datetime',
            'cash_received' => 'decimal:2',
            'change_due' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function products()
    {
        return $this->hasMany(SaleProduct::class);
    }

    public function combos()
    {
        return $this->hasMany(SaleCombo::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function canceller()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
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
            'credit' => 'Venta a crédito',
        ][$this->payment_method] ?? 'No registrado';
    }
}
