<?php

namespace Tests\Feature;

use App\Models\Mantenimiento;
use App\Models\User;
use App\Support\VerificadorEsquema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * S-09, S-10, S-13, ARQ-01, ARQ-11, D-19, O-19: seguridad y operación.
 */
class SeguridadOperacionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->admin = User::factory()->create([
            'email' => 'administrador@tecnisystemas.com', 'role' => 'admin', 'active' => true, 'must_change_password' => false,
        ]);
    }

    private function intentar(string $usuario, string $clave, string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), ['email' => $usuario, 'password' => $clave]);
    }

    public function test_alias_distintos_suman_al_mismo_bloqueo_de_cuenta(): void
    {
        // 10 fallos repartidos entre alias y equipos (antes cada alias tenía su propio contador).
        foreach (['admin', 'administrador', 'administrador@tecnisystemas.com', 'admin', 'administrador'] as $i => $alias) {
            $this->intentar($alias, 'mala', "10.0.0.{$i}");
            $this->intentar($alias, 'mala', "10.0.1.{$i}");
        }

        $this->intentar('admin', 'mala', '10.0.0.1')->assertSessionHasErrors('email');
        $this->assertStringContainsString('Acceso bloqueado', session('errors')->first('email'));
    }

    public function test_un_tercero_no_puede_dejar_afuera_al_usuario_legitimo(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->intentar('admin', 'mala', "172.16.0.{$i}");
        }

        // El administrador entra desde su propio equipo, que no participó en los fallos.
        $this->intentar('administrador@tecnisystemas.com', 'password', '192.168.1.50')->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_recordarme_dura_siete_dias(): void
    {
        $this->assertSame(60 * 24 * 7, config('auth.guards.web.remember'));
    }

    public function test_cabeceras_de_seguridad_tambien_en_descargas(): void
    {
        $orden = Mantenimiento::factory()->create(['anulado' => false, 'estado' => 'pendiente', 'fecha_salida' => null]);

        $respuesta = $this->actingAs($this->admin)->get(route('mantenimientos.recepcion', $orden))->assertOk();

        $this->assertSame('nosniff', $respuesta->headers->get('X-Content-Type-Options'));
        $this->assertSame('0', $respuesta->headers->get('X-XSS-Protection'));
        $this->assertNull($respuesta->headers->get('Strict-Transport-Security'), 'HSTS solo por https');
    }

    public function test_tecnico_no_entra_a_configuracion_auditoria_ni_respaldos_por_ruta(): void
    {
        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        $this->actingAs($tecnico);

        $this->get(route('configuracion.index'))->assertForbidden();
        $this->get(route('eventos.index'))->assertForbidden();
        $this->getJson(route('backups.config'))->assertForbidden();
        $this->get(route('caja.index'))->assertOk();
        $this->get(route('cierre.index'))->assertOk();
    }

    public function test_desactivar_un_usuario_cierra_sus_sesiones_y_respeta_la_intencion(): void
    {
        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        DB::table('sessions')->insert(['id' => 'sesion-tecnico', 'user_id' => $tecnico->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->admin)->post(route('usuarios.anular', $tecnico), ['accion' => 'anular']);
        $this->assertFalse((bool) $tecnico->fresh()->active);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $tecnico->id)->count());

        // Doble clic: la segunda "anulación" no lo reactiva.
        $this->post(route('usuarios.anular', $tecnico), ['accion' => 'anular'])->assertSessionHas('error');
        $this->assertFalse((bool) $tecnico->fresh()->active);
    }

    public function test_catalogo_de_diseno_no_existe_fuera_de_desarrollo(): void
    {
        // En pruebas (entorno testing) sí se registra; la condición excluye producción.
        $this->assertTrue(app()->environment('testing'));
        $this->assertStringContainsString("app()->environment('local', 'testing')", file_get_contents(base_path('routes/web.php')));
    }

    public function test_esquema_tiene_los_indices_y_restricciones_esperados(): void
    {
        $this->assertSame([], VerificadorEsquema::faltantes());
    }

    public function test_auditoria_de_sistema_no_escribe_en_la_base_sin_deep(): void
    {
        $antes = DB::table('clientes')->count();
        $this->artisan('system:audit')->expectsOutputToContain('Pruebas transaccionales omitidas');
        $this->assertSame($antes, DB::table('clientes')->count());
    }

    public function test_preflight_avisa_contrasenas_iniciales_en_env(): void
    {
        config(['seguridad.passwords_iniciales.admin' => 'Admin123*']);

        $this->artisan('app:preflight')->expectsOutputToContain('ADMIN_DEFAULT_PASSWORD');
    }
}
