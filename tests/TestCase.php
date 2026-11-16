<?php

namespace Tests;

use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\User;
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

    // GREATEST() para SQLite lo registra App\Database\SqliteConnector en cada conexión, como
    // función determinista. Registrarla aquí otra vez sin esa marca hacía fallar cualquier
    // ALTER TABLE en las pruebas ("non-deterministic functions prohibited in generated columns").

    /**
     * Para pruebas escritas sobre el comportamiento de SQLite (restauración con un SQL de
     * ejemplo, verificación que no aplica). En MySQL la restauración primero exige un
     * volcado completo verificado en una base temporal: eso lo cubre
     * tests/Integration/RestauracionMysqlTest.
     */
    protected function soloSqlite(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Prueba del flujo con SQLite; en MySQL la cubre RestauracionMysqlTest.');
        }
    }

    /**
     * Efectivo inicial en caja (una «Apertura de caja» con fecha antigua, para no alterar los
     * totales del día). Una salida en efectivo ya no puede dejar la caja en negativo, así que
     * las pruebas que pagan compras o egresos en efectivo registran antes el dinero, como en
     * el local.
     */
    protected function registrarEfectivoEnCaja(float $monto = 10000000): void
    {
        $concepto = ConceptoCaja::firstOrCreate(['nombre' => 'Apertura de caja']);
        MovimientoCaja::create([
            'persona' => 'Base de caja',
            'fecha' => '2000-01-01',
            'concepto_id' => $concepto->id,
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => $monto,
            'estado' => 'activo',
            'anulado' => false,
            'user_id' => User::query()->value('id') ?? User::factory()->create()->id,
        ]);
    }
}
