<?php

namespace App\Console\Commands;

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
        if (env('APP_ENV') !== 'production') {
            $this->error('❌ APP_ENV no es "production"');
            $warnings++;
        } else {
            $this->info('✅ Entorno en producción');
        }

        // 2. Validar Debug
        if (env('APP_DEBUG') == true) {
            $this->error('❌ APP_DEBUG está activado (¡Peligro! Expone código fuente al fallar)');
            $warnings++;
        } else {
            $this->info('✅ Modo debug desactivado');
        }

        // 3. Validar Contraseñas Críticas
        $adminPass = env('ADMIN_DEFAULT_PASSWORD');
        if (!$adminPass || $adminPass === 'Admin123*') {
            $this->error('❌ ADMIN_DEFAULT_PASSWORD está ausente o usa un valor inseguro por defecto');
            $warnings++;
        } else {
            $this->info('✅ Contraseña de administrador segura detectada');
        }

        $dbPass = env('DB_PASSWORD');
        if (!$dbPass) {
            $this->error('❌ La base de datos no tiene contraseña (DB_PASSWORD vacío)');
            $warnings++;
        } else {
            $this->info('✅ Base de datos protegida con contraseña');
        }

        // 4. Validar Sesión
        $sessionLife = env('SESSION_LIFETIME', 120);
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
