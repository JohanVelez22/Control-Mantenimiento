<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConfiguracionEmpresaYAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected User $invitado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);
        $this->invitado = User::factory()->create(['role' => 'invitado']);
    }

    public function test_admin_can_update_empresa_datos_completos(): void
    {
        $response = $this->actingAs($this->admin)->post(route('configuracion.update'), [
            'nombre' => 'Tecni Systemas Palmira SAS',
            'nit' => '900987654-3',
            'telefono' => '3123456789',
            'direccion' => 'Calle 30 # 25-10 Centro',
            'correo' => 'contacto@tecnisystemas.com',
            'pie_pagina_factura' => 'Gracias por su compra. Garantía 30 días.',
            'formato_factura' => 'pos',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('configuraciones', [
            'nombre' => 'Tecni Systemas Palmira SAS',
            'nit' => '900987654-3',
            'telefono' => '3123456789',
            'direccion' => 'Calle 30 # 25-10 Centro',
            'correo' => 'contacto@tecnisystemas.com',
            'pie_pagina_factura' => 'Gracias por su compra. Garantía 30 días.',
            'formato_factura' => 'pos',
        ]);
    }

    public function test_non_admin_cannot_access_or_update_configuracion(): void
    {
        $resTecnicoIndex = $this->actingAs($this->tecnico)->get(route('configuracion.index'));
        $resTecnicoIndex->assertRedirect(route('dashboard'));

        $resTecnicoUpdate = $this->actingAs($this->tecnico)->post(route('configuracion.update'), [
            'nombre' => 'Intento Hacker',
        ]);
        $resTecnicoUpdate->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('configuraciones', ['nombre' => 'Intento Hacker']);
    }

    public function test_auditoria_eventos_registra_creacion_y_actualizacion(): void
    {
        // Al crear un usuario (usa Auditable), debe registrarse evento 'creado'
        $nuevoUsuario = User::create([
            'name' => 'Auditoria Test',
            'email' => 'audit.user@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'tecnico',
            'active' => true,
        ]);

        $this->assertDatabaseHas('eventos', [
            'accion' => 'creado',
            'modelo_tipo' => 'App\\Models\\User',
            'modelo_id' => $nuevoUsuario->id,
        ]);

        // Al inactivarlo/anularlo, debe registrar evento 'anulado'
        $nuevoUsuario->update(['active' => false]);

        $this->assertDatabaseHas('eventos', [
            'accion' => 'anulado',
            'modelo_tipo' => 'App\\Models\\User',
            'modelo_id' => $nuevoUsuario->id,
        ]);
    }

    public function test_admin_can_view_eventos_index_and_detail(): void
    {
        $evento = Evento::create([
            'user_id' => $this->admin->id,
            'accion' => 'creado',
            'descripcion' => 'Creó un nuevo registro de prueba',
            'modelo_tipo' => 'User',
            'modelo_id' => $this->admin->id,
            'datos_nuevos' => json_encode(['name' => 'Admin Test']),
        ]);

        $resIndex = $this->actingAs($this->admin)->get(route('eventos.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Registro de Eventos');

        $resShow = $this->actingAs($this->admin)->get(route('eventos.show', $evento));
        $resShow->assertStatus(200);
    }

    public function test_invitado_cannot_access_eventos(): void
    {
        $response = $this->actingAs($this->invitado)->get(route('eventos.index'));
        $this->assertContains($response->status(), [302, 403]);
    }
}
