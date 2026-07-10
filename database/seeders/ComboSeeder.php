<?php

namespace Database\Seeders;

use App\Models\Combo;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ComboSeeder extends Seeder
{
    public function run(): void
    {
        $combos = [
            [
                'name' => 'Combo Hamburguesa',
                'description' => 'Hamburguesa clásica + papas medianas + bebida.',
                'price' => 17000,
                'products' => [
                    'Hamburguesa Clásica' => 1,
                    'Papas Fritas Medianas' => 1,
                    'Coca-Cola 500ml' => 1,
                ],
            ],
            [
                'name' => 'Combo Pizza Familiar',
                'description' => 'Pizza muzzarella + 2 bebidas.',
                'price' => 21500,
                'products' => [
                    'Pizza Muzzarella' => 1,
                    'Coca-Cola 500ml' => 2,
                ],
            ],
        ];

        foreach ($combos as $comboData) {
            $combo = Combo::create([
                'name' => $comboData['name'],
                'description' => $comboData['description'],
                'price' => $comboData['price'],
                'active' => true,
            ]);

            $sync = [];
            foreach ($comboData['products'] as $productName => $quantity) {
                $product = Product::where('name', $productName)->first();
                if ($product) {
                    $sync[$product->id] = ['quantity' => $quantity];
                }
            }

            $combo->products()->sync($sync);
        }
    }
}
