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

        $this->browse(function (Browser $browser) use ($admin) {
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
        });
    }
}
