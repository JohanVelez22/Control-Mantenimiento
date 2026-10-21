<?php

namespace Tests\Browser;

use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CajaAbonosBrowserTest extends DuskTestCase
{
    protected static ?User $adminUser = null;

    protected static ?ConceptoCaja $concepto = null;

    public function test_caja_lifecycle_and_abonos_in_browser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_caja_dusk@test.com'],
            [
                'name' => 'Admin Caja Dusk',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        $concepto = ConceptoCaja::firstOrCreate(
            ['nombre' => 'Servicio Técnico Dusk'],
            [
                'tipo' => 'ingreso',
                'active' => true,
            ]
        );

        // Crear un movimiento de prueba con saldo pendiente:
        // Total: 200.000, Pago inicial: 50.000 -> Saldo: 150.000
        $movimiento = MovimientoCaja::create([
            'tipo_movimiento' => 'ingreso',
            'tipo_pago' => 'efectivo',
            'monto' => 50000,
            'monto_total' => 200000,
            'fecha' => now()->format('Y-m-d'),
            'concepto_id' => $concepto->id,
            'persona' => 'Cliente Prueba Dusk',
            'descripcion' => 'Prueba E2E de movimiento con saldo pendiente',
            'user_id' => $admin->id,
            'anulado' => false,
            'active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $movimiento) {
            // 1. Visitar listado de caja
            $browser->loginAs($admin)
                ->visit('/caja')
                ->assertPathIs('/caja')
                ->assertSee('Cliente Prueba Dusk')
                ->assertSee('SALDO:')
                ->screenshot('caja_listado_general');

            // 2. Visitar la pantalla de edición / gestión de abonos
            $browser->visit(route('caja.edit', $movimiento->id))
                ->assertPathIs("/caja/{$movimiento->id}/edit")
                ->assertSee('Resumen Financiero')
                ->assertSee('$ 200.000') // Monto Total
                ->assertSee('$ 150.000') // Saldo Pendiente
                ->assertSee('Registrar Nuevo Abono')
                ->screenshot('caja_resumen_financiero');

            // 3. Registrar un abono real de $ 150.000 para saldar la deuda
            $browser->script([
                "document.getElementById('monto_abono_real').value = '150000'",
                "document.getElementById('monto_abono_visual').value = '150.000'",
            ]);
            $browser->type('descripcion', 'Abono final liquidación total')
                ->press('➕ Registrar Abono')
                ->waitForText('Abono de $150.000 registrado correctamente.')
                ->screenshot('caja_abono_completado');

            // 4. Verificar que el movimiento ahora muestre "Totalmente Pagado"
            $browser->visit(route('caja.edit', $movimiento->id))
                ->waitForText('¡Totalmente Pagado!')
                ->assertSee('$ 200.000') // Total Acumulado Pagado
                ->screenshot('caja_totalmente_pagado');
        });

        // Limpieza de datos de prueba
        $movimiento->childPayments()->forceDelete();
        $movimiento->forceDelete();
    }
}
