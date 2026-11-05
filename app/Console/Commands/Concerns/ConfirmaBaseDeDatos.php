<?php

namespace App\Console\Commands\Concerns;

/**
 * Confirmación fuerte para comandos que borran datos.
 *
 * La instalación de producción puede correr con APP_ENV=local, así que una guarda por
 * entorno no basta: se exige escribir el nombre de la base de datos. En modo no
 * interactivo (-n) la pregunta devuelve null y la operación se cancela.
 */
trait ConfirmaBaseDeDatos
{
    protected function confirmarBaseDeDatos(string $accion): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        if (app()->environment('production')) {
            $this->error("{$accion}: deshabilitado en producción.");

            return false;
        }

        $conexion = config('database.default');
        $nombre = (string) config("database.connections.{$conexion}.database");

        $this->warn("⚠️  {$accion} sobre la base de datos [{$nombre}] ({$conexion}). Esta acción no se puede deshacer.");
        $respuesta = $this->ask('Escriba el nombre exacto de la base de datos para confirmar');

        if ($respuesta === null || trim($respuesta) !== basename($nombre)) {
            $this->info('Operación cancelada.');

            return false;
        }

        return true;
    }
}
