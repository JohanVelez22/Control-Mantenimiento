<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F-04: el buscador de los listados encontraba solo en la página visible. Con Enter
 * se envía `search` al servidor; aquí el registro buscado es el más antiguo, así
 * que nunca está en la primera página.
 */
class BusquedaServidorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'active' => true, 'must_change_password' => false, 'name' => 'Admin']);
    }

    private function assertEncuentra(string $ruta, string $termino, string $esperado, string $variable): void
    {
        // Se compara el contenido de las filas de la página, no todo el HTML: algunos
        // listados traen selectores con todos los clientes.
        $filas = fn ($resp) => collect($resp->viewData($variable)->items())
            ->map(fn ($m) => json_encode($m->toArray() + ['rel' => $m->getRelations()], JSON_UNESCAPED_UNICODE))
            ->implode("\n");

        $sin = $this->actingAs($this->admin)->get(route($ruta))->assertOk();
        $this->assertStringNotContainsString($esperado, $filas($sin), "{$ruta}: el registro no debería estar en la página 1");

        $con = $this->get(route($ruta, ['search' => $termino]))->assertOk();
        $this->assertStringContainsString($esperado, $filas($con), "{$ruta}: la búsqueda no encontró el registro");
        $this->assertGreaterThanOrEqual(1, $con->viewData($variable)->total());
    }

    public function test_mantenimientos(): void
    {
        $cliente = Cliente::factory()->create(['nombres' => 'Zoraida', 'apellidos' => 'Quintero']);
        Mantenimiento::factory()->create(['equipo_id' => Equipo::factory()->create(['cliente_id' => $cliente->id])->id]);
        Mantenimiento::factory()->count(12)->create();

        $this->assertEncuentra('mantenimientos.index', 'Zoraida', 'Zoraida', 'mantenimientos');
    }

    public function test_electronicas_por_identificacion(): void
    {
        $cliente = Cliente::factory()->create(['nombres' => 'Wilmer', 'identificacion' => '99887766']);
        Electronica::factory()->create(['equipo_id' => Equipo::factory()->create(['cliente_id' => $cliente->id])->id]);
        Electronica::factory()->count(12)->create();

        $this->assertEncuentra('electronicas.index', '99887766', 'Wilmer', 'electronicas');
    }

    public function test_equipos(): void
    {
        Equipo::factory()->create(['serie' => 'SN-BUSCADA-777']);
        Equipo::factory()->count(12)->create();

        $this->assertEncuentra('equipos.index', 'BUSCADA-777', 'SN-BUSCADA-777', 'equipos');
    }

    public function test_tecnicos(): void
    {
        Tecnico::factory()->create(['nombre' => 'Hermenegildo Pardo']);
        Tecnico::factory()->count(12)->create();

        $this->assertEncuentra('tecnicos.index', 'Hermenegildo', 'Hermenegildo Pardo', 'tecnicos');
    }

    public function test_cotizaciones(): void
    {
        $cliente = Cliente::factory()->create(['nombres' => 'Eustaquio']);
        foreach (range(1, 17) as $i) {
            Cotizacion::create([
                'codigo' => "COT-{$i}", 'cliente_id' => $i === 1 ? $cliente->id : Cliente::factory()->create()->id,
                'fecha' => now()->toDateString(), 'validez_dias' => 15, 'total' => 1000, 'estado' => 'pendiente', 'user_id' => $this->admin->id,
            ]);
        }

        $this->assertEncuentra('cotizaciones.index', 'Eustaquio', 'Eustaquio', 'cotizaciones');
    }

    public function test_usuarios(): void
    {
        User::factory()->create(['name' => 'Usuaria Buscada', 'role' => 'tecnico']);
        User::factory()->count(12)->create(['role' => 'tecnico']);

        $this->assertEncuentra('usuarios.index', 'Buscada', 'Usuaria Buscada', 'users');
    }

    public function test_stock_con_filtro_agrupado(): void
    {
        Stock::create(['codigo' => 'ZZ-1', 'producto' => 'Fuente de poder buscada', 'categoria' => 'C', 'subcategoria' => 'S',
            'cantidad' => 1, 'precio_compra' => 1, 'utilidad' => 1, 'precio_venta' => 2, 'active' => true]);
        foreach (range(1, 12) as $i) {
            Stock::create(['codigo' => "S-{$i}", 'producto' => "Otro {$i}", 'categoria' => 'C', 'subcategoria' => 'S',
                'cantidad' => 1, 'precio_compra' => 1, 'utilidad' => 1, 'precio_venta' => 2, 'active' => true]);
        }

        $this->assertEncuentra('stocks.index', 'buscada', 'Fuente de poder buscada', 'stocks');
    }

    public function test_facturas_por_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nombres' => 'Gumersindo']);
        foreach (range(1, 12) as $i) {
            Factura::create([
                'numero_factura' => "VT-{$i}", 'tipo_movimiento' => 'venta', 'fecha' => now(),
                'facturable_type' => Cliente::class, 'facturable_id' => $i === 1 ? $cliente->id : Cliente::factory()->create()->id,
                'total_documento' => 1000, 'estado' => 'emitida', 'user_id' => $this->admin->id,
            ]);
        }

        $this->assertEncuentra('inventario.facturas', 'Gumersindo', 'Gumersindo', 'facturas');
    }
}
