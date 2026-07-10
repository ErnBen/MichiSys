<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'client_id', 'subtotal', 'discount', 'tax', 'total'];

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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
