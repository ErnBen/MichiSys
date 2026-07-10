<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Hamburguesas', 'description' => 'Hamburguesas clásicas y especiales'],
            ['name' => 'Lomitos', 'description' => 'Lomitos con papas y salsas'],
            ['name' => 'Pizzas', 'description' => 'Pizzas con los mejores ingredientes'],
            ['name' => 'Bebidas', 'description' => 'Refrescos, jugos y bebidas frías'],
            ['name' => 'Papas Fritas', 'description' => 'Papas fritas crocantes'],
            ['name' => 'Postres', 'description' => 'Dulces y postres para terminar'],
        ];

        foreach ($categories as $category) {
            Category::create(array_merge($category, [
                'slug' => \Illuminate\Support\Str::slug($category['name']),
                'active' => true,
            ]));
        }
    }
}
