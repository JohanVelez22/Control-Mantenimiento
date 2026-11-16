<?php

namespace App\Http\Controllers;

use App\Console\Commands\BackupDatabase;
use App\Models\Configuracion;
use App\Models\Evento;
use App\Support\BinariosMysql;
use App\Support\ManifiestoRespaldos;
use App\Support\ModoRestauracion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
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
    /**
     * S-08: descargar, restaurar, borrar un respaldo o actualizar el sistema exige volver a
     * escribir la contraseña del administrador que tiene la sesión abierta. Así una sesión
     * olvidada abierta no basta para llevarse la base completa o reemplazarla.
     * Máximo 5 intentos fallidos cada 15 minutos.
     */
    protected function confirmarClave(Request $request): ?string
    {
        $user = Auth::user();
        $clave = 'reauth-respaldos:'.$user?->id;
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            return 'Demasiados intentos con contraseña incorrecta. Espere unos minutos.';
        }

        $password = $request->input('password');
        if (! is_string($password) || $password === '' || ! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($clave, 15 * 60);

            return 'Confirme la operación con su contraseña (la contraseña no es correcta).';
        }

        RateLimiter::clear($clave);

        return null;
    }

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
            // Carbon 3 crea la fecha en UTC: sin la zona del sistema la lista mostraba 07:25 PM por 02:25 PM.
            $carbon = Carbon::createFromTimestamp($mtime, config('app.timezone'));

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
            Log::error('Excepción al generar respaldo manual: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'Excepción al generar respaldo: '.$e->getMessage()
                    : 'No se pudo generar la copia de seguridad. Verifique los registros del sistema.',
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
    public function download(Request $request, string $filename): BinaryFileResponse|JsonResponse|RedirectResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        // Prevenir path traversal de forma estricta
        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename) || str_contains($filename, '..')) {
            abort(400, 'Nombre de archivo no válido.');
        }

        if ($error = $this->confirmarClave($request)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $error], 403)
                : back()->with('error', $error);
        }

        $dir = $this->getBackupDir();
        $filePath = $dir.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($filePath)) {
            abort(404, 'El archivo de respaldo solicitado no existe.');
        }

        try {
            Evento::registrar('backup', null, null, ['Archivo' => $filename], "Respaldo '{$filename}' descargado.");
        } catch (\Throwable) {
            // La descarga no depende del registro.
        }

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    /**
     * Elimina manualmente una copia de seguridad.
     */
    public function destroy(Request $request, string $filename): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        if (! preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename) || str_contains($filename, '..')) {
            return response()->json(['success' => false, 'message' => 'Nombre de archivo no válido.'], 400);
        }

        if ($error = $this->confirmarClave($request)) {
            return response()->json(['success' => false, 'message' => $error], 403);
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

        if (! app()->environment('local', 'testing')) {
            return response()->json([
                'success' => false,
                'message' => 'Esta operación solo está disponible en entorno local.',
            ], 403);
        }

        $backupDir = $this->getBackupDir();
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $opened = false;

        if (app()->runningUnitTests()) {
            $opened = true;
        } elseif ($isWindows) {
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
                    pclose(popen('explorer.exe /separate,'.escapeshellarg($backupDir), 'r'));
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
     * Ejecuta la restauración de un archivo SQL sobre la base de datos configurada.
     */
    protected function restoreSqlFile(string $sqlFilePath): void
    {
        $defaultConnection = config('database.default');
        $isSqlite = $defaultConnection === 'sqlite';

        if ($isSqlite) {
            $sql = File::get($sqlFilePath);
            if (! empty(trim($sql))) {
                DB::unprepared($sql);
            }

            return;
        }

        $mysqlBinary = BinariosMysql::mysql();
        $database = (string) config('database.connections.mysql.database');

        if ($mysqlBinary && File::exists($mysqlBinary)) {
            // D-10: credenciales en archivo de opciones temporal, no en MYSQL_PWD.
            [$returnCode, $stderr] = BinariosMysql::conCredenciales(config('database.connections.mysql'), function (string $credenciales) use ($mysqlBinary, $database, $sqlFilePath) {
                $process = proc_open(
                    [$mysqlBinary, '--defaults-extra-file='.$credenciales, '--default-character-set=utf8mb4', $database],
                    [0 => ['file', $sqlFilePath, 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes
                );
                if (! is_resource($process)) {
                    return [-1, 'No se pudo iniciar mysql.'];
                }
                stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                return [proc_close($process), (string) $stderr];
            });

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

        if ($error = $this->confirmarClave($request)) {
            return response()->json(['success' => false, 'message' => $error], 403);
        }

        // D-03: un archivo que cambió desde que se creó no se restaura.
        if (ManifiestoRespaldos::verificar($filePath) === false) {
            return response()->json([
                'success' => false,
                'message' => 'Restauración cancelada: la huella SHA-256 del archivo no coincide con la registrada al crearlo (archivo dañado o alterado). La base actual no se modificó.',
            ], 422);
        }

        // La restauración no debe quedar a medias si el navegador se cierra o tarda.
        ignore_user_abort(true);
        @set_time_limit(0);

        // D-01: antes de tocar la base real, el respaldo se restaura en una base TEMPORAL
        //    (app:verificar-respaldo): si el archivo está incompleto ("Dump completed"),
        //    trae USE/CREATE DATABASE o MySQL no lo carga, se cancela sin cambiar nada.
        if (config('database.default') === 'mysql') {
            try {
                $verificacion = Artisan::call('app:verificar-respaldo', ['--archivo' => $filename]);
                $salidaVerificacion = trim(Artisan::output());
            } catch (\Throwable $e) {
                $verificacion = 1;
                $salidaVerificacion = $e->getMessage();
            }
            if ($verificacion !== 0) {
                Log::warning("Restauración cancelada: {$filename} no pasó la verificación en base temporal. {$salidaVerificacion}");

                return response()->json([
                    'success' => false,
                    'message' => 'Restauración cancelada: el respaldo no se pudo restaurar en una base temporal de prueba (archivo incompleto o dañado). La base actual no se modificó.',
                ], 422);
            }
        }

        // 0. Respaldo de seguridad del estado actual: restaurar sobrescribe la base completa y,
        //    si se eligió el archivo equivocado, sin esta copia no habría vuelta atrás.
        //    (Se omite solo con SQLite en memoria, que no se puede volcar: entorno de pruebas.)
        $respaldoSeguridad = null;
        $enMemoria = config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:';
        if (! $enMemoria) {
            $antes = collect(File::files($dir))->map->getFilename()->all();
            try {
                $codigo = Artisan::call('app:backup-db', ['--sin-rotacion' => true]);
            } catch (\Throwable $e) {
                $codigo = 1;
                Log::error('Respaldo de seguridad previo a restaurar falló: '.$e->getMessage());
            }
            if ($codigo !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Restauración cancelada: no se pudo crear el respaldo de seguridad del estado actual.',
                ], 500);
            }
            $respaldoSeguridad = collect(File::files($dir))->map->getFilename()->diff($antes)->first();
        }

        // 1. D-16: los eventos de auditoría se copian a una tabla auxiliar dentro de la misma
        //    base (el respaldo no la toca) y se reintegran con SQL; antes se cargaban todos en
        //    memoria y se comparaban uno a uno, lo que no escalaba.
        $eventosPreservados = $this->preservarEventos();

        // D-01: el resto de usuarios ve "mantenimiento" mientras se reemplaza la base.
        ModoRestauracion::activar($filename, Auth::user()?->name);

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

                // Prevenir Zip Slip: validar cada entrada antes de extraer
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if ($entryName === false) {
                        continue;
                    }
                    if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\') || str_contains($entryName, "\0") || str_contains($entryName, ':')) {
                        $zip->close();
                        File::deleteDirectory($tempExtractDir);

                        return response()->json([
                            'success' => false,
                            'message' => 'El paquete de respaldo contiene rutas de archivo no permitidas (Zip Slip detectado).',
                        ], 400);
                    }
                }

                // S-08: respaldos cifrados con BACKUP_PASSWORD.
                if ($claveZip = BackupDatabase::claveCifrado()) {
                    $zip->setPassword($claveZip);
                }
                if (! $zip->extractTo($tempExtractDir)) {
                    $zip->close();
                    throw new \Exception('No se pudo extraer el respaldo (¿está cifrado con otra clave? Revise BACKUP_PASSWORD).');
                }
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

                // 2. Restaurar Archivos Públicos Multimedia (storage/app/public) con lista blanca estricta
                $publicMediaDir = $tempExtractDir.DIRECTORY_SEPARATOR.'archivos_publicos';
                if (File::isDirectory($publicMediaDir)) {
                    $targetPublicDir = storage_path('app/public');
                    if (! File::exists($targetPublicDir)) {
                        File::makeDirectory($targetPublicDir, 0755, true);
                    }

                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt'];
                    $mediaFiles = File::allFiles($publicMediaDir);
                    $archivosRestauradosCount = 0;

                    foreach ($mediaFiles as $mediaFile) {
                        $ext = strtolower($mediaFile->getExtension());
                        if (! in_array($ext, $allowedExtensions)) {
                            Log::warning('Archivo ignorado en restauración por extensión peligrosa o no permitida: '.$mediaFile->getFilename());

                            continue;
                        }

                        $relPath = $mediaFile->getRelativePathname();
                        $destinationPath = $targetPublicDir.DIRECTORY_SEPARATOR.$relPath;
                        File::ensureDirectoryExists(dirname($destinationPath));
                        File::copy($mediaFile->getRealPath(), $destinationPath);
                        $archivosRestauradosCount++;
                    }
                }

                // Determinar etiqueta descriptiva
                if (str_starts_with($filename, 'backup_integral')) {
                    $tipoDetectado = 'Respaldo Integral (BD + Multimedia; código fuente intacto en ZIP)';
                } else {
                    $tipoDetectado = 'Base de Datos + Multimedia (.zip)';
                }
            }

            // 2. Trazabilidad: reintegrar los eventos que no venían en el respaldo
            if (config('database.default') !== 'sqlite') {
                DB::reconnect();
            }
            $eventosPreservadosCount = $this->reintegrarEventos($eventosPreservados);

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
                        'Eventos Preservados' => $eventosPreservadosCount,
                        'Archivos Multimedia' => $archivosRestauradosCount > 0 ? "{$archivosRestauradosCount} archivos actualizados" : 'N/A',
                    ],
                    "Copia de seguridad '{$filename}' restaurada exitosamente. Se preservó la trazabilidad total de auditoría ({$eventosPreservadosCount} eventos históricos integrados)."
                );
            } catch (\Throwable $evLogEx) {
                Log::warning('Fallo al registrar evento de auditoría de restauración: '.$evLogEx->getMessage());
            }

            $filesData = $this->getBackupFiles();

            $msgSuccess = "La copia de seguridad '{$filename}' fue restaurada exitosamente.";
            if ($respaldoSeguridad) {
                $msgSuccess .= " El estado anterior quedó guardado en '{$respaldoSeguridad}' por si necesita revertir.";
            }
            if (str_starts_with($filename, 'backup_integral')) {
                $msgSuccess .= ' Se restauró la Base de Datos y los Archivos Multimedia, conservando el código fuente en ejecución estable y la trazabilidad de eventos.';
            }

            // D-01: un respaldo de una versión anterior deja la base con un esquema más viejo
            // que el código; se avisa para aplicar las migraciones que falten.
            try {
                $aplicadas = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration')->all() : [];
                $archivos = collect(File::files(database_path('migrations')))
                    ->map(fn ($f) => pathinfo($f->getFilename(), PATHINFO_FILENAME));
                $faltantes = $archivos->diff($aplicadas)->count();
                if ($faltantes > 0) {
                    $msgSuccess .= " ATENCIÓN: el respaldo es de una versión anterior del sistema ({$faltantes} migración(es) sin aplicar). Ejecute en el servidor: php artisan migrate --force";
                    Log::warning("Restauración de {$filename}: {$faltantes} migraciones pendientes tras restaurar.");
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo comprobar la versión del esquema tras restaurar: '.$e->getMessage());
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
                'message' => config('app.debug')
                    ? 'Error al restaurar la copia de seguridad: '.$e->getMessage()
                    : 'Error al restaurar la copia de seguridad. Verifique el archivo e intente nuevamente.',
            ], 500);

        } finally {
            ModoRestauracion::desactivar();
            $this->descartarEventosPreservados();

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

    private const TABLA_EVENTOS_PRESERVADOS = 'eventos_preservados_restauracion';

    /** Copia los eventos actuales a una tabla auxiliar; devuelve cuántos se copiaron. */
    private function preservarEventos(): int
    {
        try {
            if (! Schema::hasTable('eventos')) {
                return 0;
            }
            $this->descartarEventosPreservados();
            DB::statement('CREATE TABLE '.self::TABLA_EVENTOS_PRESERVADOS.' AS SELECT * FROM eventos');

            return (int) DB::table(self::TABLA_EVENTOS_PRESERVADOS)->count();
        } catch (\Throwable $e) {
            Log::warning('No se pudieron preservar los eventos previos a la restauración: '.$e->getMessage());

            return 0;
        }
    }

    /** Inserta los eventos preservados que no trae el respaldo; devuelve cuántos agregó. */
    private function reintegrarEventos(int $preservados): int
    {
        if ($preservados === 0) {
            return 0;
        }
        try {
            if (! Schema::hasTable('eventos') || ! Schema::hasTable(self::TABLA_EVENTOS_PRESERVADOS)) {
                return 0;
            }
            $antes = (int) DB::table('eventos')->count();
            $columnas = 'accion, modelo_tipo, modelo_id, valores_antiguos, valores_nuevos, ip_direccion, user_agent, descripcion, created_at, updated_at';
            DB::statement(
                'INSERT INTO eventos (user_id, '.$columnas.') '
                .'SELECT CASE WHEN EXISTS (SELECT 1 FROM users u WHERE u.id = p.user_id) THEN p.user_id ELSE NULL END, '
                .'p.'.str_replace(', ', ', p.', $columnas).' '
                .'FROM '.self::TABLA_EVENTOS_PRESERVADOS.' p '
                .'WHERE NOT EXISTS (SELECT 1 FROM eventos e WHERE e.created_at = p.created_at AND e.accion = p.accion '
                .'AND (e.descripcion = p.descripcion OR (e.descripcion IS NULL AND p.descripcion IS NULL))) '
                .'ORDER BY p.id'
            );

            return (int) DB::table('eventos')->count() - $antes;
        } catch (\Throwable $e) {
            Log::warning('No se pudieron reintegrar los eventos previos: '.$e->getMessage());

            return 0;
        }
    }

    private function descartarEventosPreservados(): void
    {
        try {
            Schema::dropIfExists(self::TABLA_EVENTOS_PRESERVADOS);
        } catch (\Throwable) {
            // Sin tabla auxiliar no hay nada que limpiar.
        }
    }

    /**
     * Sincroniza el código fuente con GitHub (git pull) para respaldos integrales,
     * ejecuta migraciones de base de datos pendientes y limpia la caché.
     */
    public function gitUpdate(Request $request): JsonResponse
    {
        if ($resp = $this->ensureAdmin()) {
            return $resp;
        }

        if ($error = $this->confirmarClave($request)) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => $error], 403);
        }

        try {
            if (app()->runningUnitTests()) {
                $fakeStatus = $request->input('fake_status', 'up_to_date');
                if ($fakeStatus === 'no_connection') {
                    return response()->json([
                        'success' => false,
                        'status' => 'no_connection',
                        'message' => 'No se pudo establecer conexión con GitHub.',
                    ], 200);
                }
                if ($fakeStatus === 'updated') {
                    return response()->json([
                        'success' => true,
                        'status' => 'updated',
                        'message' => 'Se actualizó correctamente.',
                    ], 200);
                }

                return response()->json([
                    'success' => true,
                    'status' => 'up_to_date',
                    'message' => 'Nada por hacer.',
                ], 200);
            }

            $basePath = base_path();

            // 1. Probar conectividad con GitHub mediante git fetch
            $fetchOutput = [];
            $fetchCode = -1;
            exec('git -c safe.directory='.escapeshellarg($basePath).' -C '.escapeshellarg($basePath).' fetch origin 2>&1', $fetchOutput, $fetchCode);

            if ($fetchCode !== 0) {
                Log::warning('Git fetch falló: '.implode(' | ', $fetchOutput));

                return response()->json([
                    'success' => false,
                    'status' => 'no_connection',
                    'message' => 'No se pudo establecer conexión con GitHub.',
                ], 200);
            }

            // 2. No actualizar si el servidor tiene cambios locales sin confirmar: un pull
            //    los mezclaría o fallaría a medias dejando el código en un estado intermedio.
            $statusOutput = [];
            exec('git -c safe.directory='.escapeshellarg($basePath).' -C '.escapeshellarg($basePath).' status --porcelain --untracked-files=no 2>&1', $statusOutput);
            if (! empty(array_filter($statusOutput))) {
                Log::warning('Actualización cancelada: hay cambios locales sin confirmar en el servidor.', ['cambios' => $statusOutput]);

                return response()->json([
                    'success' => false,
                    'status' => 'local_changes',
                    'message' => 'El servidor tiene cambios locales sin confirmar. Actualice manualmente para no perderlos.',
                ], 200);
            }

            // 3. Solo avance rápido (fast-forward): nunca crea merges ni reescribe historia
            $pullOutput = [];
            $pullCode = -1;
            exec('git -c safe.directory='.escapeshellarg($basePath).' -C '.escapeshellarg($basePath).' pull --ff-only 2>&1', $pullOutput, $pullCode);
            $outputText = implode("\n", $pullOutput);

            if ($pullCode !== 0) {
                Log::warning('Git pull falló: '.$outputText);

                return response()->json([
                    'success' => false,
                    'status' => 'no_connection',
                    'message' => 'No se pudo establecer conexión con GitHub.',
                ], 200);
            }

            // 3. Evaluar si hubo cambios o ya estaba al día
            if (str_contains($outputText, 'Already up to date.') || str_contains($outputText, 'Ya está actualizado')) {
                return response()->json([
                    'success' => true,
                    'status' => 'up_to_date',
                    'message' => 'Nada por hacer.',
                ], 200);
            }

            // Si trajo cambios: RESPALDO previo, luego migraciones (O-04). Antes se migraba sin
            // respaldo y se informaba éxito aunque la migración fallara.
            try {
                $codigoRespaldo = Artisan::call('app:backup-db', ['--sin-rotacion' => true]);
            } catch (\Throwable $bkEx) {
                $codigoRespaldo = 1;
                Log::error('Respaldo previo a migrar falló: '.$bkEx->getMessage());
            }
            if ($codigoRespaldo !== 0) {
                Artisan::call('optimize:clear');

                return response()->json([
                    'success' => false,
                    'status' => 'backup_failed',
                    'message' => 'El código se actualizó, pero NO se aplicaron las migraciones porque no se pudo generar el respaldo previo. Revise el respaldo y ejecute php artisan migrate --force manualmente.',
                ], 200);
            }

            try {
                $codigoMigracion = Artisan::call('migrate', ['--force' => true]);
                $salidaMigracion = Artisan::output();
            } catch (\Throwable $migEx) {
                $codigoMigracion = 1;
                $salidaMigracion = $migEx->getMessage();
            }
            Artisan::call('optimize:clear');

            if ($codigoMigracion !== 0) {
                Log::error('Migraciones tras git pull fallaron: '.$salidaMigracion);

                return response()->json([
                    'success' => false,
                    'status' => 'migration_failed',
                    'message' => 'El código se actualizó pero las migraciones FALLARON. Hay un respaldo recién generado en Respaldos; revise el log antes de seguir operando.',
                ], 200);
            }

            // Dependencias y pantallas: public/build no está en el repositorio, así que un cambio
            // en estilos o scripts no se veía hasta compilar a mano (y un composer.lock nuevo
            // dejaba clases faltantes). Se hace aquí y, si falla, se dice exactamente qué correr.
            $pasosPendientes = $this->completarActualizacion($basePath);
            if ($pasosPendientes !== []) {
                return response()->json([
                    'success' => false,
                    'status' => 'build_failed',
                    'message' => 'El código y la base de datos se actualizaron, pero falta completar: '
                        .implode(' y ', $pasosPendientes)
                        .'. Ejecútelo en la carpeta del sistema antes de seguir operando.',
                ], 200);
            }

            // Registrar en auditoría
            try {
                $user = Auth::user();
                $userName = $user ? $user->name : 'Administrador';
                Evento::registrar(
                    'git_actualizado',
                    null,
                    ['Repositorio' => 'GitHub origin/main'],
                    ['Resultado' => 'Se actualizó correctamente.', 'Detalle' => $outputText],
                    "El usuario {$userName} actualizó el código fuente exitosamente desde GitHub."
                );
            } catch (\Throwable) {
                // Silencioso
            }

            return response()->json([
                'success' => true,
                'status' => 'updated',
                'message' => 'Se actualizó correctamente.',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Excepción en gitUpdate: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 'no_connection',
                'message' => 'No se pudo establecer conexión con GitHub.',
            ], 200);
        }
    }

    /**
     * Tras un git pull: instala dependencias de PHP si cambió composer.lock y recompila las
     * pantallas si cambiaron estilos, scripts o package.json. Devuelve los comandos que no se
     * pudieron ejecutar (vacío si todo quedó listo).
     *
     * @return list<string>
     */
    private function completarActualizacion(string $basePath): array
    {
        $cambios = [];
        exec('git -c safe.directory='.escapeshellarg($basePath).' -C '.escapeshellarg($basePath).' diff --name-only ORIG_HEAD HEAD 2>&1', $cambios, $codigo);
        if ($codigo !== 0) {
            // Sin ORIG_HEAD no se sabe qué cambió: se hace todo por seguridad.
            $cambios = ['composer.lock', 'package.json'];
        }

        $necesitaComposer = collect($cambios)->contains(fn ($f) => $f === 'composer.lock');
        $necesitaBuild = collect($cambios)->contains(fn ($f) => $f === 'package.json' || $f === 'package-lock.json'
            || $f === 'vite.config.js' || str_starts_with($f, 'resources/css/') || str_starts_with($f, 'resources/js/'));

        @set_time_limit(900);
        $pendientes = [];
        $ejecutar = function (string $comando) use ($basePath): bool {
            $salida = [];
            $codigo = -1;
            $cd = PHP_OS_FAMILY === 'Windows' ? 'cd /d ' : 'cd ';
            exec($cd.escapeshellarg($basePath).' && '.$comando.' 2>&1', $salida, $codigo);
            if ($codigo !== 0) {
                Log::error('Actualización: falló «'.$comando.'»', ['salida' => array_slice($salida, -20)]);
            }

            return $codigo === 0;
        };

        if ($necesitaComposer) {
            $composer = (string) config('services.herramientas.composer', 'composer');
            if (! $ejecutar(escapeshellarg($composer).' install --no-dev --no-interaction --optimize-autoloader')) {
                $pendientes[] = 'composer install --no-dev';
            }
        }
        if ($necesitaBuild) {
            $npm = (string) config('services.herramientas.npm', 'npm');
            if (! $ejecutar(escapeshellarg($npm).' ci --no-audit --no-fund') || ! $ejecutar(escapeshellarg($npm).' run build')) {
                $pendientes[] = 'npm ci && npm run build';
            }
        }

        return $pendientes;
    }
}
