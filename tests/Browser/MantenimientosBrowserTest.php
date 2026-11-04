<?php

namespace Tests\Browser;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class MantenimientosBrowserTest extends DuskTestCase
{
    public function test_mantenimientos_lifecycle_in_browser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_mant_dusk@test.com'],
            [
                'name' => 'Admin Mant Dusk',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'active' => true,
            ]
        );

        $cliente = Cliente::firstOrCreate(
            ['identificacion' => 'CC-MANT-DUSK'],
            [
                'nombres' => 'Cliente Mantenimiento',
                'apellidos' => 'Dusk',
                'tipo_identificacion' => 'cedula_ciudadania',
                'genero' => 'masculino',
                'tipo_cliente' => 'cliente',
                'movil' => '3201112233',
                'active' => true,
            ]
        );

        $equipo = Equipo::firstOrCreate(
            ['serie' => 'SN-MANT-DUSK-01'],
            [
                'nombre' => 'PC Gamer Mantenimiento Dusk',
                'marca' => 'Asus',
                'modelo' => 'ROG',
                'cliente_id' => $cliente->id,
                'user_id' => $admin->id,
                'estado' => 'operativo',
                'active' => true,
            ]
        );

        $tecnico = Tecnico::firstOrCreate(
            ['identificacion' => 'TEC-DUSK-01'],
            [
                'nombre' => 'Tecnico Dusk',
                'especialidad' => 'Hardware y Software',
                'movil' => '3001234567',
                'active' => true,
            ]
        );

        $mantenimiento = Mantenimiento::firstOrCreate(
            ['id_orden' => 'ORD-DUSK-999'],
            [
                'equipo_id' => $equipo->id,
                'tecnico_id' => $tecnico->id,
                'user_id' => $admin->id,
                'tipo' => 'preventivo',
                'reparacion' => 'software',
                'descripcion' => 'Diagnóstico inicial Dusk: mantenimiento preventivo general',
                'estado' => 'pendiente',
                'costo' => 120000,
                'fecha_entrada' => now()->format('Y-m-d'),
                'anulado' => false,
            ]
        );

        $this->browse(function (Browser $browser) use ($admin, $mantenimiento) {
            // 1. Visitar listado de órdenes de mantenimiento
            $browser->loginAs($admin)
                ->visit('/mantenimientos')
                ->assertPathIs('/mantenimientos')
                ->assertSee('Órdenes de Mantenimiento')
                ->assertSee('ORD-DUSK-999')
                ->assertSee('PC Gamer Mantenimiento Dusk')
                ->screenshot('mantenimientos_tabla_general');

            // 2. Usar el filtro de búsqueda interactivo en la tabla
            $browser->type('#search-mantenimientos', 'ORD-DUSK-999')
                ->pause(200)
                ->assertSee('ORD-DUSK-999');

            // 3. Ver detalle completo de la orden
            $browser->visit(route('mantenimientos.show', $mantenimiento->id))
                ->assertPathIs("/mantenimientos/{$mantenimiento->id}")
                ->assertSee('ORD-DUSK-999')
                ->assertSee('Asus')
                ->assertSee('Diagnóstico inicial Dusk')
                ->screenshot('mantenimientos_detalle_orden');
        });

        // Limpieza
        $mantenimiento->forceDelete();
    }
}
