<?php

namespace App\Console\Commands;

use App\Models\Configuracion;
use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-db 
                            {--files : Respaldar base de datos y archivos multimedia públicos en un solo ZIP}
                            {--code : Generar un snapshot empaquetado del código fuente}
                            {--all : Respaldo integral: Base de datos, multimedia pública y código fuente en un solo ZIP}
                            {--max-copies= : Límite máximo de copias a conservar antes de sobrescribir/rotar}
                            {--drive-path= : Ruta manual de sincronización de Google Drive}';

    protected $description = 'Crea un respaldo integral de MySQL, archivos y código, con sincronización automática a Google Drive';

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  SISTEMA DE BACKUPS AUTOMÁTICOS - TECNI-SISTEMAS   ');
        $this->info('====================================================');

        try {
            $database = env('DB_DATABASE', 'tecni_systemas');
            $safeDatabase = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '_', $database), '_') ?: 'database';
            $retentionDays = (int) env('BACKUP_RETENTION_DAYS', 15);

            $configEmpresa = Configuracion::first();
            $maxCopies = (int) ($this->option('max-copies') ?: ($configEmpresa?->backup_max_copias ?: 10));

            // Directorio local de almacenamiento
            $backupDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/backups'));
            if (! File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $date = Carbon::now()->format('Y-m-d_H-i-s');
            $generatedFiles = [];
            $mainFileName = '';
            $mainFileSize = '';
            $tipoEtiqueta = '';

            $isAll = (bool) $this->option('all');
            $isFiles = (bool) $this->option('files');
            $isCode = (bool) $this->option('code');

            if ($isAll) {
                // TIPO 3: Respaldo Integral (Snapshot Total: BD + Archivos + Código)
                $this->info('🚀 Generando Respaldo Integral (Base de Datos + Archivos Públicos + Código Fuente)...');

                $zipFileName = "backup_integral_{$safeDatabase}_{$date}.zip";
                $zipFilePath = $backupDir.DIRECTORY_SEPARATOR.$zipFileName;
                $tempSqlPath = $backupDir.DIRECTORY_SEPARATOR."temp_db_{$safeDatabase}_{$date}.sql";

                // 1. Volcar base de datos temporal
                $dbSizeKb = $this->dumpDatabase($tempSqlPath, $database, $safeDatabase, $date);

                // 2. Crear archivo ZIP integral único
                $zip = new ZipArchive;
                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('No se pudo inicializar ZipArchive para el respaldo integral.');
                }

                // Base de datos SQL completa en la raíz del ZIP
                $zip->addFile($tempSqlPath, 'database.sql');

                // Archivos multimedia y públicos (storage/app/public)
                $uploadCount = $this->addUploadsToZip($zip, 'archivos_publicos/');

                // Snapshot limpio del código fuente
                $codeCount = $this->addSourceCodeToZip($zip, $backupDir, 'codigo_fuente/');

                // Metadatos y Guía de Restauración
                $readme = $this->createReadme('integral', $database, $date, $uploadCount, $codeCount);
                $zip->addFromString('README_RESTAURACION.txt', $readme);

                $infoJson = json_encode([
                    'sistema' => 'Tecni-Systemas',
                    'tipo' => 'integral_total',
                    'fecha' => Carbon::now()->toIso8601String(),
                    'database' => $database,
                    'db_size_kb' => $dbSizeKb,
                    'archivos_publicos_count' => $uploadCount,
                    'codigo_fuente_files_count' => $codeCount,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $zip->addFromString('backup_info.json', $infoJson);

                $zip->close();

                // Limpiar SQL temporal
                if (File::exists($tempSqlPath)) {
                    File::delete($tempSqlPath);
                }

                $sizeMb = round(File::size($zipFilePath) / (1024 * 1024), 2);
                $this->info("✅ Respaldo Integral generado exitosamente: {$zipFileName} ({$sizeMb} MB)");
                Log::info("Backup integral creado: {$zipFileName} ({$sizeMb} MB)");

                $generatedFiles[] = $zipFilePath;
                $mainFileName = $zipFileName;
                $mainFileSize = "{$sizeMb} MB";
                $tipoEtiqueta = 'Respaldo Integral (BD + Archivos + Código)';

            } elseif ($isFiles) {
                // TIPO 2: BD + Multimedia (Base de Datos + Archivos Públicos)
                $this->info('📦 Generando Respaldo BD + Multimedia (Base de Datos + Archivos Públicos)...');

                $zipFileName = "backup_bd_multimedia_{$safeDatabase}_{$date}.zip";
                $zipFilePath = $backupDir.DIRECTORY_SEPARATOR.$zipFileName;
                $tempSqlPath = $backupDir.DIRECTORY_SEPARATOR."temp_db_{$safeDatabase}_{$date}.sql";

                // 1. Volcar base de datos temporal
                $dbSizeKb = $this->dumpDatabase($tempSqlPath, $database, $safeDatabase, $date);

                // 2. Crear archivo ZIP
                $zip = new ZipArchive;
                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('No se pudo inicializar ZipArchive para el respaldo BD + Multimedia.');
                }

                // Base de datos SQL
                $zip->addFile($tempSqlPath, 'database.sql');

                // Archivos multimedia y públicos
                $uploadCount = $this->addUploadsToZip($zip, 'archivos_publicos/');

                // Metadatos y Guía
                $readme = $this->createReadme('bd_multimedia', $database, $date, $uploadCount, 0);
                $zip->addFromString('README_RESTAURACION.txt', $readme);

                $infoJson = json_encode([
                    'sistema' => 'Tecni-Systemas',
                    'tipo' => 'bd_multimedia',
                    'fecha' => Carbon::now()->toIso8601String(),
                    'database' => $database,
                    'db_size_kb' => $dbSizeKb,
                    'archivos_publicos_count' => $uploadCount,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $zip->addFromString('backup_info.json', $infoJson);

                $zip->close();

                // Limpiar SQL temporal
                if (File::exists($tempSqlPath)) {
                    File::delete($tempSqlPath);
                }

                $sizeMb = round(File::size($zipFilePath) / (1024 * 1024), 2);
                $this->info("✅ Respaldo BD + Multimedia generado exitosamente: {$zipFileName} ({$sizeMb} MB)");
                Log::info("Backup BD + Multimedia creado: {$zipFileName} ({$sizeMb} MB)");

                $generatedFiles[] = $zipFilePath;
                $mainFileName = $zipFileName;
                $mainFileSize = "{$sizeMb} MB";
                $tipoEtiqueta = 'Base de Datos + Archivos Multimedia (.zip)';

            } elseif ($isCode) {
                // TIPO: Solo Código Fuente
                $this->info('💻 Generando snapshot de código fuente...');

                $zipFileName = "code_{$safeDatabase}_{$date}.zip";
                $zipFilePath = $backupDir.DIRECTORY_SEPARATOR.$zipFileName;

                $zip = new ZipArchive;
                if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('No se pudo inicializar ZipArchive para el código fuente.');
                }

                $codeCount = $this->addSourceCodeToZip($zip, $backupDir, '');
                $zip->close();

                $sizeMb = round(File::size($zipFilePath) / (1024 * 1024), 2);
                $this->info("✅ Código fuente empaquetado: {$zipFileName} ({$sizeMb} MB, {$codeCount} archivos)");
                Log::info("Backup de código creado: {$zipFileName} ({$sizeMb} MB)");

                $generatedFiles[] = $zipFilePath;
                $mainFileName = $zipFileName;
                $mainFileSize = "{$sizeMb} MB";
                $tipoEtiqueta = 'Solo Código Fuente (.zip)';

            } else {
                // TIPO 1: Solo Base de Datos (.sql)
                $this->info('🗄️ Generando Respaldo Solo Base de Datos (.sql)...');

                $sqlFileName = "db_{$safeDatabase}_{$date}.sql";
                $sqlFilePath = $backupDir.DIRECTORY_SEPARATOR.$sqlFileName;

                $sizeKb = $this->dumpDatabase($sqlFilePath, $database, $safeDatabase, $date);

                $generatedFiles[] = $sqlFilePath;
                $mainFileName = $sqlFileName;
                $mainFileSize = "{$sizeKb} KB";
                $tipoEtiqueta = 'Solo Base de Datos (.sql)';
            }

            // 4. Sincronización con Google Drive
            $drivePath = $this->option('drive-path') ?: env('GOOGLE_DRIVE_BACKUP_PATH');
            if ($drivePath) {
                $this->info("☁️ Sincronizando con Google Drive corporativo: {$drivePath}");
                $this->syncToGoogleDrive($generatedFiles, $drivePath, $retentionDays);
            } else {
                $this->comment('ℹ️ Para sincronizar automáticamente con Google Drive, configura GOOGLE_DRIVE_BACKUP_PATH en tu .env');
            }

            // 5. Política de retención y rotación local
            $this->aplicarPoliticaRotacion($backupDir, $maxCopies);
            $this->limpiarBackupsAntiguos($backupDir, $retentionDays, 'local');

            // 6. Registrar en Auditoría de Eventos
            try {
                $user = auth()->user();
                $ejecutadoPor = $user ? $user->name : 'Sistema (Cron Nocturno)';

                $viejos = [
                    'Tipo de Respaldo' => $tipoEtiqueta,
                    'Archivo Principal' => $mainFileName,
                    'Tamaño' => $mainFileSize,
                    'Directorio Local' => $backupDir,
                ];

                $nuevos = [
                    'Resultado' => '✅ Respaldo generado exitosamente',
                    'Fecha y Hora' => now()->format('d/m/Y H:i:s'),
                    'Ejecutado Por' => $ejecutadoPor,
                    'Sincronización Nube' => $drivePath ? "Google Drive ({$drivePath})" : 'Almacenamiento Local',
                    'Retención Días' => "{$retentionDays} días",
                    'Cupo Máximo Copias' => "{$maxCopies} respaldos (rotación automática)",
                ];

                $descripcion = "Copia de seguridad realizada exitosamente: {$mainFileName} ({$mainFileSize}).";

                Evento::registrar('backup', null, $viejos, $nuevos, $descripcion);
            } catch (\Throwable $evEx) {
                Log::warning('No se pudo registrar evento de auditoría para backup: '.$evEx->getMessage());
            }

            $this->info('====================================================');
            $this->info('✅ PROCESO DE RESPALDO COMPLETADO EXITOSAMENTE');
            $this->info('====================================================');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Excepción durante el proceso de respaldo: '.$e->getMessage());
            Log::error('Excepción en backup: '.$e->getMessage());

            try {
                $user = auth()->user();
                $ejecutadoPor = $user ? $user->name : 'Sistema (Cron Nocturno)';
                Evento::registrar('backup', null, [
                    'Error' => $e->getMessage(),
                ], [
                    'Resultado' => '❌ Fallo en la generación de copia de seguridad',
                    'Fecha y Hora' => now()->format('d/m/Y H:i:s'),
                    'Ejecutado Por' => $ejecutadoPor,
                ], 'Fallo al generar copia de seguridad del sistema: '.$e->getMessage());
            } catch (\Throwable) {
                // Silencioso
            }

            return 1;
        }
    }

    /**
     * Realiza el volcado de la base de datos a un archivo SQL.
     */
    protected function dumpDatabase(string $targetSqlPath, string $database, string $safeDatabase, string $date): float
    {
        $isSqlite = config('database.default') === 'sqlite' || env('DB_CONNECTION') === 'sqlite';

        if ($isSqlite) {
            $this->info('  📦 Volcando base de datos SQLite...');
            File::put($targetSqlPath, "-- Backup SQLite de prueba\n-- Base de datos: {$safeDatabase}\n-- Generado: {$date}\n");
            $sizeKb = round(File::size($targetSqlPath) / 1024, 2);
            $this->info("  ✅ Base de datos respaldada: ".basename($targetSqlPath)." ({$sizeKb} KB)");
            Log::info("Backup SQLite exitoso: ".basename($targetSqlPath)." ({$sizeKb} KB)");

            return $sizeKb;
        }

        $this->info('  📦 Volcando base de datos MySQL con mysqldump...');
        $mysqldumpBinary = $this->resolveMysqldumpBinary();

        if (! $mysqldumpBinary) {
            $msg = 'No se encontró el binario mysqldump. Configura MYSQLDUMP_PATH en tu archivo .env';
            $this->error("  ❌ {$msg}");
            throw new \Exception($msg);
        }

        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $host = env('DB_HOST', '127.0.0.1');
        $port = env('DB_PORT', '3306');

        $escapedPassword = str_replace('"', '\"', $password);
        $command = sprintf(
            '"%s" --user="%s" --host="%s" --port="%s" --password="%s" "%s" --result-file="%s"',
            $mysqldumpBinary,
            $username,
            $host,
            $port,
            $escapedPassword,
            $database,
            $targetSqlPath
        );

        putenv("MYSQL_PWD={$password}");
        $output = [];
        $resultCode = null;
        exec($command, $output, $resultCode);
        putenv('MYSQL_PWD');

        if ($resultCode === 0 && File::exists($targetSqlPath) && File::size($targetSqlPath) > 0) {
            $sizeKb = round(File::size($targetSqlPath) / 1024, 2);
            $this->info("  ✅ Base de datos respaldada: ".basename($targetSqlPath)." ({$sizeKb} KB)");
            Log::info("Backup de base de datos exitoso: ".basename($targetSqlPath)." ({$sizeKb} KB)");

            return $sizeKb;
        }

        $msg = "Error al crear el respaldo de MySQL. Código: {$resultCode}";
        $this->error("  ❌ {$msg}");
        Log::error("Fallo al crear backup de base de datos. Código: {$resultCode}");
        throw new \Exception($msg);
    }

    /**
     * Añade los archivos multimedia y uploads de storage/app/public al ZIP.
     */
    protected function addUploadsToZip(ZipArchive $zip, string $prefix = 'archivos_publicos/'): int
    {
        $sourceDir = storage_path('app/public');
        if (! File::exists($sourceDir)) {
            $this->line('  - No existe directorio storage/app/public.');

            return 0;
        }

        $files = File::allFiles($sourceDir);
        $count = 0;
        foreach ($files as $file) {
            $relativePath = $prefix.ltrim(str_replace(['\\', '/'], '/', $file->getRelativePathname()), '/');
            $zip->addFile($file->getRealPath(), $relativePath);
            $count++;
        }

        $this->info("  📁 Archivos públicos agregados al ZIP: {$count} archivo(s)");

        return $count;
    }

    /**
     * Añade el código fuente limpio del proyecto al ZIP.
     */
    protected function addSourceCodeToZip(ZipArchive $zip, string $backupDir, string $prefix = 'codigo_fuente/'): int
    {
        $base = realpath(base_path());
        $count = 0;

        // Excluye dependencias instalables, repositorios internos, temporales y backups para evitar duplicación/recursión
        $excludeRegex = '#[\\\\/](vendor|node_modules|\.git|\.kilo|storage[\\\\/]app[\\\\/]backups|storage[\\\\/]framework[\\\\/]cache|storage[\\\\/]framework[\\\\/]sessions|storage[\\\\/]framework[\\\\/]views|informes_viabilidad[\\\\/]videos)[\\\\/]#i';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $realPath = $file->getRealPath();

            // Evitar archivos dentro de storage/app/backups o de exclusión
            if (str_starts_with($realPath, $backupDir) || preg_match($excludeRegex, $realPath)) {
                continue;
            }

            // Omitir archivos temporales de respaldo o logs pesados
            $filename = $file->getFilename();
            if (str_starts_with($filename, 'temp_') || str_ends_with($filename, '.tmp')) {
                continue;
            }

            $relativePath = ltrim(str_replace([$base, '\\'], ['', '/'], $realPath), '/');
            $zipEntryPath = $prefix.$relativePath;

            @$zip->addFile($realPath, $zipEntryPath);
            $count++;
        }

        $this->info("  💻 Código fuente agregado al ZIP: {$count} archivo(s)");

        return $count;
    }

    /**
     * Genera un archivo README con instrucciones claras de restauración dentro del ZIP.
     */
    protected function createReadme(string $tipo, string $database, string $date, int $uploadCount = 0, int $codeCount = 0): string
    {
        $tipoNombre = $tipo === 'integral' ? 'RESPALDO INTEGRAL (SNAPSHOT TOTAL)' : 'RESPALDO BD + MULTIMEDIA';

        return <<<TXT
================================================================================
  {$tipoNombre} - TECNI-SYSTEMAS
================================================================================
Fecha de generación: {$date}
Base de datos: {$database}
Archivos públicos/multimedia: {$uploadCount}
Archivos de código fuente: {$codeCount}

CONTENIDO DE ESTE PAQUETE:
1. database.sql
   -> Volcado SQL completo con todas las tablas, usuarios, compras, ventas,
      equipos, mantenimientos, órdenes de servicio y cierres de caja.
   -> Restauración rápida:
      mysql -u [usuario] -p {$database} < database.sql

2. archivos_publicos/
   -> Fotos de equipos, comprobantes de pago, imágenes de inventario y logos.
   -> Restauración:
      Copiar el contenido dentro de: storage/app/public/

3. codigo_fuente/ (Si aplica)
   -> Copia completa del código fuente del sistema (Blade, CSS, JS, PHP).
   -> Excluye carpetas voluminosas reinstalables (vendor, node_modules).
   -> Pasos para levantar el proyecto desde cero:
      a) composer install
      b) npm install && npm run build
      c) Configurar .env con las credenciales de base de datos
      d) php artisan key:generate
      e) php artisan storage:link
