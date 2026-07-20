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
        // Deshabilitar foreign key checks temporalmente para truncate
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $adminPass = env('ADMIN_DEFAULT_PASSWORD', 'Admin123*');
        $tecnicoPass = env('TECNICO_DEFAULT_PASSWORD', 'Tecni123*');
        $invitadoPass = env('INVITADO_DEFAULT_PASSWORD', 'Invit123*');

        // Admin principal
        User::create([
            'name' => 'Administrador',
            'email' => 'administrador@tecnisystemas.com',
            'password' => Hash::make($adminPass),
            'role' => 'admin',
            'active' => true,
        ]);

        // Técnico
        User::create([
            'name' => 'Técnico',
            'email' => 'tecnico@tecnisystemas.com',
            'password' => Hash::make($tecnicoPass),
            'role' => 'tecnico',
            'active' => true,
        ]);

        // Invitado (solo consulta)
        User::create([
            'name' => 'Invitado',
            'email' => 'invitado@tecnisystemas.com',
            'password' => Hash::make($invitadoPass),
            'role' => 'invitado',
            'active' => true,
        ]);
    }
}