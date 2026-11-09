<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'product_id',
        'yield_quantity',
        'instructions',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'yield_quantity' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }
}
