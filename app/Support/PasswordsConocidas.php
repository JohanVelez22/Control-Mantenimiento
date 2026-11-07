<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * Contraseñas que se publicaron en versiones anteriores de la documentación o del código
 * (y otras triviales). Una cuenta que las use debe cambiarlas antes de operar.
 */
class PasswordsConocidas
{
    public const LISTA = [
        'Admin123*', 'Tecni123*', 'Tecnico123*', 'Invit123*', 'Invitado123*',
        'password', 'secret', '12345678', 'admin123', 'Admin123',
    ];

    /** Compara texto plano (en el login, donde se conoce la contraseña escrita). */
    public static function es(string $password): bool
    {
        return in_array($password, self::LISTA, true);
    }

    /** Compara contra un hash guardado (backfill y verificación de pre-despliegue). */
    public static function coincideConHash(string $hash): bool
    {
        foreach (self::LISTA as $conocida) {
            if (Hash::check($conocida, $hash)) {
                return true;
            }
        }

        return false;
    }
}
