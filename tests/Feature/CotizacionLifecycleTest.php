<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Stock;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected Cliente $cliente;

    protected Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);
        $this->cliente = Cliente::factory()->create();
        $this->stock = Stock::factory()->create(['cantidad' => 10, 'precio_venta' => 25000]);
    }

    public function test_admin_can_create_and_store_cotizacion_with_items(): void
    {
        $response = $this->actingAs($this->admin)->post(route('cotizaciones.store'), [
            'cliente_id' => $this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'validez_dias' => 15,
            'notas' => 'Cotización de prueba',
            'items' => [
                [
                    'tipo' => 'stock',
                    'item_id' => $this->stock->id,
                    'descripcion' => $this->stock->producto,
                    'cantidad' => 2,
                    'precio_unitario' => 25000,
                ],
                [
                    'tipo' => 'libre',
                    'descripcion' => 'Servicio de mano de obra',
                    'cantidad' => 1,
                    'precio_unitario' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect(route('cotizaciones.index'));
        $this->assertDatabaseHas('cotizaciones', [
            'cliente_id' => $this->cliente->id,
            'total' => 100000,
            'estado' => 'pendiente',
            'anulado' => false,
        ]);

        $cotizacion = Cotizacion::first();
        $this->assertCount(2, $cotizacion->items);
    }

    public function test_admin_can_update_cotizacion(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-TEST-01',
            'cliente_id' => $this->cliente->id,
            'fecha' => Carbon::today(),
            'validez_dias' => 15,
            'total' => 50000,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->put(route('cotizaciones.update', $cotizacion), [
            'cliente_id' => $this->cliente->id,
            'fecha' => Carbon::today()->toDateString(),
            'validez_dias' => 30,
            'notas' => 'Actualizada',
            'items' => [
                [
                    'tipo' => 'libre',
                    'descripcion' => 'Servicio actualizado',
                    'cantidad' => 2,
                    'precio_unitario' => 40000,
                ],
            ],
        ]);

        $response->assertRedirect(route('cotizaciones.index'));
        $cotizacion->refresh();
        $this->assertEquals(80000, $cotizacion->total);
        $this->assertEquals(30, $cotizacion->validez_dias);
    }

    public function test_admin_can_anular_and_reactivar_cotizacion(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-TEST-02',
            'cliente_id' => $this->cliente->id,
            'fecha' => Carbon::today(),
            'validez_dias' => 15,
            'total' => 50000,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);

        // Anular
        $responseAnular = $this->actingAs($this->admin)->post(route('cotizaciones.anular', $cotizacion));
        $responseAnular->assertSessionHas('success');
        $cotizacion->refresh();
        $this->assertTrue((bool) $cotizacion->anulado);

        // Reactivar
        $responseReactivar = $this->actingAs($this->admin)->post(route('cotizaciones.anular', $cotizacion));
        $responseReactivar->assertSessionHas('success');
        $cotizacion->refresh();
        $this->assertFalse((bool) $cotizacion->anulado);
    }

    public function test_admin_can_rechazar_and_reactivar_cotizacion(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-TEST-03',
            'cliente_id' => $this->cliente->id,
            'fecha' => Carbon::today(),
            'validez_dias' => 15,
            'total' => 50000,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);

        // Rechazar
        $responseRechazar = $this->actingAs($this->admin)->post(route('cotizaciones.rechazar', $cotizacion));
        $responseRechazar->assertSessionHas('success');
        $cotizacion->refresh();
        $this->assertEquals('rechazada', $cotizacion->estado);

        // Reactivar
        $responseReactivar = $this->actingAs($this->admin)->post(route('cotizaciones.reactivar', $cotizacion));
        $responseReactivar->assertSessionHas('success');
        $cotizacion->refresh();
        $this->assertEquals('pendiente', $cotizacion->estado);
    }

    public function test_admin_can_generate_pdf_cotizacion(): void
    {
        $cotizacion = Cotizacion::create([
            'codigo' => 'COT-TEST-PDF',
            'cliente_id' => $this->cliente->id,
            'fecha' => Carbon::today(),
            'validez_dias' => 15,
            'total' => 50000,
            'estado' => 'pendiente',
            'user_id' => $this->admin->id,
            'anulado' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('cotizaciones.pdf', $cotizacion));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
