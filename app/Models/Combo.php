<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combo extends Model
{
    use HasFactory;
    use BelongsToBusiness;

    protected $fillable = ['name', 'description', 'price', 'image', 'active'];

    public function products()
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity', 'business_id')->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(SaleCombo::class);
    }
}
