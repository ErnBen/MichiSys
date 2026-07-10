<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class InventoryMovementSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $product = Product::first();

        if (! $user || ! $product) {
            return;
        }

        InventoryMovement::create([
            'product_id' => $product->id,
            'quantity' => 10,
            'type' => 'in',
            'note' => 'Entrada inicial de inventario',
            'user_id' => $user->id,
        ]);
    }
}
