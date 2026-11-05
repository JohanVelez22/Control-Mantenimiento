<?php

namespace Tests\Feature;

use App\Models\CategoriaStock;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Evento;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Prueba de humo: recorre TODAS las pantallas GET con datos demo y verifica que ninguna
 * responda con error del servidor, y que el rol invitado no acceda a pantallas internas.
 */
class SmokeRutasTest extends TestCase
{
    use RefreshDatabase;

    /** Rutas que no son pantallas (descargas de archivos con nombre libre, salud, raíz). */
    private const EXCLUIDAS = ['backups.download', 'login'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--force' => true]);
        Artisan::call('app:seed-demo-data', ['--no-interaction' => true]);

        // Datos que la siembra demo no crea
        CierreCaja::firstOrCreate(['fecha' => now()->subDays(10)->toDateString()], [
            'total_ingresos' => 0, 'total_egresos' => 0, 'efectivo' => 0, 'consignacion' => 0,
            'saldo_final' => 0, 'num_movimientos' => 0, 'user_id' => User::first()->id,
        ]);
        if (! Evento::exists()) {
            Evento::registrar('login', User::first(), null, null, 'Evento de prueba');
        }
    }

    /** @return array<string, string> uri => nombre de ruta */
    private function rutasGet(string $rol): array
    {
        $parametros = [
            'movimiento' => MovimientoCaja::value('id'), 'cierre' => CierreCaja::value('id'),
            'cliente' => Cliente::value('id'), 'concepto' => ConceptoCaja::value('id'),
            'cotizacion' => Cotizacion::value('id'), 'electronica' => Electronica::value('id'),
            'equipo' => Equipo::value('id'), 'evento' => Evento::value('id'), 'factura' => Factura::value('id'),
            'mantenimiento' => Mantenimiento::value('id'), 'proveedor' => Proveedor::value('id'),
            'categoria' => CategoriaStock::value('id'), 'stock' => Stock::value('id'),
            'tecnico' => Tecnico::value('id'), 'usuario' => User::value('id'),
        ];

        $rutas = [];
        /** @var Route $ruta */
        foreach (RouteFacade::getRoutes() as $ruta) {
            $nombre = $ruta->getName();
            $middleware = implode(',', $ruta->gatherMiddleware());
            if (! in_array('GET', $ruta->methods(), true) || ! $nombre || in_array($nombre, self::EXCLUIDAS, true)
                || ! str_contains($middleware, 'auth') || ! str_contains($middleware, $rol)) {
                continue;
            }

            $faltan = array_diff($ruta->parameterNames(), array_keys(array_filter($parametros)));
            if ($faltan) {
                $this->fail("Falta dato demo para {$nombre}: ".implode(', ', $faltan));
            }
            $rutas[$nombre] = route($nombre, array_intersect_key($parametros, array_flip($ruta->parameterNames())));
        }

        return $rutas;
    }

    public function test_ninguna_pantalla_responde_error_para_admin_y_tecnico(): void
    {
        $fallos = [];
        foreach (['admin', 'tecnico'] as $rol) {
            $usuario = User::where('role', $rol)->first();
            foreach ($this->rutasGet($rol) as $nombre => $url) {
                $estado = $this->actingAs($usuario)->get($url)->getStatusCode();
                if ($estado >= 500) {
                    $fallos[] = "[{$rol}] {$nombre} → {$estado}";
                }
            }
        }

        $this->assertSame([], $fallos, "Pantallas con error:\n".implode("\n", $fallos));
        $this->assertGreaterThan(60, count($this->rutasGet('admin')), 'El recorrido debe cubrir todas las pantallas');
    }

    /** Las librerías locales existen y coinciden con su hash SRI (si no, el navegador las bloquea). */
    public function test_librerias_locales_coinciden_con_su_sri(): void
    {
        $vistas = ['layouts/app.blade.php', 'layouts/consulta.blade.php', 'dashboard.blade.php'];
        $revisadas = 0;

        foreach ($vistas as $vista) {
            $html = file_get_contents(resource_path('views/'.$vista));
            preg_match_all("#asset\('(vendor/[^']+)'\) }}\" integrity=\"(sha384-[^\"]+)\"#", $html, $m, PREG_SET_ORDER);
            foreach ($m as [, $ruta, $sri]) {
                $archivo = public_path($ruta);
                $this->assertFileExists($archivo, "Falta {$ruta}: ejecute npm run vendor:sync");
                $this->assertSame($sri, 'sha384-'.base64_encode(hash_file('sha384', $archivo, true)), "SRI desactualizado para {$ruta}");
                $revisadas++;
            }
        }

        $this->assertGreaterThanOrEqual(11, $revisadas);
    }

