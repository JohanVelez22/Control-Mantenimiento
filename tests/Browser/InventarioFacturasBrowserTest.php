<?php

namespace Tests\Browser;

use App\Models\Cliente;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InventarioFacturasBrowserTest extends DuskTestCase
{
    public function test_inventario_venta_scroll_and_facturas_in_browser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_inv_dusk@test.com'],
            [
                'name' => 'Admin Inv Dusk',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        $cliente = Cliente::firstOrCreate(
            ['identificacion' => 'CC-INV-DUSK'],
            [
                'nombres' => 'Cliente Factura',
                'apellidos' => 'Dusk',
                'tipo_identificacion' => 'cedula_ciudadania',
                'genero' => 'masculino',
                'tipo_cliente' => 'cliente',
                'movil' => '3119998877',
                'active' => true,
            ]
        );

        $this->browse(function (Browser $browser) use ($admin) {
            // 1. Visitar pantalla de nueva venta
            $browser->loginAs($admin)
                ->visit('/inventario/venta/nueva')
                ->assertPathIs('/inventario/venta/nueva')
                ->assertSee('Registrar Venta de Inventario')
                ->assertSee('Artículos a Vender');

            // 2. Agregar más de 5 artículos dinámicamente con clic (probando la solución de scroll infinito sin líneas blancas)
            for ($i = 0; $i < 6; $i++) {
                $browser->click('.btn-add')
                    ->pause(150);
            }

            // 3. Validar que la tabla ahora tenga 7 filas de ítems (la inicial + 6 agregadas)
            $rows = $browser->elements('#items-table tbody tr');
            $this->assertCount(7, $rows);
            $browser->screenshot('inventario_venta_scroll_7_items');

            // 4. Visitar el historial de facturas
            $browser->visit('/inventario/facturas')
                ->assertPathIs('/inventario/facturas')
                ->assertSee('Facturas de Inventario')
                ->screenshot('inventario_facturas_historial');
        });
    }
}
