<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-db 
                            {--files : Respaldar también los archivos multimedia y uploads (storage/app/public)}
                            {--code : Generar un snapshot empaquetado del código fuente}
                            {--all : Respaldar base de datos, uploads y snapshot de código}
                            {--drive-path= : Ruta manual de sincronización de Google Drive}';

    protected $description = 'Crea un respaldo integral de MySQL, archivos y código, con sincronización automática a Google Drive';

    public function handle()
    {
        $this->info('====================================================');
        $this->info('  SISTEMA DE BACKUPS AUTOMÁTICOS - TECNI-SISTEMAS   ');
        $this->info('====================================================');

        try {
            $database = env('DB_DATABASE', 'tecni_systemas');
            $username = env('DB_USERNAME', 'root');
            $password = env('DB_PASSWORD', '');
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $retentionDays = (int) env('BACKUP_RETENTION_DAYS', 15);

            // Directorio local de almacenamiento
            $backupDir = storage_path('app/backups');
            if (! File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $date = Carbon::now()->format('Y-m-d_H-i-s');
            $generatedFiles = [];

            // 1. Respaldo de Base de Datos MySQL
            $this->info('📦 1. Iniciando respaldo de la base de datos MySQL...');
            $mysqldumpBinary = $this->resolveMysqldumpBinary();

            if (! $mysqldumpBinary) {
                $this->error('❌ No se encontró el binario mysqldump. Configura MYSQLDUMP_PATH en tu archivo .env');

                return 1;
            }

            $sqlFileName = "db_{$database}_{$date}.sql";
            $sqlFilePath = $backupDir.DIRECTORY_SEPARATOR.$sqlFileName;

            $escapedPassword = str_replace('"', '\"', $password);
            $command = sprintf(
                '"%s" --user="%s" --host="%s" --port="%s" --password="%s" "%s" --result-file="%s"',
                $mysqldumpBinary,
                $username,
                $host,
                $port,
                $escapedPassword,
                $database,
                $sqlFilePath
            );

            putenv("MYSQL_PWD={$password}");
            $output = [];
            $resultCode = null;
            exec($command, $output, $resultCode);
            putenv('MYSQL_PWD');

            if ($resultCode === 0 && File::exists($sqlFilePath) && File::size($sqlFilePath) > 0) {
                $sizeKb = round(File::size($sqlFilePath) / 1024, 2);
                $this->info("✅ Base de datos respaldada: {$sqlFileName} ({$sizeKb} KB)");
                Log::info("Backup de base de datos exitoso: {$sqlFileName} ({$sizeKb} KB)");
                $generatedFiles[] = $sqlFilePath;
            } else {
                $this->error("❌ Error al crear el respaldo de MySQL. Código: {$resultCode}");
                Log::error("Fallo al crear backup de base de datos. Código: {$resultCode}");

                return 1;
            }

            // 2. Respaldo de Archivos Multimedia / Subidas si se solicitó
            if ($this->option('files') || $this->option('all')) {
                $this->info('📁 2. Empaquetando archivos multimedia y subidas (storage/app/public)...');
                $filesZip = $this->backupPublicUploads($backupDir, $date);
                if ($filesZip) {
                    $generatedFiles[] = $filesZip;
                }
            }

            // 3. Respaldo del Código Fuente si se solicitó
            if ($this->option('code') || $this->option('all')) {
                $this->info('💻 3. Generando snapshot empaquetado del código fuente...');
                $codeZip = $this->backupSourceCode($backupDir, $date);
                if ($codeZip) {
                    $generatedFiles[] = $codeZip;
                }
            }

            // 4. Sincronización con Google Drive
            $drivePath = $this->option('drive-path') ?: env('GOOGLE_DRIVE_BACKUP_PATH');
            if ($drivePath) {
                $this->info("☁️ 4. Sincronizando con Google Drive corporativo: {$drivePath}");
                $this->syncToGoogleDrive($generatedFiles, $drivePath, $retentionDays);
            } else {
                $this->comment('ℹ️ Para sincronizar automáticamente con Google Drive, configura GOOGLE_DRIVE_BACKUP_PATH en tu .env');
            }

            // 5. Política de retención local
            $this->limpiarBackupsAntiguos($backupDir, $retentionDays, 'local');

            $this->info('====================================================');
            $this->info('✅ PROCESO DE RESPALDO COMPLETADO EXITOSAMENTE');
            $this->info('====================================================');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Excepción durante el proceso de respaldo: '.$e->getMessage());
            Log::error('Excepción en backup: '.$e->getMessage());

            return 1;
        }
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
            'C:\\ServBay\\packages\\mysql\\8.4\\bin\\mysqldump.exe',
            'C:\\ServBay\\packages\\mysql\\8.0\\bin\\mysqldump.exe',
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
     * Comprime la carpeta storage/app/public (fotos de repuestos, logos, comprobantes).
     */
    protected function backupPublicUploads(string $backupDir, string $date): ?string
    {
        $sourceDir = storage_path('app/public');
        if (! File::exists($sourceDir)) {
            $this->line('  - No existe directorio storage/app/public.');

            return null;
        }

        $zipName = "uploads_{$date}.zip";
        $zipPath = $backupDir.DIRECTORY_SEPARATOR.$zipName;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('  ❌ No se pudo inicializar ZipArchive para uploads.');

            return null;
        }

        $files = File::allFiles($sourceDir);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $zip->addFile($file->getRealPath(), $relativePath);
        }

        $zip->close();
        $sizeKb = round(File::size($zipPath) / 1024, 2);
        $this->info("  ✅ Archivos multimedia empaquetados: {$zipName} ({$sizeKb} KB, ".count($files).' archivos)');
        Log::info("Backup de uploads creado: {$zipName} ({$sizeKb} KB)");

        return $zipPath;
    }

    /**
     * Empaqueta el código fuente limpio mediante Git Archive.
     */
    protected function backupSourceCode(string $backupDir, string $date): ?string
    {
        $zipName = "code_{$date}.zip";
        $zipPath = $backupDir.DIRECTORY_SEPARATOR.$zipName;

        $cmd = sprintf('git archive --format=zip HEAD -o "%s"', $zipPath);
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        if ($code === 0 && File::exists($zipPath)) {
            $sizeMb = round(File::size($zipPath) / (1024 * 1024), 2);
            $this->info("  ✅ Código fuente empaquetado: {$zipName} ({$sizeMb} MB)");
            Log::info("Backup de código creado: {$zipName} ({$sizeMb} MB)");

            return $zipPath;
        }

        $this->warn('  ⚠️ No se pudo generar snapshot por Git. Omitiendo respaldo de código.');

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
}
