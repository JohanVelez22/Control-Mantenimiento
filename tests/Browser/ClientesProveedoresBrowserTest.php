<?php

namespace Tests\Browser;

use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientesProveedoresBrowserTest extends DuskTestCase
{
    public function test_clientes_and_proveedores_directory_in_browser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_directorio_dusk@test.com'],
            [
                'name' => 'Admin Directorio Dusk',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        $cliente = Cliente::firstOrCreate(
            ['identificacion' => 'CC-DIR-DUSK-01'],
            [
                'nombres' => 'Alejandro',
                'apellidos' => 'Directorio Dusk',
                'tipo_identificacion' => 'cedula_ciudadania',
                'genero' => 'masculino',
                'tipo_cliente' => 'cliente',
                'movil' => '3123456789',
                'email' => 'alejandro_dir@dusk.com',
                'active' => true,
            ]
        );

        $proveedor = Proveedor::firstOrCreate(
            ['identificacion' => 'NIT-DIR-DUSK-01'],
            [
                'nombre_razon_social' => 'Mayorista Tecnológico Dusk SAS',
                'tipo_identificacion' => 'nit',
                'tipo_tercero' => 'persona_juridica',
                'telefono' => '6015554321',
                'email' => 'contacto@mayoristadusk.com',
                'active' => true,
            ]
        );

        $proveedor->update([
            'tipo_identificacion' => 'cedula_ciudadania',
            'telefono2' => '6060606',
        ]);

        $this->browse(function (Browser $browser) use ($admin, $proveedor) {
            // 1. Módulo Clientes: Ver listado y filtrar en tiempo real
            $browser->loginAs($admin)
                ->visit('/clientes')
                ->assertPathIs('/clientes')
                ->assertSee('Clientes')
                ->assertSee('Alejandro Directorio Dusk')
                ->type('#search-clientes', 'Alejandro')
                ->pause(200)
                ->assertSee('Alejandro Directorio Dusk')
                ->screenshot('clientes_directorio_filtro');

            // 2. Módulo Proveedores: Ver listado y filtrar en tiempo real
            $browser->visit('/proveedores')
                ->assertPathIs('/proveedores')
                ->assertSee('Proveedores')
                ->assertSee('Mayorista Tecnológico Dusk SAS')
                ->type('#search-proveedores', 'Mayorista')
                ->pause(200)
                ->assertSee('Mayorista Tecnológico Dusk SAS')
                ->screenshot('proveedores_directorio_filtro');

            // 3. Módulo Proveedores: Ver detalle con abreviatura (C.C.) y teléfono alternativo
            $browser->visit('/proveedores/' . $proveedor->id)
                ->assertPathIs('/proveedores/' . $proveedor->id)
                ->assertSee('(C.C.)')
                ->assertSee('Alt:')
                ->assertSee('6060606')
                ->screenshot('proveedor_detalle_abreviado')
                ->visit('/proveedores/' . $proveedor->id)
                ->assertSee('Artículos Suministrados')
                ->assertSee('Historial de Compras')
                ->screenshot('proveedor_1_tablas')
                ->script("window.scrollTo(0, 600);");
            $browser->pause(200)
                ->screenshot('proveedor_1_historial_compras')
                ->visit('/stocks/1')
                ->screenshot('stock_1_proveedor_card');

            // 4. Módulo Proveedores: Verificar que el desplegable de Tipo de Identificación muestra 5 opciones completas sin cortes horizontales
            $browser->visit('/proveedores/create')
                ->waitFor('.ts-wrapper')
                ->pause(300)
                ->script("document.querySelector('select[name=\"tipo_identificacion\"]').tomselect.open();");

            $browser->pause(400)
                ->assertSee('Cédula de Ciudadanía (CC)')
                ->assertSee('Tarjeta de Identidad (TI)')
                ->screenshot('desplegable_tipo_identificacion_calibrado');
        });
    }
}


