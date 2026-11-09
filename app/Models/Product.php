<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;
    use BelongsToBusiness;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'price',
        'cost',
        'stock',
        'stock_minimo',
        'fecha_vencimiento',
        'image',
        'active',
        'kind',
        'unit',
    ];

    protected $casts = [
        'active' => 'boolean',
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'fecha_vencimiento' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->kind ??= 'product';
            $product->unit ??= 'piece';
        });

        static::created(function (Product $product): void {
            app(InventoryService::class)->createOpeningLot($product);
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function providers()
    {
        return $this->belongsToMany(Provider::class)->withTimestamps();
    }

    public function combos()
    {
        return $this->belongsToMany(Combo::class)->withPivot('quantity')->withTimestamps();
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function recipe(): HasOne
    {
        return $this->hasOne(Recipe::class);
    }

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }

    public function purchaseUnitFactors(): array
    {
        return match ($this->unit) {
            'g' => ['g' => 1, 'kg' => 1000],
            'ml' => ['ml' => 1, 'l' => 1000],
            default => ['piece' => 1],
        };
    }

    public function getUnitLabelAttribute(): string
    {
        return match ($this->unit) {
            'g' => 'g',
            'ml' => 'ml',
            default => 'pieza(s)',
        };
    }
}
