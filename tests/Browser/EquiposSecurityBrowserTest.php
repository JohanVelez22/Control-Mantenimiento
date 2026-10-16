<?php

namespace Tests\Browser;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class EquiposSecurityBrowserTest extends DuskTestCase
{
    public function test_login_and_equipos_security_in_browser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_dusk@test.com'],
            [
                'name' => 'Admin Dusk',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        $cliente = Cliente::firstOrCreate(
            ['identificacion' => 'CC-DUSK-01'],
            [
                'nombres' => 'Cliente',
                'apellidos' => 'Dusk',
                'tipo_identificacion' => 'cedula_ciudadania',
                'genero' => 'masculino',
                'tipo_cliente' => 'cliente',
                'movil' => '3009998877',
                'active' => true,
            ]
        );

        $equipoBaja = Equipo::updateOrCreate(
            ['serie' => 'SN-DUSK-BAJA-01'],
            [
                'nombre' => 'Laptop Baja Dusk',
                'marca' => 'Dell',
                'modelo' => 'Latitude',
                'cliente_id' => $cliente->id,
                'user_id' => $admin->id,
                'estado' => 'dado_de_baja',
                'active' => false,
                'motivo_baja' => 'irreparable',
            ]
        );

        $this->browse(function (Browser $browser) use ($admin, $equipoBaja) {
            $browser->loginAs($admin)
                ->visit('/equipos')
                ->assertPathIs('/equipos')
                ->type('#search-equipos', 'SN-DUSK-BAJA-01')
                ->pause(300)
                ->assertSee('Laptop Baja Dusk')
                ->assertSee('Dado de Baja')
                ->assertDontSeeLink(route('equipos.edit', $equipoBaja))
                ->screenshot('equipos_security_index');

            // Intentar entrar por URL directa a la edición de un equipo dado de baja
            $browser->visit(route('equipos.edit', $equipoBaja))
                ->assertPathIs('/equipos')
                ->waitForText('Por seguridad, no se puede editar un equipo')
                ->screenshot('equipos_security_blocked');
        });
    }
}
