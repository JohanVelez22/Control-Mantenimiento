<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * D-03: manifiesto con la huella SHA-256 y el tamaño de cada respaldo generado.
 *
 * Permite saber si un archivo se dañó o se alteró después de crearse (por ejemplo, al
 * copiarlo a la nube o a un disco externo). `app:verificar-respaldo` lo comprueba antes de
 * restaurar en la base temporal. El manifiesto vive junto a los respaldos
 * (storage/app/backups/manifiesto.json) y se copia con ellos a la carpeta externa.
 */
final class ManifiestoRespaldos
{
    public const ARCHIVO = 'manifiesto.json';

    public static function registrar(string $rutaRespaldo, array $extra = []): void
    {
        $dir = dirname($rutaRespaldo);
        $datos = self::leer($dir);
        $datos[basename($rutaRespaldo)] = [
            'sha256' => hash_file('sha256', $rutaRespaldo),
            'bytes' => filesize($rutaRespaldo),
            'creado' => now()->toIso8601String(),
        ] + $extra;

        // Se quitan las entradas de archivos que ya no existen (rotación o borrado).
        $datos = array_filter($datos, fn ($v, $nombre) => File::exists($dir.DIRECTORY_SEPARATOR.$nombre), ARRAY_FILTER_USE_BOTH);
        ksort($datos);
        File::put($dir.DIRECTORY_SEPARATOR.self::ARCHIVO, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * null = el archivo no está en el manifiesto (respaldo anterior a esta versión);
     * true = la huella coincide; false = el archivo cambió o está dañado.
     */
    public static function verificar(string $rutaRespaldo): ?bool
    {
        $entrada = self::leer(dirname($rutaRespaldo))[basename($rutaRespaldo)] ?? null;
        if (! $entrada || empty($entrada['sha256'])) {
            return null;
        }

        return hash_equals($entrada['sha256'], (string) hash_file('sha256', $rutaRespaldo));
    }

    public static function cifrado(string $rutaRespaldo): bool
    {
        return (bool) (self::leer(dirname($rutaRespaldo))[basename($rutaRespaldo)]['cifrado'] ?? false);
    }

    /** @return array<string, array<string, mixed>> */
    public static function leer(string $dir): array
    {
        $ruta = $dir.DIRECTORY_SEPARATOR.self::ARCHIVO;
        if (! File::exists($ruta)) {
            return [];
        }
        $datos = json_decode((string) File::get($ruta), true);

        return is_array($datos) ? $datos : [];
    }
}
