<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea usuarios base del sistema (admin + técnico demo).
     * Ejecutar: php artisan db:seed --class=AdminUserSeeder
     * Las contraseñas se definen en .env para producción.
     */
    public function run(): void
    {
        // Admin principal
        User::updateOrCreate(
            ['email' => 'admin@tusistema.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'AdminSeguro2026!')),
                'role' => 'admin',
                'active' => true,
            ]
        );

// Técnico demo
        User::updateOrCreate(
            ['email' => 'tecnico@tusistema.com'],
            [
                'name' => 'Técnico Demo',
                'password' => Hash::make(env('TECNICO_DEFAULT_PASSWORD', 'TecnicoSeguro2026!')),
                'role' => 'tecnico',
                'active' => true,
            ]
        );

        // Invitado demo (solo consulta)
        User::updateOrCreate(
            ['email' => 'invitado@tusistema.com'],
            [
                'name' => 'Invitado Demo',
                'password' => Hash::make(env('INVITADO_DEFAULT_PASSWORD', 'Invitado2026!')),
                'role' => 'invitado',
                'active' => true,
            ]
        );
    }
}