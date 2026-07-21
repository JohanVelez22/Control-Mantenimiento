<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-db';
    protected $description = 'Crea un respaldo de la base de datos MySQL y lo guarda en storage/app/backups';

    public function handle()
    {
        try {
            $database = env('DB_DATABASE');
            $username = env('DB_USERNAME');
            $password = env('DB_PASSWORD');
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');

            // Asegurar que exista el directorio
            $backupDir = storage_path('app/backups');
            if (!File::exists($backupDir)) {
                File::makeDirectory($backupDir, 0755, true);
            }

            $date = Carbon::now()->format('Y-m-d_H-i-s');
            $fileName = "backup_{$database}_{$date}.sql";
            $filePath = $backupDir . '/' . $fileName;

            // En ServBay (Windows) o Linux, usar MYSQL_PWD evita exponer la contraseña en procesos
            $command = sprintf(
                'mysqldump --user="%s" --host="%s" --port="%s" "%s" > "%s"',
                $username,
                $host,
                $port,
                $database,
                $filePath
            );

            $this->info("Iniciando respaldo de la base de datos...");
            
            // Pasar contraseña por variable de entorno de forma segura
            putenv("MYSQL_PWD={$password}");
            
            $output = null;
            $resultCode = null;
            exec($command, $output, $resultCode);
            
            // Limpiar la variable de entorno inmediatamente después
            putenv("MYSQL_PWD");

            if ($resultCode === 0) {
                $this->info("✅ Respaldo exitoso: {$fileName}");
                Log::info("Backup de base de datos exitoso: {$fileName}");
                
                // Retención: Eliminar backups más antiguos de 15 días
                $this->limpiarBackupsAntiguos($backupDir);
            } else {
                $this->error("❌ Error al crear el respaldo. Código: {$resultCode}");
                Log::error("Fallo al crear backup de base de datos. Código: {$resultCode}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Excepción: " . $e->getMessage());
            Log::error("Excepción en backup de BD: " . $e->getMessage());
        }
    }

    private function limpiarBackupsAntiguos($dir)
    {
        $files = File::files($dir);
        $deleted = 0;
        foreach ($files as $file) {
            if (Carbon::createFromTimestamp($file->getCTime())->diffInDays(Carbon::now()) > 15) {
                File::delete($file);
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $this->line("Se eliminaron {$deleted} backups antiguos (más de 15 días).");
            Log::info("Se eliminaron {$deleted} backups antiguos (más de 15 días).");
        }
    }
}
