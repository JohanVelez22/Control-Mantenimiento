<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * ARQ-09: un solo lugar para localizar mysql / mysqldump (antes había tres copias que
 * podían divergir) y para pasarles las credenciales.
 *
 * D-10: la contraseña ya no va en la variable de entorno MYSQL_PWD (visible para otros
 * procesos del mismo usuario y obsoleta en MySQL 8.4) sino en un archivo de opciones
 * temporal (--defaults-extra-file) que solo existe mientras dura el comando.
 */
final class BinariosMysql
{
    private const RUTAS = [
        'C:\\ServBay\\packages\\mysql\\current\\bin\\',
        'C:\\ServBay\\packages\\mysql\\8.4\\bin\\',
        'C:\\ServBay\\packages\\mysql\\8.0\\bin\\',
        'C:\\ServBay\\packages\\defaultsqlserver\\current\\bin\\',
        'C:\\ServBay\\packages\\defaultsqlserver\\8.4\\bin\\',
        'C:\\ServBay\\packages\\mariadb\\current\\bin\\',
        'C:\\xampp\\mysql\\bin\\',
        '/usr/bin/',
        '/usr/local/bin/',
    ];

    public static function mysql(): ?string
    {
        return self::buscar('mysql', config('backup.mysql_path'));
    }

    public static function mysqldump(): ?string
    {
        return self::buscar('mysqldump', config('backup.mysqldump_path'));
    }

    private static function buscar(string $nombre, ?string $configurado): ?string
    {
        if ($configurado && File::exists($configurado)) {
            return $configurado;
        }

        $ejecutable = PHP_OS_FAMILY === 'Windows' ? $nombre.'.exe' : $nombre;
        foreach (self::RUTAS as $carpeta) {
            $esWindows = str_contains($carpeta, ':\\');
            if ($esWindows !== (PHP_OS_FAMILY === 'Windows')) {
                continue;
            }
            if (File::exists($carpeta.$ejecutable)) {
                return $carpeta.$ejecutable;
            }
        }

        $salida = [];
        $codigo = 1;
        @exec(PHP_OS_FAMILY === 'Windows' ? "where {$nombre} 2>nul" : "command -v {$nombre} 2>/dev/null", $salida, $codigo);

        return $codigo === 0 && ! empty($salida[0]) ? trim($salida[0]) : null;
    }

    /**
     * Ejecuta $usar con la ruta de un archivo de opciones [client] con las credenciales
     * de la conexión, y lo borra al terminar (aunque falle).
     *
     * @template T
     *
     * @param  callable(string): T  $usar
     * @return T
     */
    public static function conCredenciales(array $conexion, callable $usar): mixed
    {
        $ruta = tempnam(sys_get_temp_dir(), 'tecni_my');
        $escapar = fn ($v) => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $v).'"';
        file_put_contents($ruta, implode("\n", [
            '[client]',
            'user='.$escapar($conexion['username'] ?? 'root'),
            'password='.$escapar($conexion['password'] ?? ''),
            'host='.$escapar($conexion['host'] ?? '127.0.0.1'),
            'port='.(int) ($conexion['port'] ?? 3306),
            '',
        ]));
        @chmod($ruta, 0600);

        try {
            return $usar($ruta);
        } finally {
            @unlink($ruta);
        }
    }
}