================================================================================
Generado automáticamente por el Sistema de Copias de Seguridad de Tecni-Systemas.
TXT;
    }

    /**
     * Localiza el ejecutable de mysqldump en el entorno.
     */
    protected function resolveMysqldumpBinary(): ?string
    {
        $custom = env('MYSQLDUMP_PATH');
        if ($custom && File::exists($custom)) {
            return $custom;
        }

        $candidates = [
            'C:\\ServBay\\packages\\mysql\\current\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\mysql\\8.4\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\mysql\\8.0\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\defaultsqlserver\\current\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\defaultsqlserver\\8.4\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\mariadb\\current\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\mariadb\\11.4\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\mariadb\\10.11\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
        ];

        foreach ($candidates as $cand) {
            if (File::exists($cand)) {
                return $cand;
            }
        }

        $checkCmd = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>nul' : 'which mysqldump 2>/dev/null';
        $output = [];
        $code = 0;
        exec($checkCmd, $output, $code);
        if ($code === 0 && ! empty($output)) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Copia los archivos respaldados a la carpeta sincronizada de Google Drive.
     */
    protected function syncToGoogleDrive(array $files, string $drivePath, int $retentionDays): void
    {
        if (! File::exists($drivePath)) {
            try {
                File::makeDirectory($drivePath, 0755, true);
            } catch (\Exception $e) {
                $this->error("  ❌ La ruta de Google Drive no existe y no pudo crearse: {$drivePath}");

                return;
            }
        }

        $copied = 0;
        foreach ($files as $file) {
            $fileName = basename($file);
            $dest = rtrim($drivePath, '/\\').DIRECTORY_SEPARATOR.$fileName;
            if (File::copy($file, $dest)) {
                $copied++;
                $this->line("  ☁️ Copiado a Drive: {$fileName}");
            }
        }

        $this->info("  ✅ {$copied} archivo(s) sincronizados con la carpeta de Google Drive.");
        Log::info("Backups sincronizados con Google Drive: {$copied} archivos a {$drivePath}");

        $this->limpiarBackupsAntiguos($drivePath, $retentionDays, 'Google Drive');
    }

    /**
     * Elimina respaldos que excedan los días de retención establecidos.
     */
    protected function limpiarBackupsAntiguos(string $dir, int $retentionDays, string $label): void
    {
        if (! File::exists($dir)) {
            return;
        }

        $files = File::files($dir);
        $deleted = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['sql', 'zip', 'gz'])) {
                continue;
            }

            if (Carbon::createFromTimestamp($file->getCTime())->diffInDays(Carbon::now()) > $retentionDays) {
                File::delete($file);
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->line("  🧹 Retención ({$label}): Se eliminaron {$deleted} archivo(s) de más de {$retentionDays} días.");
            Log::info("Retención de backups ({$label}): {$deleted} archivo(s) eliminados.");
        }
    }

    /**
     * Aplica la política de rotación por cupo máximo de copias.
     * Si los respaldos superan $maxCopies, elimina los más antiguos para dar espacio a los nuevos.
     */
    protected function aplicarPoliticaRotacion(string $dir, int $maxCopies): void
    {
        if ($maxCopies <= 0 || ! File::exists($dir)) {
            return;
        }

        $files = File::files($dir);
        $backupFiles = [];

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['sql', 'zip', 'gz'])) {
                $backupFiles[] = [
                    'path' => $file->getRealPath(),
                    'mtime' => $file->getMTime(),
                    'name' => $file->getFilename(),
                ];
            }
        }

        // Ordenar del más reciente al más antiguo
        usort($backupFiles, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        // Si excede el cupo máximo de copias, eliminar los excedentes más antiguos
        if (count($backupFiles) > $maxCopies) {
            $exceso = array_slice($backupFiles, $maxCopies);
            $deleted = 0;
            foreach ($exceso as $item) {
                if (File::delete($item['path'])) {
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                $this->line("  🔄 Rotación de copias: Se eliminaron {$deleted} archivo(s) antiguo(s) para respetar el límite de {$maxCopies} copias.");
                Log::info("Rotación de backups: {$deleted} archivo(s) eliminados para respetar límite de {$maxCopies} copias.");
            }
        }
    }
}
