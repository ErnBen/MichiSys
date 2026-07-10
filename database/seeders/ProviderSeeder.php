<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            ['name' => 'Distribuciones Rápidas', 'email' => 'contacto@distribucionesrapidas.com', 'phone' => '1166778899', 'address' => 'Zona Norte 101'],
            ['name' => 'Bebidas Express', 'email' => 'ventas@bebidasexpress.com', 'phone' => '1133557799', 'address' => 'Centro Comercial'],
            ['name' => 'Postres Deliciosos', 'email' => 'info@postresdeliciosos.com', 'phone' => '1177665544', 'address' => 'Paseo del Buen Gusto'],
        ];

        foreach ($providers as $provider) {
            Provider::create($provider);
        }
    }
}
