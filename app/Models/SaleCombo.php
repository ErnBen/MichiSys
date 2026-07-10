<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleCombo extends Model
{
    use HasFactory;

    protected $table = 'sale_combo';

    protected $fillable = ['sale_id', 'combo_id', 'quantity', 'price', 'subtotal'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class);
    }
}
