<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use PDO;
use ZipArchive;

/**
 * Comprueba que un respaldo se puede RESTAURAR (no solo que existe).
 *
 * Restaura el respaldo en una base temporal, compara filas por tabla y sumas de los
 * montos clave contra la base viva, y elimina la base temporal. La base viva solo se lee.
 *
 *   php artisan app:verificar-respaldo                 (último respaldo)
 *   php artisan app:verificar-respaldo --archivo=X.sql
 *
 * Las diferencias son esperables si hubo operaciones después de generar el respaldo;
 * para una verificación exacta, genere el respaldo justo antes (app:backup-db).
 */
class VerificarRespaldo extends Command
{
    protected $signature = 'app:verificar-respaldo {--archivo= : Nombre del respaldo en storage/app/backups (por defecto el más reciente)}';

    protected $description = 'Restaura un respaldo en una base temporal y lo compara con la base viva (solo lectura sobre la viva)';

    /** Tablas de uso técnico que cambian solas y no se comparan. */
    private const IGNORADAS = ['cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs', 'eventos'];

    /** Sumas de control: tabla => columna. */
    private const SUMAS = [
        'movimiento_cajas' => 'monto', 'abonos' => 'monto', 'facturas' => 'total_documento',
        'factura_items' => 'precio_unitario', 'mantenimientos' => 'costo', 'electronicas' => 'costo',
        'cierre_cajas' => 'saldo_final', 'stocks' => 'cantidad',
    ];

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('La verificación de restauración solo está disponible con MySQL.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        $archivo = $this->option('archivo')
            ?: collect(File::exists($dir) ? File::files($dir) : [])
                // Solo respaldos que contienen la base de datos (no las copias de código)
                ->filter(fn ($f) => in_array(BackupDatabase::tipoRespaldo($f->getFilename()), ['bd', 'bd_multimedia', 'integral'], true))
                ->sortByDesc(fn ($f) => $f->getMTime())
                ->first()?->getFilename();

        if (! $archivo || ! preg_match('/^[A-Za-z0-9_.\-]+$/', $archivo) || ! File::exists("{$dir}/{$archivo}")) {
            $this->error('No se encontró el respaldo a verificar en storage/app/backups.');

            return self::FAILURE;
        }

        $mysql = $this->binarioMysql();
        if (! $mysql) {
            $this->error('No se encontró el cliente mysql: defina MYSQL_PATH en .env.');

            return self::FAILURE;
        }

        $c = config('database.connections.mysql');
        $viva = $c['database'];
        $temp = 'verif_restauracion_'.date('Ymd_His');
        $pdo = new PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sqlTemporal = null;

        $this->info("Verificando respaldo: {$archivo}");

        try {
            $sql = "{$dir}/{$archivo}";
            if (str_ends_with(strtolower($archivo), '.zip')) {
                $sqlTemporal = $sql = $this->extraerSqlDeZip($sql);
            }

            $this->validarArchivo($sql);

            $pdo->exec("CREATE DATABASE `{$temp}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->restaurar($mysql, $c, $temp, $sql);

            $diferencias = $this->comparar($pdo, $viva, $temp);
        } catch (\Throwable $e) {
            $this->error('La restauración FALLÓ: '.$e->getMessage());
            $diferencias = -1;
        } finally {
            $pdo->exec("DROP DATABASE IF EXISTS `{$temp}`");
            if ($sqlTemporal) {
                File::delete($sqlTemporal);
            }
            $this->line('Base temporal eliminada.');
        }

        if ($diferencias === 0) {
            $this->info('✅ Respaldo restaurable y consistente con la base actual.');

            return self::SUCCESS;
        }

        if ($diferencias > 0) {
            $this->warn("⚠ El respaldo se restauró, pero hay {$diferencias} diferencia(s) con la base actual (normal si hubo operaciones después del respaldo).");

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    private function restaurar(string $mysql, array $c, string $temp, string $sql): void
    {
        putenv("MYSQL_PWD={$c['password']}");
        try {
            $proceso = proc_open(
                [$mysql, "--user={$c['username']}", "--host={$c['host']}", "--port={$c['port']}", '--default-character-set=utf8mb4', '--one-database', $temp],
                [0 => ['file', $sql, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $tuberias
            );
            $error = stream_get_contents($tuberias[2]);
            if (proc_close($proceso) !== 0) {
                throw new \RuntimeException(trim($error) ?: 'mysql terminó con error');
            }
        } finally {
            putenv('MYSQL_PWD');
        }
    }

    /**
     * El archivo debe estar completo (mysqldump escribe "Dump completed" al final) y no
     * puede cambiar de base: un USE o CREATE DATABASE escribiría fuera de la temporal.
     */
    private function validarArchivo(string $sql): void
    {
        $fin = '';
        $h = fopen($sql, 'rb');
        fseek($h, max(0, filesize($sql) - 512));
        $fin = (string) stream_get_contents($h);
        rewind($h);
        while (($linea = fgets($h)) !== false) {
            if (preg_match('/^\s*(USE\s|CREATE\s+(DATABASE|SCHEMA)\s)/i', $linea)) {
                fclose($h);
                throw new \RuntimeException('El respaldo contiene USE/CREATE DATABASE; se rechaza para no escribir fuera de la base temporal.');
            }
        }
        fclose($h);

        if (! str_contains($fin, 'Dump completed')) {
            throw new \RuntimeException('El respaldo está incompleto: falta la marca final "Dump completed" de mysqldump.');
        }
    }

    private function comparar(PDO $pdo, string $viva, string $temp): int
    {
        $diferencias = 0;
        $tablas = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$viva}' AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
        $restauradas = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$temp}'")->fetchAll(PDO::FETCH_COLUMN);

        $filas = [];
        foreach ($tablas as $tabla) {
            if (! in_array($tabla, $restauradas, true)) {
                $filas[] = [$tabla, '—', 'falta', '✘'];
                $diferencias++;

                continue;
            }
            $a = (int) $pdo->query("SELECT COUNT(*) FROM `{$viva}`.`{$tabla}`")->fetchColumn();
            $b = (int) $pdo->query("SELECT COUNT(*) FROM `{$temp}`.`{$tabla}`")->fetchColumn();
            $igual = $a === $b || in_array($tabla, self::IGNORADAS, true);
            $diferencias += $igual ? 0 : 1;
            $filas[] = [$tabla, $a, $b, $igual ? '✔' : '✘'];
        }
        $this->table(['Tabla', 'Filas actuales', 'Filas restauradas', ''], $filas);

        $sumas = [];
        foreach (self::SUMAS as $tabla => $columna) {
            if (! in_array($tabla, $restauradas, true)) {
                continue;
            }
            $a = $pdo->query("SELECT COALESCE(SUM(`{$columna}`), 0) FROM `{$viva}`.`{$tabla}`")->fetchColumn();
            $b = $pdo->query("SELECT COALESCE(SUM(`{$columna}`), 0) FROM `{$temp}`.`{$tabla}`")->fetchColumn();
            $igual = bccomp((string) $a, (string) $b, 2) === 0;
            $diferencias += $igual ? 0 : 1;
            $sumas[] = ["{$tabla}.{$columna}", $a, $b, $igual ? '✔' : '✘'];
        }
        $this->table(['Suma de control', 'Actual', 'Restaurada', ''], $sumas);

        return $diferencias;
    }

    private function extraerSqlDeZip(string $zipRuta): string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipRuta) !== true) {
            throw new \RuntimeException('No se pudo abrir el ZIP.');
        }
        $contenido = $zip->getFromName('database.sql');
        $zip->close();
        if ($contenido === false) {
            throw new \RuntimeException('El ZIP no contiene database.sql.');
        }
        $destino = storage_path('app/verif_restauracion_'.uniqid().'.sql');
        File::put($destino, $contenido);

        return $destino;
    }

    private function binarioMysql(): ?string
    {
        $candidatos = array_filter([
            config('backup.mysql_path'),
            'C:\\ServBay\\packages\\mysql\\current\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\mysql\\8.4\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
        ]);

        foreach ($candidatos as $ruta) {
            if (File::exists($ruta)) {
                return $ruta;
            }
        }

        return null;
    }
}
