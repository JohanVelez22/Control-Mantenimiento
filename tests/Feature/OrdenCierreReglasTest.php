<?php

namespace Tests\Feature;

use App\Models\Electronica;
use App\Models\Evento;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * B-01 (reglas de cierre y reapertura de órdenes) y B-03 (repuestos bloqueados en
 * órdenes terminadas). Cada prueba falla con el código anterior a la corrección.
 */
class OrdenCierreReglasTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE_ADMIN = 'Clave-Admin-2026';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'admin', 'active' => true, 'must_change_password' => false,
            'password' => Hash::make(self::CLAVE_ADMIN),
        ]);
    }

    private function orden(array $attrs = []): Mantenimiento
    {
        return Mantenimiento::factory()->create($attrs + [
            'costo' => 100000, 'anulado' => false, 'estado' => 'pendiente', 'fecha_salida' => null,
            'fecha_entrada' => now()->subDays(2)->toDateString(),
        ]);
    }

    private function datos(Mantenimiento $m, array $cambios = []): array
    {
        return $cambios + [
            'fecha_entrada' => $m->fecha_entrada instanceof \DateTimeInterface ? $m->fecha_entrada->format('Y-m-d') : substr((string) $m->fecha_entrada, 0, 10),
            'fecha_salida' => $m->fecha_salida ? substr((string) $m->fecha_salida, 0, 10) : null,
            'tipo' => $m->tipo,
            'reparacion' => $m->reparacion,
            'descripcion' => $m->descripcion,
            'costo' => (float) $m->costo,
            'estado' => $m->estado,
            'equipo_id' => $m->equipo_id,
            'tecnico_id' => $m->tecnico_id,
        ];
    }

    private function abonar(Mantenimiento $m, int $monto): void
    {
        $this->actingAs($this->admin)->post(route('abonos.store', $m), [
            'monto' => $monto, 'fecha' => now()->toDateString(), 'tipo_pago' => 'efectivo', 'descripcion' => 'Pago',
        ])->assertSessionHasNoErrors();
    }

    private function stock(): Stock
    {
        return Stock::create([
            'codigo' => 'R-9', 'producto' => 'Memoria', 'categoria' => 'C', 'subcategoria' => 'S',
            'cantidad' => 5, 'precio_compra' => 10000, 'utilidad' => 50, 'precio_venta' => 20000, 'active' => true,
        ]);
    }

    public function test_terminar_exige_fecha_de_salida(): void
    {
        $m = $this->orden(['costo' => 0]);

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, ['estado' => 'terminado', 'fecha_salida' => null]))
            ->assertSessionHasErrors('fecha_salida');

        $this->assertSame('pendiente', $m->fresh()->estado);
    }

    public function test_fecha_de_salida_no_puede_ser_futura(): void
    {
        $m = $this->orden(['costo' => 0]);

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, [
            'estado' => 'terminado', 'fecha_salida' => now()->addDays(3)->toDateString(),
        ]))->assertSessionHasErrors('fecha_salida');
    }

    public function test_terminar_con_saldo_sin_autorizacion_se_rechaza(): void
    {
        $m = $this->orden();

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, [
            'estado' => 'terminado', 'fecha_salida' => now()->toDateString(),
        ]))->assertSessionHasErrors('entrega_con_saldo');

        $this->assertSame('pendiente', $m->fresh()->estado);
    }

    public function test_entrega_con_saldo_exige_motivo_y_queda_registrada(): void
    {
        $m = $this->orden();
        $base = $this->datos($m, ['estado' => 'terminado', 'fecha_salida' => now()->toDateString(), 'entrega_con_saldo' => '1']);

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $base + ['motivo_entrega' => ''])
            ->assertSessionHasErrors('motivo_entrega');
        $this->assertSame('pendiente', $m->fresh()->estado);

        $this->put(route('mantenimientos.update', $m), $base + ['motivo_entrega' => 'Cliente frecuente, paga el viernes'])
            ->assertSessionHasNoErrors();

        $this->assertSame('terminado', $m->fresh()->estado);
        $evento = Evento::where('accion', 'orden_entregada_con_saldo')->where('modelo_id', $m->id)->first();
        $this->assertNotNull($evento);
        $this->assertStringContainsString('100.000', $evento->descripcion);
        $this->assertStringContainsString('paga el viernes', $evento->descripcion);
    }

    public function test_orden_pagada_se_termina_sin_autorizacion(): void
    {
        $m = $this->orden();
        $this->abonar($m, 100000);

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, [
            'estado' => 'terminado', 'fecha_salida' => now()->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertSame('terminado', $m->fresh()->estado);
        $this->assertSame(0, Evento::where('accion', 'orden_entregada_con_saldo')->count());
    }

    public function test_orden_pendiente_no_guarda_fecha_de_salida(): void
    {
        $m = $this->orden();

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, [
            'fecha_salida' => now()->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertNull($m->fresh()->fecha_salida);
    }

    public function test_reabrir_exige_motivo_borra_salida_y_queda_registrado(): void
    {
        $m = $this->orden(['costo' => 0, 'estado' => 'terminado', 'fecha_salida' => now()->toDateString()]);

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, ['estado' => 'pendiente']))
            ->assertSessionHasErrors('motivo_reapertura');
        $this->assertSame('terminado', $m->fresh()->estado);

        $this->put(route('mantenimientos.update', $m), $this->datos($m, ['estado' => 'pendiente', 'motivo_reapertura' => 'Volvió con la misma falla']))
            ->assertSessionHasNoErrors();

        $m->refresh();
        $this->assertSame('pendiente', $m->estado);
        $this->assertNull($m->fecha_salida);
        $this->assertTrue(Evento::where('accion', 'orden_reabierta')->where('modelo_id', $m->id)->exists());
    }

    public function test_orden_terminada_no_cambia_costo_equipo_ni_tecnico_pero_si_la_descripcion(): void
    {
        $m = $this->orden(['costo' => 50000]);
        $this->abonar($m, 50000);
        $m->update(['estado' => 'terminado', 'fecha_salida' => now()->toDateString()]);
        $m->refresh();

        $this->actingAs($this->admin)->put(route('mantenimientos.update', $m), $this->datos($m, ['costo' => 80000]))
            ->assertSessionHasErrors('estado');
        $this->assertEquals(50000, (float) $m->fresh()->costo);

        $this->put(route('mantenimientos.update', $m), $this->datos($m, ['descripcion' => 'Se cambió la pasta térmica']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Se cambió la pasta térmica', $m->fresh()->descripcion);
    }

    public function test_factura_solo_para_orden_terminada(): void
    {
        $pendiente = $this->orden();
        // Dato heredado: pendiente con fecha de salida (antes se podía facturar).
        $pendiente->forceFill(['fecha_salida' => now()->toDateString()])->save();

        $this->actingAs($this->admin)->get(route('mantenimientos.factura', $pendiente))
            ->assertRedirect()
            ->assertSessionHas('error');

        $terminada = $this->orden(['estado' => 'terminado', 'fecha_salida' => now()->toDateString()]);
        $this->get(route('mantenimientos.factura', $terminada))->assertOk();
    }

    public function test_b03_no_se_agregan_ni_quitan_repuestos_en_orden_terminada(): void
    {
        $stock = $this->stock();
        $m = $this->orden();
        $this->actingAs($this->admin)->post(route('mantenimientos.stocks.store', $m), ['stock_id' => $stock->id, 'cantidad' => 1]);
        $m->refresh();
        $costo = (float) $m->costo;
        $m->update(['estado' => 'terminado', 'fecha_salida' => now()->toDateString()]);

        $this->post(route('mantenimientos.stocks.store', $m), ['stock_id' => $stock->id, 'cantidad' => 1])
            ->assertSessionHas('error');
        $this->delete(route('mantenimientos.stocks.destroy', [$m, $stock->id]))
            ->assertSessionHas('error');

        $this->assertEquals($costo, (float) $m->fresh()->costo);
        $this->assertSame(4, (int) $stock->fresh()->cantidad);
        $this->assertSame(1, $m->stocks()->count());
    }

    public function test_orden_terminada_con_saldo_si_recibe_abonos(): void
    {
        $m = $this->orden(['estado' => 'terminado', 'fecha_salida' => now()->toDateString()]);

        $this->abonar($m, 40000);

        $this->assertEquals(60000, $m->fresh()->saldo_pendiente);
    }

    public function test_tecnico_que_crea_orden_terminada_con_saldo_necesita_clave_de_admin(): void
    {
        $tecnico = User::factory()->create(['role' => 'tecnico', 'active' => true, 'must_change_password' => false]);
        $plantilla = Mantenimiento::factory()->make(['costo' => 70000]);
        $datos = [
            'fecha_entrada' => now()->toDateString(), 'fecha_salida' => now()->toDateString(),
            'tipo' => 'correctivo', 'reparacion' => 'hardware', 'descripcion' => 'Cambio de disco',
            'costo' => 70000, 'estado' => 'terminado',
            'equipo_id' => $plantilla->equipo_id, 'tecnico_id' => $plantilla->tecnico_id,
            'entrega_con_saldo' => '1', 'motivo_entrega' => 'Paga al recoger el cargador',
        ];

        $this->actingAs($tecnico)->post(route('mantenimientos.store'), $datos + ['admin_password' => 'incorrecta'])
            ->assertSessionHas('error');
        $this->assertSame(0, Mantenimiento::where('descripcion', 'Cambio de disco')->count());

        $this->post(route('mantenimientos.store'), $datos + ['admin_password' => self::CLAVE_ADMIN])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Mantenimiento::where('descripcion', 'Cambio de disco')->count());
    }

    public function test_electronica_aplica_las_mismas_reglas(): void
    {
        $e = Electronica::factory()->create([
            'costo' => 90000, 'anulado' => false, 'estado' => 'pendiente', 'fecha_salida' => null,
            'fecha_entrada' => now()->subDay()->toDateString(),
        ]);
        $datos = [
            'equipo_id' => $e->equipo_id, 'descripcion_problema' => $e->descripcion_problema,
            'tipo' => $e->tipo, 'reparacion' => $e->reparacion, 'costo' => 90000,
            'estado' => 'terminado', 'fecha_entrada' => now()->subDay()->toDateString(),
            'fecha_salida' => now()->toDateString(), 'tecnico_id' => $e->tecnico_id,
        ];

        $this->actingAs($this->admin)->put(route('electronicas.update', $e), $datos)
            ->assertSessionHasErrors('entrega_con_saldo');
        $this->get(route('electronicas.factura', $e))->assertSessionHas('error');

        $this->put(route('electronicas.update', $e), $datos + ['entrega_con_saldo' => '1', 'motivo_entrega' => 'Garantía del proveedor pendiente'])
            ->assertSessionHasNoErrors();
        $this->assertSame('terminado', $e->fresh()->estado);

        $stock = $this->stock();
        $this->post(route('electronicas.stocks.store', $e), ['stock_id' => $stock->id, 'cantidad' => 1])
            ->assertSessionHas('error');
        $this->assertSame(5, (int) $stock->fresh()->cantidad);
    }
}
