<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLot extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'product_id',
        'purchase_item_id',
        'production_id',
        'lot_number',
        'expires_at',
        'received_quantity',
        'available_quantity',
        'unit_cost',
        'base_unit',
        'base_unit_cost',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'received_quantity' => 'integer',
            'available_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'base_unit_cost' => 'decimal:6',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }
}
