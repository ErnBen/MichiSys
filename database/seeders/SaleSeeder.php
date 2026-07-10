<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Combo;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleCombo;
use App\Models\SaleProduct;
use App\Models\User;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $client = Client::first();
        $product = Product::where('name', 'Hamburguesa Clásica')->first();
        $combo = Combo::where('name', 'Combo Hamburguesa')->first();

        if (! $user || ! $client || ! $product || ! $combo) {
            return;
        }

        $sale = Sale::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'subtotal' => 25000,
            'discount' => 1000,
            'tax' => 4560,
            'total' => 28560,
        ]);

        SaleProduct::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
            'subtotal' => $product->price,
        ]);

        $product->decrement('stock', 1);
        InventoryMovement::create([
            'product_id' => $product->id,
            'quantity' => 1,
            'type' => 'out',
            'note' => "Venta #{$sale->id}",
            'user_id' => $user->id,
        ]);

        SaleCombo::create([
            'sale_id' => $sale->id,
            'combo_id' => $combo->id,
            'quantity' => 1,
            'price' => $combo->price,
            'subtotal' => $combo->price,
        ]);

        foreach ($combo->products as $comboProduct) {
            $quantity = $comboProduct->pivot->quantity;
            $comboProduct->decrement('stock', $quantity);
            InventoryMovement::create([
                'product_id' => $comboProduct->id,
                'quantity' => $quantity,
                'type' => 'out',
                'note' => "Venta combo #{$sale->id}",
                'user_id' => $user->id,
            ]);
        }
    }
}
