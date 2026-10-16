<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CierreCajaReportesBrowserTest extends DuskTestCase
{
    public function test_cierre_caja_and_financial_reports_in_browser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_reportes_dusk@test.com'],
            [
                'name' => 'Admin Reportes Dusk',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        $this->browse(function (Browser $browser) use ($admin) {
            // 1. Visitar pantalla de Cierres de Caja
            $browser->loginAs($admin)
                ->visit('/cierre')
                ->assertPathIs('/cierre')
                ->assertSee('Cierre del Día')
                ->assertSee('EFECTIVO')
                ->assertSee('Historial de Cierres de Caja')
                ->screenshot('cierre_caja_panel_principal');

            // 2. Visitar el Reporte Financiero Diario
            $browser->visit('/reportes-financieros/diario')
                ->assertPathIs('/reportes-financieros/diario')
                ->assertSee('Informes y Reportes')
                ->assertSee('Imprimir')
                ->assertSee('Excel')
                ->assertSee('PDF')
                ->screenshot('reporte_financiero_diario');
        });
    }
}
