<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use App\Support\BinariosMysql;
use App\Support\ManifiestoRespaldos;
use App\Support\ModoRestauracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

/**
 * S-08 / D-01 / D-03 / D-10 / D-16: seguridad y robustez de los respaldos.
 */
class RespaldosSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false]);
        $this->dir = storage_path('app/backups');
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        ModoRestauracion::desactivar();
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function archivo(string $nombre, string $contenido = "-- respaldo\nSELECT 1;\n"): string
    {
        File::put($this->dir.DIRECTORY_SEPARATOR.$nombre, $contenido);

        return $nombre;
    }

    // ── S-08: reautenticación ───────────────────────────────────────────────────────

    public function test_operaciones_de_respaldo_exigen_la_contrasena(): void
    {
        $nombre = $this->archivo('db_prueba_2026-11-16_10-00-00.sql');
        $this->actingAs($this->admin);

        $this->postJson(route('backups.restore', $nombre))->assertForbidden();
        $this->postJson(route('backups.restore', $nombre), ['password' => 'incorrecta'])->assertForbidden();
        $this->deleteJson(route('backups.destroy', $nombre), ['password' => 'incorrecta'])->assertForbidden();
        $this->postJson(route('backups.download', $nombre), ['password' => 'incorrecta'])->assertForbidden();
        $this->postJson(route('backups.git-update'), ['password' => 'incorrecta', 'fake_status' => 'updated'])->assertForbidden();

        $this->assertTrue(File::exists($this->dir.DIRECTORY_SEPARATOR.$nombre));
    }

    public function test_cinco_contrasenas_incorrectas_bloquean_temporalmente(): void
    {
        $nombre = $this->archivo('db_prueba_2026-11-16_10-00-00.sql');
        $this->actingAs($this->admin);

        for ($i = 0; $i < 5; $i++) {
            $this->deleteJson(route('backups.destroy', $nombre), ['password' => 'mala']);
        }
        $this->deleteJson(route('backups.destroy', $nombre), ['password' => 'password'])
            ->assertForbidden()->assertJsonFragment(['message' => 'Demasiados intentos con contraseña incorrecta. Espere unos minutos.']);
    }

    // ── D-01: mantenimiento durante la restauración ─────────────────────────────────

    public function test_mientras_se_restaura_los_demas_ven_mantenimiento(): void
    {
        ModoRestauracion::activar('db_x.sql', 'Admin');

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertStatus(503)->assertSee('Se está restaurando una copia de seguridad');
        $this->getJson(route('dashboard'))->assertStatus(503);

        ModoRestauracion::desactivar();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_el_bloqueo_de_restauracion_vence_solo(): void
    {
        ModoRestauracion::activar('db_x.sql', 'Admin');
        $this->travel(ModoRestauracion::MINUTOS_MAXIMOS + 1)->minutes();

        $this->assertFalse(ModoRestauracion::activo());
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
    }

    // ── D-16: el historial sobrevive a la restauración ──────────────────────────────

    public function test_restaurar_conserva_los_eventos_posteriores_al_respaldo_y_libera_el_bloqueo(): void
    {
        Evento::registrar('evento_reciente', null, null, null, 'Ocurrió después del respaldo');
        // El "respaldo" vacía el historial, como haría un volcado anterior a estos eventos.
        $nombre = $this->archivo('db_prueba_2026-11-16_10-00-00.sql', "DELETE FROM eventos;\n");

        $this->actingAs($this->admin)->postJson(route('backups.restore', $nombre), ['password' => 'password'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('eventos', ['accion' => 'evento_reciente']);
        $this->assertDatabaseHas('eventos', ['accion' => 'backup_restaurado']);
        $this->assertFalse(ModoRestauracion::activo());
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('eventos_preservados_restauracion'));
    }

    // ── S-08 / D-03: cifrado y manifiesto ───────────────────────────────────────────

    public function test_con_clave_el_respaldo_queda_cifrado_y_con_huella(): void
    {
        config(['backup.password' => 'ClaveDeRespaldo2026']);

        $this->assertSame(0, Artisan::call('app:backup-db', ['--sin-rotacion' => true]));

        $zipNombre = collect(File::files($this->dir))->map->getFilename()->first(fn ($n) => str_starts_with($n, 'db_') && str_ends_with($n, '.zip'));
        $this->assertNotNull($zipNombre, 'Con BACKUP_PASSWORD el respaldo de BD debe ser un ZIP cifrado');
        $ruta = $this->dir.DIRECTORY_SEPARATOR.$zipNombre;

        $sinClave = new ZipArchive;
        $sinClave->open($ruta);
        $this->assertFalse($sinClave->getFromName('database.sql'), 'Sin la clave no se puede leer');
        $sinClave->close();

        $conClave = new ZipArchive;
        $conClave->open($ruta);
        $conClave->setPassword('ClaveDeRespaldo2026');
        $this->assertStringContainsString('Backup SQLite', (string) $conClave->getFromName('database.sql'));
        $conClave->close();

        $this->assertTrue(ManifiestoRespaldos::verificar($ruta));
        $this->assertTrue(ManifiestoRespaldos::cifrado($ruta));
    }

    public function test_archivo_alterado_despues_de_crearse_no_se_restaura(): void
    {
        $nombre = $this->archivo('db_prueba_2026-11-16_10-00-00.sql');
        ManifiestoRespaldos::registrar($this->dir.DIRECTORY_SEPARATOR.$nombre);
        File::append($this->dir.DIRECTORY_SEPARATOR.$nombre, "DROP TABLE users;\n");

        $this->actingAs($this->admin)->postJson(route('backups.restore', $nombre), ['password' => 'password'])
            ->assertStatus(422)->assertJsonFragment(['success' => false]);
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));
    }

    // ── D-10: credenciales temporales ───────────────────────────────────────────────

    public function test_archivo_de_credenciales_se_borra_al_terminar(): void
    {
        $ruta = BinariosMysql::conCredenciales(['username' => 'u', 'password' => 'p"x', 'host' => 'h', 'port' => 3306], function (string $ruta) {
            $contenido = File::get($ruta);
            $this->assertStringContainsString('password="p\\"x"', $contenido);

            return $ruta;
        });

        $this->assertFileDoesNotExist($ruta);
    }
}
