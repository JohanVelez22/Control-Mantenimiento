<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea los 3 usuarios únicos del sistema.
     * Ejecutar: php artisan db:seed --class=AdminUserSeeder
     *
     * Las contraseñas se leen de .env (ADMIN_DEFAULT_PASSWORD, TECNICO_DEFAULT_PASSWORD,
     * INVITADO_DEFAULT_PASSWORD). No existe ninguna contraseña por defecto en el código:
     * en producción la variable es obligatoria; en otros entornos se genera una aleatoria
     * y se muestra una sola vez en consola.
     */
    public function run(): void
    {
        $usuarios = [
            ['email' => 'administrador@tecnisystemas.com', 'name' => 'Administrador', 'role' => 'admin', 'env' => 'admin'],
            ['email' => 'tecnico@tecnisystemas.com', 'name' => 'Técnico', 'role' => 'tecnico', 'env' => 'tecnico'],
            ['email' => 'invitado@tecnisystemas.com', 'name' => 'Invitado', 'role' => 'invitado', 'env' => 'invitado'],
        ];

        foreach ($usuarios as $u) {
            if (User::where('email', $u['email'])->exists()) {
                continue;
            }

            User::create([
                'email' => $u['email'],
                'name' => $u['name'],
                'password' => Hash::make($this->resolverPassword($u['env'], $u['email'])),
                'role' => $u['role'],
                'active' => true,
            ]);
        }
    }

    private function resolverPassword(string $variable, string $email): string
    {
        // O-14: config(), no env(): funciona igual con config:cache.
        $password = (string) config('seguridad.passwords_iniciales.'.$variable, '');

        if ($password !== '') {
            return $password;
        }

        if (app()->environment('production')) {
            throw new \RuntimeException("Defina {$variable} en .env antes de ejecutar el seeder en producción.");
        }

        $password = Str::password(16);
        $this->command?->warn("{$variable} no está definida. Contraseña temporal para {$email}: {$password}");

        return $password;
    }
}
