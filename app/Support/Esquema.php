<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Consulta, una vez por proceso, si una columna existe.
 *
 * Las columnas nuevas de esta versión (snapshot del cierre, costo histórico, código de
 * consulta, stock mínimo) se agregan con migraciones que ejecuta el dueño con
 * `php artisan migrate --force`. Mientras tanto el código sigue funcionando como antes:
 * solo usa una columna nueva si ya existe.
 */
final class Esquema
{
    /** @var array<string, array<int, string>> */
    private static array $columnas = [];

    public static function tiene(string $tabla, string $columna): bool
    {
        if (! array_key_exists($tabla, self::$columnas)) {
            try {
                self::$columnas[$tabla] = Schema::hasTable($tabla) ? Schema::getColumnListing($tabla) : [];
            } catch (\Throwable) {
                return false;
            }
        }

        return in_array($columna, self::$columnas[$tabla], true);
    }

    /** Para pruebas y tras migrar dentro del mismo proceso. */
    public static function olvidar(): void
    {
        self::$columnas = [];
    }
}