    /** El invitado no puede modificar nada: se intenta cada ruta de escritura y ningún dato cambia. */
    public function test_invitado_no_puede_ejecutar_ninguna_accion_de_escritura(): void
    {
        $invitado = User::where('role', 'invitado')->first();
        $permitidas = ['logout', 'login', 'electronicas.dismiss-alert'];
        $tablas = ['clientes', 'equipos', 'tecnicos', 'stocks', 'mantenimientos', 'electronicas', 'abonos',
            'movimiento_cajas', 'facturas', 'factura_items', 'cotizaciones', 'cierre_cajas', 'users',
            'proveedores', 'concepto_cajas', 'categoria_stocks', 'configuraciones', 'bajas_stock'];
        $huella = fn () => collect($tablas)->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()
            .'|'.DB::table($t)->max('updated_at')])->all();

        $antes = $huella();
        $probadas = 0;
        foreach (RouteFacade::getRoutes() as $ruta) {
            $metodo = collect($ruta->methods())->first(fn ($m) => in_array($m, ['POST', 'PUT', 'PATCH', 'DELETE'], true));
            if (! $metodo || in_array($ruta->getName(), $permitidas, true) || ! in_array('auth', $ruta->gatherMiddleware(), true)) {
                continue;
            }
            $url = url(preg_replace('/\{[^}]+\}/', '1', $ruta->uri()));
            $this->actingAs($invitado)->call($metodo, $url, ['monto' => 1000, 'nombre' => 'x', 'accion' => 'anular']);
            $probadas++;
        }

        $this->assertGreaterThan(50, $probadas);
        $this->assertSame($antes, $huella(), 'Una acción de escritura modificó datos siendo invitado');
    }

    /** El técnico no ejecuta acciones exclusivas del administrador (usuarios, configuración, respaldos). */
    public function test_tecnico_no_ejecuta_acciones_de_administrador(): void
    {
        $tecnico = User::where('role', 'tecnico')->first();
        $otro = User::where('role', 'invitado')->first();
        $usuarios = User::count();
        $config = Configuracion::first()?->nombre;

        $this->actingAs($tecnico)->post(route('usuarios.store'), [
            'name' => 'Intruso', 'email' => 'intruso@x.com', 'password' => 'Clave-Segura-1', 'password_confirmation' => 'Clave-Segura-1', 'role' => 'admin',
        ]);
        $this->actingAs($tecnico)->put(route('usuarios.update', $otro), ['name' => $otro->name, 'email' => $otro->email, 'role' => 'admin']);
        $this->actingAs($tecnico)->post(route('usuarios.anular', $otro));
        $this->actingAs($tecnico)->post(route('configuracion.update'), ['nombre' => 'Cambiado por técnico']);
        $this->actingAs($tecnico)->postJson(route('backups.manual'))->assertForbidden();
        $this->actingAs($tecnico)->postJson(route('backups.restore', 'cualquiera.sql'))->assertForbidden();
        $this->actingAs($tecnico)->postJson(route('backups.git-update'))->assertForbidden();

        $this->assertSame($usuarios, User::count());
        $this->assertSame('invitado', $otro->fresh()->role);
        $this->assertTrue((bool) $otro->fresh()->active);
        $this->assertSame($config, Configuracion::first()?->nombre);
    }

    /** El comando de pre-despliegue detecta un entorno que no es de producción y contraseñas publicadas. */
    public function test_preflight_detecta_entorno_no_listo(): void
    {
        User::where('role', 'admin')->first()->update(['password' => bcrypt('Admin123*')]);

        $this->artisan('app:preflight')
            ->expectsOutputToContain('APP_ENV=production')
            ->expectsOutputToContain('contraseña publicada')
            ->assertExitCode(1);
    }

    public function test_invitado_no_accede_a_pantallas_internas(): void
    {
        $invitado = User::where('role', 'invitado')->first();
        $internas = array_diff_key($this->rutasGet('admin'), $this->rutasGet('invitado'));

        $accesibles = [];
        foreach ($internas as $nombre => $url) {
            $respuesta = $this->actingAs($invitado)->get($url);
            if ($respuesta->getStatusCode() === 200) {
                $accesibles[] = $nombre;
            }
        }

        $this->assertSame([], $accesibles, 'El invitado accede a: '.implode(', ', $accesibles));
    }
}
