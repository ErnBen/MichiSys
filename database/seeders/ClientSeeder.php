<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            ['name' => 'Juan Pérez', 'email' => 'juan.perez@example.com', 'phone' => '1122334455', 'address' => 'Av. Principal 123'],
            ['name' => 'María López', 'email' => 'maria.lopez@example.com', 'phone' => '1144556677', 'address' => 'Calle Falsa 456'],
            ['name' => 'Carlos Rodríguez', 'email' => 'carlos.rodriguez@example.com', 'phone' => '1199887766', 'address' => 'Barrio Sur 789'],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
