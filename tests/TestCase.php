<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Las pruebas NUNCA usan el storage real: este proyecto se ejecuta en la misma carpeta
     * que producción, y una prueba de rotación de respaldos llegó a borrar copias reales.
     * Cada proceso de pruebas trabaja en un storage temporal propio (respaldos, archivos
     * públicos, logs y vistas compiladas), que se crea antes de cargar la configuración.
     */
    public function createApplication()
    {
        /** @var Application $app */
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->useStoragePath(self::storageDePruebas());

        $this->traitsUsedByTest = class_uses_recursive(static::class);
        $app->make(Kernel::class)->bootstrap();

        // Nunca copiar a la carpeta externa (Drive) desde una prueba
        $app['config']->set('backup.drive_path', null);

        return $app;
    }

    public static function storageDePruebas(): string
    {
        $raiz = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tecnisystemas-testing-'.getmypid();
        foreach (['app/public', 'app/private', 'app/backups', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $sub) {
            $dir = $raiz.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sub);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        return $raiz;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', function (...$args) {
                return max(...$args);
            });
        }
    }
}
