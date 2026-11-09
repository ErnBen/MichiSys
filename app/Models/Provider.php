<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;
    use BelongsToBusiness;

    protected $fillable = ['name', 'email', 'contact', 'phone', 'address'];

    public function products()
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }
}
