<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea los 3 usuarios únicos del sistema.
     * Ejecutar: php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        $adminPass = env('ADMIN_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(16));
        $tecnicoPass = env('TECNICO_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(16));
        $invitadoPass = env('INVITADO_DEFAULT_PASSWORD', \Illuminate\Support\Str::random(16));

        // Admin principal
        User::firstOrCreate(
            ['email' => 'administrador@tecnisystemas.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make($adminPass),
                'role' => 'admin',
                'active' => true,
            ]
        );

        // Técnico
        User::firstOrCreate(
            ['email' => 'tecnico@tecnisystemas.com'],
            [
                'name' => 'Técnico',
                'password' => Hash::make($tecnicoPass),
                'role' => 'tecnico',
                'active' => true,
            ]
        );

        // Invitado (solo consulta)
        User::firstOrCreate(
            ['email' => 'invitado@tecnisystemas.com'],
            [
                'name' => 'Invitado',
                'password' => Hash::make($invitadoPass),
                'role' => 'invitado',
                'active' => true,
            ]
        );
    }
}