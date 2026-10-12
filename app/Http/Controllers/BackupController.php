<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BackupController extends Controller
{
    /**
     * Valida permisos de administrador.
     */
    protected function ensureAdmin(): ?JsonResponse
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Se requieren permisos de administrador.',
            ], 403);
        }

        return null;
    }

    /**
     * Obtiene la ruta física del directorio de respaldos.
     */
    protected function getBackupDir(): string
    {
        $backupDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/backups'));
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        return $backupDir;
    }

    /**
     * Formatea bytes a una cadena legible (KB, MB, GB).
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Obtiene la lista ordenada de archivos de respaldo.
     */
    protected function getBackupFiles(): array
    {
        $dir = $this->getBackupDir();
        $files = File::files($dir);
        $backupFiles = [];
        $totalBytes = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['sql', 'zip', 'gz'])) {
                continue;
            }

            $size = $file->getSize();
            $totalBytes += $size;
            $mtime = $file->getMTime();
            $carbon = Carbon::createFromTimestamp($mtime);

            $backupFiles[] = [
                'name' => $file->getFilename(),
                'size_bytes' => $size,
                'size_formatted' => $this->formatBytes($size),
                'mtime' => $mtime,
                'fecha' => $carbon->format('d/m/Y h:i A'),
                'fecha_relativa' => $carbon->diffForHumans(),
                'extension' => $ext,
            ];
        }

        // Ordenar del más reciente al más antiguo
        usort($backupFiles, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        return [
            'files' => $backupFiles,
            'total_files' => count($backupFiles),
            'total_size_bytes' => $totalBytes,
            'total_size_formatted' => $this->formatBytes($totalBytes),
            'latest' => $backupFiles[0] ?? null,
        ];
    }

    /**
     * Retorna la configuración de respaldos y el estado actual de los archivos.
     */
    public function getConfig(): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        $config = Configuracion::first() ?? new Configuracion;
        $filesData = $this->getBackupFiles();

        return response()->json([
            'success' => true,
            'config' => [
                'backup_automatico' => (bool) ($config->backup_automatico ?? true),
                'backup_frecuencia' => $config->backup_frecuencia ?? 'daily',
                'backup_hora' => $config->backup_hora ?? '02:00',
                'backup_dia_semana' => $config->backup_dia_semana ?? 'lmv',
                'backup_dia_mes' => (int) ($config->backup_dia_mes ?? 1),
                'backup_max_copias' => (int) ($config->backup_max_copias ?? 10),
                'backup_tipo_incluido' => $config->backup_tipo_incluido ?? 'db',
            ],
            'storage_path' => $this->getBackupDir(),
            'files' => $filesData['files'],
            'total_files' => $filesData['total_files'],
            'total_size' => $filesData['total_size_formatted'],
            'latest' => $filesData['latest'],
        ]);
    }

    /**
     * Ejecuta la generación manual de una copia de seguridad.
     */
    public function createManual(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        $tipo = $request->input('tipo', 'db'); // 'db', 'files', 'all'
        $params = [];

        if ($tipo === 'files') {
            $params['--files'] = true;
        } elseif ($tipo === 'all') {
            $params['--all'] = true;
        }

        try {
            $exitCode = Artisan::call('app:backup-db', $params);

            if ($exitCode === 0) {
                $filesData = $this->getBackupFiles();

                return response()->json([
                    'success' => true,
                    'message' => '¡Copia de seguridad generada con éxito!',
                    'latest' => $filesData['latest'],
                    'files' => $filesData['files'],
                    'total_files' => $filesData['total_files'],
                    'total_size' => $filesData['total_size_formatted'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al ejecutar el comando de respaldo.',
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Excepción al generar respaldo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guarda la configuración de programación automática y retención.
     */
    public function saveSchedule(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        $validated = $request->validate([
            'backup_automatico' => 'required|boolean',
            'backup_frecuencia' => 'required|in:daily,weekly,monthly',
            'backup_hora' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            'backup_dia_semana' => 'required|in:lmv,mjs,lunes_miercoles_viernes,martes_jueves_sabado,sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'backup_dia_mes' => 'required|integer|min:1|max:28',
            'backup_max_copias' => 'required|integer|min:1|max:100',
            'backup_tipo_incluido' => 'required|in:db,files,all',
        ]);

        $diasMap = [
            'lmv' => 'Lunes, Miércoles y Viernes',
            'mjs' => 'Martes, Jueves y Sábado',
            'lunes_miercoles_viernes' => 'Lunes, Miércoles y Viernes',
            'martes_jueves_sabado' => 'Martes, Jueves y Sábado',
            'sunday' => 'Domingo',
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
        ];

        $config = Configuracion::first() ?? new Configuracion;
        $viejos = [
            'Automático' => $config->backup_automatico ? 'Sí' : 'No',
            'Frecuencia' => $config->backup_frecuencia ?? 'daily',
            'Hora' => $config->backup_hora ?? '02:00',
            'Días Semana' => $diasMap[$config->backup_dia_semana] ?? ($config->backup_dia_semana ?? 'Lunes, Miércoles y Viernes'),
            'Límite de Copias' => ($config->backup_max_copias ?? 10).' copias',
            'Tipo Incluido' => $config->backup_tipo_incluido ?? 'db',
        ];

        $config->backup_automatico = $validated['backup_automatico'];
        $config->backup_frecuencia = $validated['backup_frecuencia'];
        $config->backup_hora = $validated['backup_hora'];
        $config->backup_dia_semana = $validated['backup_dia_semana'];
        $config->backup_dia_mes = $validated['backup_dia_mes'];
        $config->backup_max_copias = $validated['backup_max_copias'];
        $config->backup_tipo_incluido = $validated['backup_tipo_incluido'];
        $config->save();

        $nuevos = [
            'Automático' => $config->backup_automatico ? 'Sí' : 'No',
            'Frecuencia' => $config->backup_frecuencia,
            'Hora' => $config->backup_hora,
            'Días Semana' => $diasMap[$config->backup_dia_semana] ?? $config->backup_dia_semana,
            'Día Mes' => $config->backup_dia_mes,
            'Límite de Copias' => "{$config->backup_max_copias} copias (rotación automática)",
            'Tipo Incluido' => $config->backup_tipo_incluido,
        ];

        try {
            Evento::registrar(
                'backup',
                null,
                $viejos,
                $nuevos,
                "Se actualizó la programación de respaldos automáticos: {$config->backup_frecuencia} a las {$config->backup_hora}, cupo de {$config->backup_max_copias} copias."
            );
        } catch (\Throwable $e) {
            // Ignorar fallo de evento
        }

        return response()->json([
            'success' => true,
            'message' => 'Programación de respaldos actualizada correctamente.',
            'config' => [
                'backup_automatico' => (bool) $config->backup_automatico,
                'backup_frecuencia' => $config->backup_frecuencia,
                'backup_hora' => $config->backup_hora,
                'backup_dia_semana' => $config->backup_dia_semana,
                'backup_dia_mes' => $config->backup_dia_mes,
                'backup_max_copias' => $config->backup_max_copias,
                'backup_tipo_incluido' => $config->backup_tipo_incluido,
            ],
        ]);
    }

    /**
     * Descarga de forma segura un archivo de respaldo.
     */
    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        // Prevenir path traversal de forma estricta
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename) || str_contains($filename, '..')) {
            abort(400, 'Nombre de archivo no válido.');
        }

        $dir = $this->getBackupDir();
        $filePath = $dir.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            abort(404, 'El archivo de respaldo solicitado no existe.');
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Elimina manualmente una copia de seguridad.
     */
    public function destroy(string $filename): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename) || str_contains($filename, '..')) {
            return response()->json(['success' => false, 'message' => 'Nombre de archivo no válido.'], 400);
        }

        $dir = $this->getBackupDir();
        $filePath = $dir.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'El archivo no existe o ya fue eliminado.'], 404);
        }

        $size = $this->formatBytes(File::size($filePath));
        File::delete($filePath);

        try {
            Evento::registrar(
                'backup',
                null,
                ['Archivo' => $filename, 'Tamaño' => $size],
                ['Resultado' => 'Archivo eliminado manualmente por el usuario'],
                "Respaldo '{$filename}' ({$size}) eliminado manualmente del almacenamiento."
            );
        } catch (\Throwable $e) {
            // Ignorar
        }

        $filesData = $this->getBackupFiles();

        return response()->json([
            'success' => true,
            'message' => "El respaldo '{$filename}' fue eliminado exitosamente.",
            'files' => $filesData['files'],
            'total_files' => $filesData['total_files'],
            'total_size' => $filesData['total_size_formatted'],
        ]);
    }

    /**
     * Abre la carpeta de almacenamiento de respaldos en el explorador de archivos.
     */
    public function openFolder(): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        $backupDir = $this->getBackupDir();
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $opened = false;

        if ($isWindows) {
            try {
                $folderLeaf = basename($backupDir);
                // Usamos explorer.exe /separate para abrir una ventana nueva e independiente centrada,
                // evitando que Windows reutilice una ventana previa minimizada o agrupada en la barra de tareas.
                // Combinado con SendKeys('%') para desbloquear el Foreground Lockout y AppActivate
                // para sobreponer inmediatamente la ventana al primer plano encima del navegador.
                $psScript = "\$ws = New-Object -ComObject WScript.Shell; Start-Process explorer.exe -ArgumentList '/separate,\"{$backupDir}\"'; for (\$i = 0; \$i -lt 8; \$i++) { Start-Sleep -Milliseconds 150; \$ws.SendKeys('%'); if (\$ws.AppActivate('{$folderLeaf}')) { break; } }";
                $encoded = base64_encode(iconv('UTF-8', 'UTF-16LE', $psScript));
                pclose(popen("start /B powershell.exe -NoProfile -WindowStyle Hidden -EncodedCommand {$encoded}", 'r'));
                $opened = true;
            } catch (\Throwable $e) {
                try {
                    pclose(popen('explorer.exe /separate,' . escapeshellarg($backupDir), 'r'));
                    $opened = true;
                } catch (\Throwable $e2) {
                    $opened = false;
                }
            }
        }

        return response()->json([
            'success' => true,
            'opened' => $opened,
            'path' => $backupDir,
            'message' => $opened
                ? 'Carpeta de respaldos abierta en el Explorador de Windows.'
                : "Ubicación en el servidor: {$backupDir}",
        ]);
    }

    /**
     * Localiza el binario ejecutable de mysql en el servidor.
     */
    protected function resolveMysqlBinary(): ?string
    {
        $custom = env('MYSQL_PATH');
        if ($custom && File::exists($custom)) {
            return $custom;
        }

        $candidates = [
            'C:\\ServBay\\packages\\mysql\\current\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\mysql\\8.4\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\mysql\\8.0\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\defaultsqlserver\\current\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\defaultsqlserver\\8.4\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\mariadb\\current\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\mariadb\\11.4\\bin\\mysql.exe',
            'C:\\ServBay\\packages\\mariadb\\10.11\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            '/usr/bin/mysql',
            '/usr/local/bin/mysql',
        ];

        foreach ($candidates as $cand) {
            if (File::exists($cand)) {
                return $cand;
            }
        }

        $checkCmd = PHP_OS_FAMILY === 'Windows' ? 'where mysql 2>nul' : 'which mysql 2>/dev/null';
        $output = [];
        $code = 0;
        exec($checkCmd, $output, $code);
        if ($code === 0 && ! empty($output)) {
            return trim($output[0]);
        }

        return null;
    }

    /**
     * Ejecuta la restauración de un archivo SQL sobre la base de datos configurada.
     */
    protected function restoreSqlFile(string $sqlFilePath): void
    {
        $defaultConnection = config('database.default');
        $isSqlite = $defaultConnection === 'sqlite' || env('DB_CONNECTION') === 'sqlite';

        if ($isSqlite) {
            $sql = File::get($sqlFilePath);
            if (! empty(trim($sql))) {
                DB::unprepared($sql);
            }

            return;
        }

        $mysqlBinary = $this->resolveMysqlBinary();
        $database = env('DB_DATABASE', config('database.connections.mysql.database', 'tecni_systemas'));
        $username = env('DB_USERNAME', config('database.connections.mysql.username', 'root'));
        $password = env('DB_PASSWORD', config('database.connections.mysql.password', ''));
        $host = env('DB_HOST', config('database.connections.mysql.host', '127.0.0.1'));
        $port = env('DB_PORT', config('database.connections.mysql.port', '3306'));

        if ($mysqlBinary && File::exists($mysqlBinary)) {
            putenv("MYSQL_PWD={$password}");
            $descriptors = [
                0 => ['file', $sqlFilePath, 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $escapedBinary = str_replace('"', '\"', $mysqlBinary);
            $escapedHost = str_replace('"', '\"', $host);
            $escapedUser = str_replace('"', '\"', $username);
            $escapedPort = str_replace('"', '\"', $port);
            $escapedDb = str_replace('"', '\"', $database);

            $cmd = sprintf(
                '"%s" --user="%s" --host="%s" --port="%s" --default-character-set=utf8mb4 "%s"',
                $escapedBinary,
                $escapedUser,
                $escapedHost,
                $escapedPort,
                $escapedDb
            );

            $process = proc_open($cmd, $descriptors, $pipes);
            $stderr = '';
            $returnCode = -1;

            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $returnCode = proc_close($process);
            }
            putenv('MYSQL_PWD');

            if ($returnCode !== 0) {
                throw new \Exception("Error al restaurar MySQL (código {$returnCode}): ".trim($stderr));
            }
        } else {
            // Fallback por PDO si no hay binario mysql CLI
            $sql = File::get($sqlFilePath);
            if (! empty(trim($sql))) {
                DB::unprepared($sql);
            }
        }
    }

    /**
     * Restaura una copia de seguridad seleccionada (.sql o .zip).
     * Conserva todos los eventos de auditoría existentes para preservar la trazabilidad.
     */
    public function restore(Request $request, string $filename): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        // Prevenir path traversal estrictamente
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename) || str_contains($filename, '..')) {
            return response()->json(['success' => false, 'message' => 'Nombre de archivo no válido.'], 400);
        }

        $dir = $this->getBackupDir();
        $filePath = $dir.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'El archivo de respaldo seleccionado no existe.'], 404);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['sql', 'zip'])) {
            return response()->json(['success' => false, 'message' => 'Formato no soportado para restauración. Solo se admiten archivos .sql y .zip.'], 400);
        }

        // 1. Preservar eventos de auditoría previos para garantizar trazabilidad ininterrumpida
        $existingEventos = [];
        try {
            if (Schema::hasTable('eventos')) {
                $existingEventos = DB::table('eventos')
                    ->get()
                    ->map(fn ($e) => (array) $e)
                    ->toArray();
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudieron leer los eventos previos a la restauración: '.$e->getMessage());
        }

        $tipoDetectado = 'Solo Base de Datos (.sql)';
        $archivosRestauradosCount = 0;
        $tempExtractDir = null;

        try {
            if ($extension === 'sql') {
                // RESTAURACIÓN TIPO 1: Solo Base de Datos
                $this->restoreSqlFile($filePath);
                $tipoDetectado = 'Solo Base de Datos (.sql)';
            } elseif ($extension === 'zip') {
                // RESTAURACIÓN TIPO 2 y 3: ZIP (BD + Multimedia o Integral)
                $zip = new ZipArchive;
                if ($zip->open($filePath) !== true) {
                    throw new \Exception('No se pudo abrir el archivo ZIP de respaldo.');
                }

                $tempExtractDir = $dir.DIRECTORY_SEPARATOR.'temp_restore_'.uniqid();
                File::makeDirectory($tempExtractDir, 0755, true);
                $zip->extractTo($tempExtractDir);
                $zip->close();

                // 1. Restaurar Base de Datos desde SQL en el ZIP
                $sqlInside = $tempExtractDir.DIRECTORY_SEPARATOR.'database.sql';
                if (! File::exists($sqlInside)) {
                    // Buscar cualquier archivo .sql dentro de la raíz extraída
                    $sqlFiles = File::glob($tempExtractDir.DIRECTORY_SEPARATOR.'*.sql');
                    $sqlInside = $sqlFiles[0] ?? null;
                }

                if ($sqlInside && File::exists($sqlInside)) {
                    $this->restoreSqlFile($sqlInside);
                } else {
                    throw new \Exception('No se encontró archivo de base de datos (database.sql) dentro del paquete ZIP.');
                }

                // 2. Restaurar Archivos Públicos Multimedia (storage/app/public)
                $publicMediaDir = $tempExtractDir.DIRECTORY_SEPARATOR.'archivos_publicos';
                if (File::isDirectory($publicMediaDir)) {
                    $targetPublicDir = storage_path('app/public');
                    if (! File::exists($targetPublicDir)) {
                        File::makeDirectory($targetPublicDir, 0755, true);
                    }
                    File::copyDirectory($publicMediaDir, $targetPublicDir);
                    $archivosRestauradosCount = count(File::allFiles($targetPublicDir));
                }

                // Determinar etiqueta descriptiva
                if (str_starts_with($filename, 'backup_integral')) {
                    $tipoDetectado = 'Respaldo Integral (BD + Multimedia; código fuente intacto en ZIP)';
                } else {
                    $tipoDetectado = 'Base de Datos + Multimedia (.zip)';
                }
            }

            // 2. Trazabilidad: Re-integrar eventos de auditoría previos
            $eventosPreservadosCount = 0;
            try {
                $isSqlite = config('database.default') === 'sqlite' || env('DB_CONNECTION') === 'sqlite';
                if (! $isSqlite) {
                    DB::reconnect();
                }
                if (Schema::hasTable('eventos') && ! empty($existingEventos)) {
                    $restoredEvents = DB::table('eventos')->get();
                    $existingUserIds = Schema::hasTable('users')
                        ? DB::table('users')->pluck('id')->all()
                        : [];

                    foreach ($existingEventos as $oldEv) {
                        $found = $restoredEvents->first(function ($r) use ($oldEv) {
                            return $r->created_at == $oldEv['created_at']
                                && $r->accion == $oldEv['accion']
                                && $r->descripcion == $oldEv['descripcion'];
                        });

                        if (! $found) {
                            $evToInsert = $oldEv;
                            unset($evToInsert['id']); // Permite generación de ID auto-incremental
                            if (! empty($evToInsert['user_id']) && ! in_array($evToInsert['user_id'], $existingUserIds)) {
                                $evToInsert['user_id'] = null;
                            }
                            DB::table('eventos')->insert($evToInsert);
                            $eventosPreservadosCount++;
                        }
                    }
                }
            } catch (\Throwable $evReEx) {
                Log::warning('No se pudieron re-insertar los eventos previos: '.$evReEx->getMessage());
            }

            // 3. Registrar Evento de Auditoría de la Restauración
            try {
                $user = Auth::user();
                $userName = $user ? $user->name : 'Administrador';

                Evento::registrar(
                    'backup_restaurado',
                    null,
                    [
                        'Archivo' => $filename,
                        'Tipo de Copia' => $tipoDetectado,
                        'Tamaño' => $this->formatBytes(File::size($filePath)),
                    ],
                    [
                        'Resultado' => '✅ Restauración completada exitosamente',
                        'Fecha y Hora' => now()->format('d/m/Y H:i:s'),
                        'Restaurado Por' => $userName,
                        'Eventos Preservados' => count($existingEventos),
                        'Archivos Multimedia' => $archivosRestauradosCount > 0 ? "{$archivosRestauradosCount} archivos actualizados" : 'N/A',
                    ],
                    "Copia de seguridad '{$filename}' restaurada exitosamente. Se preservó la trazabilidad total de auditoría ({$eventosPreservadosCount} eventos históricos integrados)."
                );
            } catch (\Throwable $evLogEx) {
                Log::warning('Fallo al registrar evento de auditoría de restauración: '.$evLogEx->getMessage());
            }

            $filesData = $this->getBackupFiles();

            $msgSuccess = "La copia de seguridad '{$filename}' fue restaurada exitosamente.";
            if (str_starts_with($filename, 'backup_integral')) {
                $msgSuccess .= ' Se restauró la Base de Datos y los Archivos Multimedia, conservando el código fuente en ejecución estable y la trazabilidad de eventos.';
            }

            return response()->json([
                'success' => true,
                'message' => $msgSuccess,
                'restored_file' => $filename,
                'tipo' => $tipoDetectado,
                'files' => $filesData['files'],
                'total_files' => $filesData['total_files'],
                'total_size' => $filesData['total_size_formatted'],
            ]);

        } catch (\Throwable $e) {
            Log::error("Fallo al restaurar respaldo '{$filename}': ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la copia de seguridad: '.$e->getMessage(),
            ], 500);

        } finally {
            // Limpiar directorio temporal si existe
            if ($tempExtractDir && File::isDirectory($tempExtractDir)) {
                try {
                    File::deleteDirectory($tempExtractDir);
                } catch (\Throwable) {
                    // Silencioso
                }
            }
        }
    }
}
