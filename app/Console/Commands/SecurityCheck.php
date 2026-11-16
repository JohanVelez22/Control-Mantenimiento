<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SecurityCheck extends Command
{
    protected $signature = 'security:check';

    protected $description = 'Verifica que la configuración del entorno (.env) sea segura para producción';

    public function handle()
    {
        $this->info('Iniciando auditoría de seguridad de producción...');
        $warnings = 0;

        // 1. Validar entorno
        if (config('app.env') !== 'production') {
            $this->error('❌ APP_ENV no es "production"');
            $warnings++;
        } else {
            $this->info('✅ Entorno en producción');
        }

        // 2. Validar Debug
        if (config('app.debug') == true) {
            $this->error('❌ APP_DEBUG está activado (¡Peligro! Expone código fuente al fallar)');
            $warnings++;
        } else {
            $this->info('✅ Modo debug desactivado');
        }

        // 3. Contraseñas iniciales (S-01 / O-14): en producción deben estar vacías y ninguna
        //    cuenta activa debe tener pendiente el cambio obligatorio de contraseña.
        $iniciales = array_filter((array) config('seguridad.passwords_iniciales', []));
        if ($iniciales) {
            $this->error('❌ Hay contraseñas iniciales definidas en .env ('.implode(', ', array_map(fn ($r) => strtoupper($r).'_DEFAULT_PASSWORD', array_keys($iniciales))).'): vacíelas tras fijar las contraseñas reales');
            $warnings++;
        } else {
            $this->info('✅ Sin contraseñas iniciales en .env');
        }
        $pendientes = User::where('active', true)->where('must_change_password', true)->pluck('email');
        if ($pendientes->isNotEmpty()) {
            $this->error('❌ Cuentas con contraseña inicial o publicada (cambio pendiente): '.$pendientes->implode(', ').'. Use php artisan app:restablecer-password <correo>');
            $warnings++;
        } else {
            $this->info('✅ Ninguna cuenta activa usa la contraseña inicial');
        }

        $dbPass = config('database.connections.'.config('database.default').'.password');
        if (! $dbPass) {
            $this->error('❌ La base de datos no tiene contraseña (DB_PASSWORD vacío)');
            $warnings++;
        } else {
            $this->info('✅ Base de datos protegida con contraseña');
        }

        // 4. Validar Sesión
        $sessionLife = config('session.lifetime', 120);
        if ($sessionLife > 1440) { // Mayor a 24h
            $this->warn('⚠️ SESSION_LIFETIME es mayor a 24 horas. Considere reducirlo por seguridad.');
            $warnings++;
        } else {
            $this->info('✅ Tiempo de vida de sesión seguro');
        }

        $this->line('---------------------------------------');
        if ($warnings > 0) {
            $this->error("Auditoría fallida: {$warnings} advertencias detectadas. Por favor, corríjalas antes de lanzar a producción.");

            return 1;
        }

        $this->info('🏆 Auditoría exitosa: El sistema cumple los estándares de seguridad básicos para producción.');

        return 0;
    }
}
