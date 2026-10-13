<?php

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);
    }

    public function test_admin_can_view_proveedores_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('proveedores.index'));
        $response->assertStatus(200);
        $response->assertSee('Proveedores');
    }

    public function test_tecnico_can_view_proveedores_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('proveedores.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_proveedores_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('proveedores.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_proveedor(): void
    {
        $response = $this->actingAs($this->admin)->post(route('proveedores.store'), [
            'tipo_entidad' => 'empresa',
            'identificacion' => '900987654',
            'nombre_razon_social' => 'Proveedor Test SAS',
            'tipo_identificacion' => 'nit',
            'telefono' => '3109876543',
            'email' => 'proveedor@test.com',
            'direccion' => 'Carrera 10 #20-30',
        ]);

        $response->assertRedirect(route('proveedores.index'));
        $this->assertDatabaseHas('proveedores', [
            'nombre_razon_social' => 'Proveedor Test SAS',
            'identificacion' => '900987654',
        ]);
    }

    public function test_proveedor_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('proveedores.store'), [
            'nombre_razon_social' => 'Sin ID',
        ]);

        $response->assertSessionHasErrors(['tipo_entidad', 'identificacion', 'tipo_identificacion']);
    }

    public function test_proveedor_creation_rejects_duplicate_identificacion(): void
    {
        Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900111111',
            'nombre_razon_social' => 'Original',
            'tipo_identificacion' => 'nit',
            'telefono' => '3101111111',
        ]);

        $response = $this->actingAs($this->admin)->post(route('proveedores.store'), [
            'tipo_entidad' => 'empresa',
            'identificacion' => '900111111',
            'nombre_razon_social' => 'Duplicado',
            'tipo_identificacion' => 'nit',
            'telefono' => '3102222222',
        ]);

        $response->assertSessionHasErrors(['identificacion']);
    }

    public function test_admin_can_edit_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900333333',
            'nombre_razon_social' => 'Original Proveedor',
            'tipo_identificacion' => 'nit',
            'telefono' => '3103333333',
        ]);

        $response = $this->actingAs($this->admin)->get(route('proveedores.edit', $proveedor));
        $response->assertStatus(200);
        $response->assertSee('Original Proveedor');
    }

    public function test_admin_can_update_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900444444',
            'nombre_razon_social' => 'Original Proveedor',
            'tipo_identificacion' => 'nit',
            'telefono' => '3104444444',
        ]);

        $response = $this->actingAs($this->admin)->put(route('proveedores.update', $proveedor), [
            'tipo_entidad' => 'persona',
            'identificacion' => '900444444',
            'nombre_razon_social' => 'Proveedor Actualizado',
            'tipo_identificacion' => 'cedula_ciudadania',
            'telefono' => '3105555555',
            'email' => 'actualizado@test.com',
            'direccion' => 'Nueva direccion',
        ]);

        $response->assertRedirect(route('proveedores.index'));
        $proveedor->refresh();
        $this->assertEquals('persona', $proveedor->tipo_entidad);
        $this->assertEquals('Proveedor Actualizado', $proveedor->nombre_razon_social);
        $this->assertEquals('cedula_ciudadania', $proveedor->tipo_identificacion);
    }

    public function test_admin_can_anular_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900666666',
            'nombre_razon_social' => 'Para Anular Proveedor',
            'tipo_identificacion' => 'nit',
            'telefono' => '3106666666',
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('proveedores.anular', $proveedor), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $proveedor->refresh();
        $this->assertFalse($proveedor->active);
    }

    public function test_admin_can_reactivar_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900777777',
            'nombre_razon_social' => 'Para Reactivar Proveedor',
            'tipo_identificacion' => 'nit',
            'telefono' => '3107777777',
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('proveedores.anular', $proveedor), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $proveedor->refresh();
        $this->assertTrue($proveedor->active);
    }

    public function test_tecnico_requires_admin_password_to_anular_proveedor(): void
    {
        $proveedor = Proveedor::create([
            'tipo_entidad' => 'empresa',
            'identificacion' => '900888888',
            'nombre_razon_social' => 'Tecnico Anular Proveedor',
            'tipo_identificacion' => 'nit',
            'telefono' => '3108888888',
            'active' => true,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('proveedores.anular', $proveedor), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $proveedor->refresh();
        $this->assertFalse($proveedor->active);
    }
}
