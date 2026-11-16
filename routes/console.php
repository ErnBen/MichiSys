<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('michisys:create-superadmin {email}', function (): int {
    $email = mb_strtolower(trim((string) $this->argument('email')));

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Indique una dirección de correo válida.');

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    if (User::where('email', $email)->exists()) {
        $this->error('Ese correo ya pertenece a un usuario. Use un correo exclusivo para esta cuenta.');

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    $password = $this->secret('Contraseña segura (mínimo 12 caracteres)');
    $confirmation = $this->secret('Repita la contraseña');

    if (! is_string($password) || strlen($password) < 12 || ! hash_equals($password, (string) $confirmation)) {
        $this->error('La contraseña no cumple el mínimo o la confirmación no coincide.');

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    if (! $this->confirm("Crear la cuenta de superadministrador {$email}?")) {
        $this->comment('Operación cancelada.');

        return \Symfony\Component\Console\Command\Command::FAILURE;
    }

    $user = new User([
        'name' => 'Superadministrador',
        'email' => $email,
        'password' => Hash::make($password),
        'role' => 'superadmin',
        'active' => true,
    ]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $this->info('Cuenta de superadministrador creada. No se imprimió ni guardó la contraseña.');

    return \Symfony\Component\Console\Command\Command::SUCCESS;
})->purpose('Crear explícitamente una cuenta inicial de superadministrador de forma interactiva');
