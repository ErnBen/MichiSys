<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;
    use BelongsToBusiness;

    protected $fillable = [
        'product_id',
        'lot_id',
        'purchase_id',
        'sale_id',
        'production_id',
        'quantity',
        'type',
        'note',
        'user_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function lot()
    {
        return $this->belongsTo(InventoryLot::class);
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }
}
