<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de verificación de solo lectura antes de poner el sistema en producción.
 * Cada punto indica qué falta y cómo corregirlo. No modifica nada.
 *
 *   php artisan app:preflight
 */
class PreflightDespliegue extends Command
{
    protected $signature = 'app:preflight';

    protected $description = 'Verifica (solo lectura) si el entorno está listo para producción y explica qué falta';

    /** Contraseñas por defecto publicadas en versiones anteriores de la documentación. */
    private const PASSWORDS_CONOCIDAS = ['Admin123*', 'Tecni123*', 'Tecnico123*', 'Invit123*', 'Invitado123*', 'password', 'secret'];

    private int $errores = 0;

    private int $avisos = 0;

    public function handle(): int
    {
        $this->info('Verificación de pre-despliegue — WolfCore System');
        $this->newLine();

        $this->seccion('Configuración de la aplicación');
        $this->check(config('app.env') === 'production', 'APP_ENV=production', 'APP_ENV es "'.config('app.env').'": las protecciones de comandos destructivos y las cookies seguras dependen de "production".');
        $this->check(! config('app.debug'), 'APP_DEBUG=false', 'APP_DEBUG está activo: un error mostraría código, rutas y consultas a cualquier visitante.');
        $this->check(! empty(config('app.key')), 'APP_KEY configurada', 'Falta APP_KEY: ejecute php artisan key:generate.');
        $this->check(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL con https', 'APP_URL ('.config('app.url').') no usa https.', aviso: true);
        $this->check(config('app.locale') === 'es', 'APP_LOCALE=es', 'APP_LOCALE es "'.config('app.locale').'": los mensajes de validación saldrán en inglés.', aviso: true);
        $canal = config('logging.default');
        $nivelLog = config("logging.channels.{$canal}.level") ?? config('logging.channels.'.(config("logging.channels.{$canal}.channels")[0] ?? 'daily').'.level', 'debug');
        $this->check($nivelLog !== 'debug', 'LOG_LEVEL distinto de debug', 'LOG_LEVEL=debug registra datos de más en producción (use warning).', aviso: true);
        $this->check((bool) config('session.secure') || ! str_starts_with((string) config('app.url'), 'https://'), 'Cookies de sesión seguras', 'SESSION_SECURE_COOKIE debe ser true con https.', aviso: true);

        $this->seccion('Base de datos');
        try {
            DB::connection()->getPdo();
            $this->check(true, 'Conexión a la base de datos ('.config('database.default').')', '');
            $this->check(filled(config('database.connections.'.config('database.default').'.password')) || config('database.default') === 'sqlite', 'Usuario de BD con contraseña', 'La base de datos no tiene contraseña.');
            $this->check(config('database.connections.'.config('database.default').'.username') !== 'root' || config('database.default') === 'sqlite', 'Usuario de BD distinto de root', 'Se usa el usuario root: cree un usuario con permisos solo sobre esta base.', aviso: true);
            $pendientes = $this->migracionesPendientes();
            $this->check($pendientes === 0, 'Migraciones al día', "{$pendientes} migración(es) pendiente(s): respaldo + php artisan app:verificar-migraciones + php artisan migrate --force.");
            $this->revisarPasswordsConocidas();
        } catch (\Throwable $e) {
            $this->check(false, 'Conexión a la base de datos', 'No se pudo conectar: '.$e->getMessage());
        }

        $this->seccion('Archivos y compilación');
        $this->check(File::exists(public_path('build/manifest.json')), 'Interfaz compilada (public/build)', 'Falta compilar la interfaz: npm ci && npm run build.');
        $this->check(File::exists(public_path('vendor/tom-select/tom-select.complete.min.js')), 'Librerías locales (public/vendor)', 'Faltan las librerías de interfaz: npm run vendor:sync.');
        $this->check(File::exists(public_path('storage')), 'Enlace public/storage', 'Falta el enlace de archivos públicos: php artisan storage:link.');
        $this->check(is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'storage y bootstrap/cache con escritura', 'storage/ o bootstrap/cache no tienen permisos de escritura.');
        $this->check(! File::exists(base_path('vendor/laravel/dusk')) || config('app.env') !== 'production', 'Dependencias de desarrollo fuera de producción', 'Laravel Dusk está instalado: en producción use composer install --no-dev.', aviso: true);

        $this->seccion('Respaldos');
        $dirRespaldos = storage_path('app/backups');
        $this->check(! File::exists($dirRespaldos) || is_writable($dirRespaldos), 'Carpeta de respaldos con escritura', "No se puede escribir en {$dirRespaldos}.");
        $mysqldump = config('backup.mysqldump_path');
        $this->check(config('database.default') !== 'mysql' || ($mysqldump && File::exists($mysqldump)) || $this->enPath('mysqldump'), 'mysqldump disponible', 'No se encontró mysqldump: defina MYSQLDUMP_PATH en .env.');
        $ultimo = collect(File::exists($dirRespaldos) ? File::files($dirRespaldos) : [])->max(fn ($f) => $f->getMTime());
        $this->check($ultimo && $ultimo > now()->subDays(2)->timestamp, 'Respaldo reciente (últimas 48 h)', 'No hay respaldos recientes: ejecute php artisan app:backup-db y programe el scheduler (DEPLOY_CHECKLIST §6).', aviso: true);

        $this->newLine();
        if ($this->errores === 0 && $this->avisos === 0) {
            $this->info('✅ Listo para producción.');
        } else {
            $this->line("Resultado: <fg=red>{$this->errores} bloqueante(s)</>, <fg=yellow>{$this->avisos} aviso(s)</>.");
            $this->line('Además, configure el programador de tareas (cron / Programador de Windows) según DEPLOY_CHECKLIST.md §6.');
        }

        return $this->errores === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function revisarPasswordsConocidas(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $expuestos = User::where('active', true)->get(['email', 'password'])
            ->filter(fn ($u) => collect(self::PASSWORDS_CONOCIDAS)->contains(fn ($p) => Hash::check($p, $u->password)))
            ->pluck('email');

        $this->check($expuestos->isEmpty(), 'Ningún usuario con contraseña conocida', 'Usuarios con contraseña publicada en la documentación: '.$expuestos->implode(', ').'. Cámbielas desde Usuarios.');
    }

    private function migracionesPendientes(): int
    {
        $ejecutadas = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration')->all() : [];

        return collect(glob(database_path('migrations/*.php')))
            ->map(fn ($f) => basename($f, '.php'))
            ->reject(fn ($m) => in_array($m, $ejecutadas, true))
            ->count();
    }

    private function enPath(string $binario): bool
    {
        $salida = [];
        exec((PHP_OS_FAMILY === 'Windows' ? 'where ' : 'command -v ').escapeshellarg($binario).' 2>&1', $salida, $codigo);

        return $codigo === 0;
    }

    private function seccion(string $titulo): void
    {
        $this->newLine();
        $this->line("<options=bold>{$titulo}</>");
    }

    private function check(bool $ok, string $titulo, string $problema, bool $aviso = false): void
    {
        if ($ok) {
            $this->line("  <fg=green>✔</> {$titulo}");

            return;
        }

        $aviso ? $this->avisos++ : $this->errores++;
        $marca = $aviso ? '<fg=yellow>⚠</>' : '<fg=red>✘</>';
        $this->line("  {$marca} {$titulo}");
        $this->line("      → {$problema}");
    }
}
