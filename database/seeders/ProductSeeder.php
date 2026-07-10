<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Hamburguesa Clásica', 'category' => 'Hamburguesas', 'price' => 8000, 'cost' => 5000, 'stock' => 20, 'description' => 'Clásica con lechuga, tomate y salsa especial.'],
            ['name' => 'Hamburguesa Doble', 'category' => 'Hamburguesas', 'price' => 12000, 'cost' => 7500, 'stock' => 15, 'description' => 'Doble carne con queso y panceta.'],
            ['name' => 'Lomito Completo', 'category' => 'Lomitos', 'price' => 11000, 'cost' => 6500, 'stock' => 12, 'description' => 'Lomito con lechuga, tomate, huevo y papas.'],
            ['name' => 'Pizza Muzzarella', 'category' => 'Pizzas', 'price' => 14000, 'cost' => 9000, 'stock' => 10, 'description' => 'Clásica pizza muzzarella con salsa y mucho queso.'],
            ['name' => 'Coca-Cola 500ml', 'category' => 'Bebidas', 'price' => 3500, 'cost' => 1500, 'stock' => 30, 'description' => 'Bebida gaseosa clásica.'],
            ['name' => 'Papas Fritas Medianas', 'category' => 'Papas Fritas', 'price' => 4000, 'cost' => 1800, 'stock' => 25, 'description' => 'Papas fritas crocantes.'],
            ['name' => 'Brownie con Helado', 'category' => 'Postres', 'price' => 7000, 'cost' => 3000, 'stock' => 18, 'description' => 'Brownie caliente con helado de vainilla.'],
        ];

        foreach ($items as $item) {
            $category = Category::where('name', $item['category'])->first();

            Product::create([
                'category_id' => $category?->id,
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'cost' => $item['cost'],
                'stock' => $item['stock'],
                'active' => true,
            ]);
        }
    }
}
