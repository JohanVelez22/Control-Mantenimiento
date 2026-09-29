<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use App\Models\Evento;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
}
