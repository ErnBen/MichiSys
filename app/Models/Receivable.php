<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receivable extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'sale_id',
        'client_id',
        'created_by',
        'original_amount',
        'paid_amount',
        'balance',
        'due_date',
        'observations',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function getCurrentStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ((float) $this->balance <= 0) {
            return 'paid';
        }

        if ($this->due_date?->isBefore(today())) {
            return 'overdue';
        }

        return (float) $this->paid_amount > 0 ? 'partially_paid' : 'pending';
    }
}
