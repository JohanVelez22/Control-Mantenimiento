<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear un Administrador fijo para pruebas
        User::factory()->create([
            'name' => 'Admin Sistema',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin123*'),
            'role' => 'admin',
        ]);

        // 2. Crear Usuarios con rol técnico
        User::factory(5)->create(['role' => 'tecnico']);

        // 3. Crear Clientes
        $clientes = Cliente::factory(10)->create();

        // 4. Crear Técnicos (entidades de mantenimiento)
        $tecnicos = Tecnico::factory(5)->create();

        // Obtener usuarios para asignar
        $users = User::all();

        // 5. Crear Equipos asociados a clientes y usuarios
        $equipos = Equipo::factory(20)->recycle($clientes)->recycle($users)->create();

        // 6. Crear Mantenimientos asociados a equipos, técnicos y usuarios
        Mantenimiento::factory(30)->recycle($equipos)->recycle($tecnicos)->recycle($users)->create();
    }
}
