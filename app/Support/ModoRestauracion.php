<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * D-01: mientras se restaura un respaldo, el resto de usuarios ve una página de
 * mantenimiento (503) en lugar de trabajar sobre una base que está siendo reemplazada.
 *
 * Se usa un archivo propio con vencimiento (y no `php artisan down`) para que, si el
 * proceso se interrumpe a mitad de la restauración, el sistema no quede bloqueado: el
 * bloqueo vence solo a los MINUTOS_MAXIMOS.
 */
final class ModoRestauracion
{
    public const MINUTOS_MAXIMOS = 30;

    private static function ruta(): string
    {
        return storage_path('framework/restaurando.json');
    }

    public static function activar(string $archivo, ?string $usuario): void
    {
        File::ensureDirectoryExists(dirname(self::ruta()));
        File::put(self::ruta(), json_encode([
            'archivo' => $archivo,
            'usuario' => $usuario,
            'inicio' => now()->toIso8601String(),
            'vence' => now()->addMinutes(self::MINUTOS_MAXIMOS)->getTimestamp(),
        ]));
    }

    public static function desactivar(): void
    {
        if (File::exists(self::ruta())) {
            File::delete(self::ruta());
        }
    }

    public static function activo(): bool
    {
        if (! File::exists(self::ruta())) {
            return false;
        }
        $datos = json_decode((string) File::get(self::ruta()), true);
        if (! is_array($datos) || (int) ($datos['vence'] ?? 0) < now()->getTimestamp()) {
            self::desactivar();

            return false;
        }

        return true;
    }
}
