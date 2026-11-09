<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_cost',
        'subtotal',
        'lot_number',
        'expires_at',
        'purchased_quantity',
        'purchase_unit',
        'base_unit',
        'base_unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'expires_at' => 'date',
            'purchased_quantity' => 'decimal:3',
            'base_unit_cost' => 'decimal:6',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
