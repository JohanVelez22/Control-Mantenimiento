<?php

namespace Tests\Integration;

use App\Models\ConceptoCaja;
use App\Models\Evento;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Support\ManifiestoRespaldos;
use App\Support\ModoRestauracion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Respaldo y restauración de punta a punta en MySQL real (volcado con mysqldump, cifrado,
 * verificación en base temporal, restauración por la web y conservación del historial).
 *
 * Necesita una base MySQL desechable: no usa transacciones (mysqldump y mysql trabajan en
 * otras conexiones). Está fuera de la suite normal (grupo mysql-aislado); se ejecuta sola:
 *   DB_CONNECTION=mysql DB_DATABASE=<base temporal> vendor/bin/phpunit --group mysql-aislado
 */
#[Group('mysql-aislado')]
class RestauracionMysqlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql' || ! str_starts_with((string) DB::getDatabaseName(), 'tecni_test_')) {
            $this->markTestSkipped('Solo en una base MySQL temporal tecni_test_*.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);
        File::deleteDirectory(storage_path('app/backups'));
        File::ensureDirectoryExists(storage_path('app/backups'));
    }

    public function test_respaldo_cifrado_y_restauracion_por_la_web_en_mysql(): void
    {
        config(['backup.password' => 'ClaveRespaldoPrueba2026']);
        $admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $orden = Mantenimiento::factory()->create(['costo' => 80000, 'anulado' => false, 'estado' => 'pendiente', 'fecha_entrada' => now()->toDateString(), 'fecha_salida' => null]);
        $this->actingAs($admin)->post(route('abonos.store', $orden), ['monto' => 30000, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo'])
            ->assertSessionHasNoErrors();

        // 1. Respaldo real (mysqldump) cifrado, con huella en el manifiesto.
        $this->assertSame(0, Artisan::call('app:backup-db', ['--sin-rotacion' => true]));
        $respaldo = collect(File::files(storage_path('app/backups')))->map->getFilename()
            ->first(fn ($n) => str_starts_with($n, 'db_') && str_ends_with($n, '.zip'));
        $this->assertNotNull($respaldo);
        $this->assertTrue(ManifiestoRespaldos::verificar(storage_path('app/backups/'.$respaldo)));

        // 2. Se verifica restaurándolo en otra base temporal.
        $this->assertSame(0, Artisan::call('app:verificar-respaldo', ['--archivo' => $respaldo]), Artisan::output());

        // 3. Cambios posteriores al respaldo: un movimiento (debe desaparecer) y un evento (debe conservarse).
        MovimientoCaja::create([
            'persona' => 'Posterior', 'fecha' => now()->toDateString(), 'concepto_id' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros'])->id,
            'tipo_movimiento' => 'ingreso', 'tipo_pago' => 'efectivo', 'monto' => 999, 'estado' => 'activo', 'anulado' => false, 'user_id' => $admin->id,
        ]);
        Evento::registrar('evento_posterior_al_respaldo', null, null, null, 'Debe sobrevivir a la restauración');

        // 4. Restauración por la web, con contraseña.
        $this->postJson(route('backups.restore', $respaldo), ['password' => 'password'])
            ->assertOk()->assertJson(['success' => true]);

        DB::reconnect();
        $this->assertSame(0, MovimientoCaja::where('persona', 'Posterior')->count(), 'El movimiento posterior al respaldo no existe tras restaurar');
        $this->assertEquals(30000, (float) MovimientoCaja::whereNotNull('abono_id')->sum('monto'));
        $this->assertSame(1, Evento::where('accion', 'evento_posterior_al_respaldo')->count(), 'El historial posterior se conserva');
        $this->assertSame(1, Evento::where('accion', 'backup_restaurado')->count());
        $this->assertFalse(ModoRestauracion::activo());
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('eventos_preservados_restauracion'));
    }
}
