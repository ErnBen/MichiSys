<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'sku', 'description', 'price', 'cost', 'stock', 'image', 'active'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function combos()
    {
        return $this->belongsToMany(Combo::class)->withPivot('quantity')->withTimestamps();
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
