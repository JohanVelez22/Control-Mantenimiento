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
        // Eliminar usuarios antiguos con @tusistema.com
        User::where('email', 'like', '%@tusistema.com')->delete();

        // Admin principal
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Sistema',
                'password' => Hash::make(env('ADMIN_DEFAULT_PASSWORD', 'Admin123*')),
                'role' => 'admin',
                'active' => true,
            ]
        );

        // Técnico demo
        User::updateOrCreate(
            ['email' => 'tecnico@example.com'],
            [
                'name' => 'Tecnico Sistema',
                'password' => Hash::make(env('TECNICO_DEFAULT_PASSWORD', 'Tecny123*')),
                'role' => 'tecnico',
                'active' => true,
            ]
        );

        // Invitado demo (solo consulta)
        User::updateOrCreate(
            ['email' => 'invitado@example.com'],
            [
                'name' => 'Invitado',
                'password' => Hash::make(env('INVITADO_DEFAULT_PASSWORD', 'Invit123*')),
                'role' => 'invitado',
                'active' => true,
            ]
        );
    }
}