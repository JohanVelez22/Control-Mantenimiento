<?php

namespace App\Console\Commands;

use App\Models\Evento;
use App\Models\User;
use App\Support\PasswordsConocidas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Fija la contraseña de un usuario desde la consola del servidor (entrada oculta).
 *
 * Cierra la ventana de riesgo de las cuentas con contraseña predeterminada: mientras
 * la contraseña sea la publicada, quien entre primero por la web podría fijar la nueva.
 * Desde la consola solo puede hacerlo quien tiene acceso físico/administrativo al servidor.
 *
 *   php artisan app:restablecer-password administrador@tecnisystemas.com
 */
class RestablecerPassword extends Command
{
    protected $signature = 'app:restablecer-password {email : Correo del usuario}';

    protected $description = 'Fija la contraseña de un usuario desde la consola (entrada oculta) y cierra sus sesiones';

    public function handle(): int
    {
        $usuario = User::where('email', $this->argument('email'))->first();
        if (! $usuario) {
            $this->error('No existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $clave = (string) $this->secret("Nueva contraseña para {$usuario->email} (no se mostrará)");
        $confirmacion = (string) $this->secret('Repita la contraseña');

        $validador = Validator::make(['password' => $clave, 'password_confirmation' => $confirmacion], [
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers(),
                function ($atributo, $valor, $fallar) {
                    if (PasswordsConocidas::es((string) $valor)) {
                        $fallar('Esa contraseña es pública o demasiado común. Elija otra.');
                    }
                },
            ],
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'Debe tener al menos 8 caracteres.',
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $usuario->forceFill([
            'password' => Hash::make($clave),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $usuario->id)->delete();
        }

        Evento::registrar('editar', $usuario, null, null, "Contraseña restablecida desde la consola del servidor para {$usuario->email}");
        $this->info("Contraseña actualizada para {$usuario->email}. Sus sesiones abiertas se cerraron.");

        return self::SUCCESS;
    }
}
