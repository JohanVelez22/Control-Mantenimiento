<?php

namespace Tests\Browser;

use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StockProveedorInactivoVerificationTest extends DuskTestCase
{
    public function test_inactive_banner_removed_and_proveedor_reactivar_added(): void
    {
        $admin = User::where('role', 'admin')->first();
        $stockInactivo = Stock::where('active', false)->first();
        $proveedorInactivo = Proveedor::where('active', false)->first();
        $proveedorActivo = Proveedor::where('active', true)->first();

        $this->browse(function (Browser $browser) use ($admin, $stockInactivo, $proveedorInactivo, $proveedorActivo) {
            // 1. Stock Inactivo: Validar que NO existe el banner grande y que SI está el badge INACTIVO y botón Reactivar
            $browser->loginAs($admin)
                ->visit('/stocks/' . $stockInactivo->id)
                ->assertPathIs('/stocks/' . $stockInactivo->id)
                ->assertDontSee('PRODUCTO ANULADO / INACTIVO')
                ->assertDontSee('Este producto no está disponible para nuevas operaciones')
                ->assertSee('INACTIVO')
                ->assertSee('Reactivar')
                ->screenshot('stock_inactivo_sin_banner');

            // 2. Proveedor Inactivo: Validar que tiene el badge INACTIVO y el botón Reactivar
            $browser->visit('/proveedores/' . $proveedorInactivo->id)
                ->assertPathIs('/proveedores/' . $proveedorInactivo->id)
                ->assertSee('INACTIVO')
                ->assertSee('Reactivar')
                ->screenshot('proveedor_inactivo_con_reactivar');

            // 3. Proveedor Activo: Validar que tiene el badge ACTIVO y el botón Anular
            $browser->visit('/proveedores/' . $proveedorActivo->id)
                ->assertPathIs('/proveedores/' . $proveedorActivo->id)
                ->assertSee('ACTIVO')
                ->assertSee('Anular')
                ->screenshot('proveedor_activo_con_anular');
        });
    }

    public function test_stock_show_title_and_tables_styling(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('stocks.show', 70))
                ->resize(1440, 900)
                ->pause(200)
                ->screenshot('stock_show_calibrated_final_dark');

            // 1. LIGHT MODE TEST
            $browser->script("document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light');");
            $browser->pause(300);
            $browser->screenshot('stock_show_calibrated_final_light');

            $lightComparison = $browser->script("
                const tables = document.querySelectorAll('table.ts-table');
                const t1 = tables[0];
                const t2 = tables[1];
                const th1 = t1 ? t1.querySelector('thead th') : null;
                return {
                    table1_bg: t1 ? window.getComputedStyle(t1).backgroundColor : null,
                    table2_bg: t2 ? window.getComputedStyle(t2).backgroundColor : null,
                    th1_height: th1 ? th1.getBoundingClientRect().height : null,
                    th1_color: th1 ? window.getComputedStyle(th1).color : null
                };
            ");
            dump('LIGHT MODE:', $lightComparison[0]);

            $browser->scrollIntoView('.mt-8 table')
                ->pause(200)
                ->screenshot('stock_70_light_mode_fixed');

            // 2. DARK MODE TEST
            $browser->script("document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark');");
            $browser->pause(200);

            $browser->scrollIntoView('.mt-8 table')
                ->pause(200)
                ->screenshot('stock_show_dark_mode_motivo_fixed');

            // 3. OPERACIONES COSTO & SALIDA TEST (Screenshot 1 test)
            $browser->resize(1400, 900)
                ->visit('/reportes-financieros/operaciones?tipo=solo_mantenimientos')
                ->pause(500)
                ->script("document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark');");
            $browser->pause(200)
                ->screenshot('operaciones_costo_horizontal_fixed');
        });
    }
}
