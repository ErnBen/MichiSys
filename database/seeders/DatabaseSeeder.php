<?php

namespace Database\Seeders;

// use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ComboSeeder::class,
            ClientSeeder::class,
            ProviderSeeder::class,
            InventoryMovementSeeder::class,
            SaleSeeder::class,
        ]);
    }
}
