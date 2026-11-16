<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('No se crean usuarios con credenciales predeterminadas. Use michisys:create-superadmin.');
    }
}
