<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteControllerTest extends TestCase
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

    public function test_admin_can_view_clientes_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clientes.index'));
        $response->assertStatus(200);
        $response->assertSee('Clientes');
    }

    public function test_tecnico_can_view_clientes_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('clientes.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_clientes_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('clientes.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_cliente(): void
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '87654321',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3007654321',
            'email' => 'carlos@test.com',
            'direccion' => 'Calle 45 #12-34',
        ]);

        $response->assertRedirect(route('clientes.index'));
        $this->assertDatabaseHas('clientes', [
            'nombres' => 'Carlos',
            'apellidos' => 'Mendoza',
            'identificacion' => '87654321',
        ]);
    }

    public function test_cliente_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
            'nombres' => 'Sin Apellido',
        ]);

        $response->assertSessionHasErrors(['apellidos', 'identificacion', 'tipo_identificacion', 'genero', 'tipo_cliente', 'movil']);
    }

    public function test_cliente_creation_rejects_duplicate_identificacion(): void
    {
        Cliente::create([
            'nombres' => 'Original',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '11111111',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3001111111',
        ]);

        $response = $this->actingAs($this->admin)->post(route('clientes.store'), [
            'nombres' => 'Duplicado',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '11111111',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3002222222',
        ]);

        $response->assertSessionHasErrors(['identificacion']);
    }

    public function test_admin_can_edit_cliente(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Original',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '22222222',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3003333333',
        ]);

        $response = $this->actingAs($this->admin)->get(route('clientes.edit', $cliente));
        $response->assertStatus(200);
        $response->assertSee('Original');
    }

    public function test_admin_can_update_cliente(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Original',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '33333333',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3004444444',
        ]);

        $response = $this->actingAs($this->admin)->put(route('clientes.update', $cliente), [
            'nombres' => 'Actualizado',
            'apellidos' => 'Nuevo',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '33333333',
            'genero' => 'femenino',
            'tipo_cliente' => 'tecnico',
            'movil' => '3005555555',
            'email' => 'actualizado@test.com',
            'direccion' => 'Nueva direccion',
        ]);

        $response->assertRedirect(route('clientes.index'));
        $cliente->refresh();
        $this->assertEquals('Actualizado', $cliente->nombres);
        $this->assertEquals('Nuevo', $cliente->apellidos);
        $this->assertEquals('femenino', $cliente->genero);
        $this->assertEquals('tecnico', $cliente->tipo_cliente);
    }

    public function test_admin_can_anular_cliente(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Para Anular',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '44444444',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3006666666',
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('clientes.anular', $cliente), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $cliente->refresh();
        $this->assertFalse($cliente->active);
    }

    public function test_admin_can_reactivar_cliente(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Para Reactivar',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '55555555',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3007777777',
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('clientes.anular', $cliente), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $cliente->refresh();
        $this->assertTrue($cliente->active);
    }

    public function test_tecnico_requires_admin_password_to_anular_cliente(): void
    {
        $cliente = Cliente::create([
            'nombres' => 'Tecnico Anular',
            'apellidos' => 'Cliente',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '66666666',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3008888888',
            'active' => true,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('clientes.anular', $cliente), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $cliente->refresh();
        $this->assertFalse($cliente->active);
    }

    public function test_municipios_api_returns_municipios_for_departamento(): void
    {
        $response = $this->actingAs($this->admin)->get(route('api.municipios', ['departamento' => 'Antioquia']));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertContains('Medellín', $data);
        $this->assertContains('Envigado', $data);
    }

    public function test_municipios_api_returns_empty_for_invalid_departamento(): void
    {
        $response = $this->actingAs($this->admin)->get(route('api.municipios', ['departamento' => 'Inexistente']));
        $response->assertStatus(200);
        $this->assertEquals([], $response->json());
    }
}
