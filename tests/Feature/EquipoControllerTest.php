<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $tecnico;

    protected Cliente $cliente;

    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tecnico = User::factory()->create(['role' => 'tecnico']);

        $this->cliente = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'identificacion' => '12345678',
            'movil' => '3001234567',
            'email' => 'juan@test.com',
        ]);

        $this->proveedor = Proveedor::create([
            'nombre_razon_social' => 'Proveedor Test',
            'identificacion' => '900123456',
            'tipo_entidad' => 'empresa',
            'email' => 'prov@test.com',
            'telefono' => '3001234567',
            'direccion' => 'Calle 100',
        ]);
    }

    public function test_admin_can_view_equipos_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('equipos.index'));
        $response->assertStatus(200);
        $response->assertSee('Equipos');
    }

    public function test_tecnico_can_view_equipos_index(): void
    {
        $response = $this->actingAs($this->tecnico)->get(route('equipos.index'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_view_equipos_index(): void
    {
        $guest = User::factory()->create(['role' => 'invitado']);
        $response = $this->actingAs($guest)->get(route('equipos.index'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_equipo_with_cliente(): void
    {
        $response = $this->actingAs($this->admin)->post(route('equipos.store'), [
            'nombre' => 'Laptop Dell',
            'marca' => 'Dell',
            'modelo' => 'Inspiron',
            'serie' => 'SN-DELL-001',
            'propietario_global' => 'Cliente:'.$this->cliente->id,
            'observacion' => 'Equipo de cliente',
        ]);

        $response->assertRedirect(route('equipos.index'));
        $this->assertDatabaseHas('equipos', [
            'nombre' => 'Laptop Dell',
            'serie' => 'SN-DELL-001',
            'cliente_id' => $this->cliente->id,
        ]);
    }

    public function test_admin_can_create_equipo_with_proveedor(): void
    {
        $response = $this->actingAs($this->admin)->post(route('equipos.store'), [
            'nombre' => 'Servidor HP',
            'marca' => 'HP',
            'modelo' => 'ProLiant',
            'serie' => 'SN-HP-001',
            'propietario_global' => 'Proveedor:'.$this->proveedor->id,
            'observacion' => 'Equipo de proveedor',
        ]);

        $response->assertRedirect(route('equipos.index'));
        $this->assertDatabaseHas('equipos', [
            'nombre' => 'Servidor HP',
            'serie' => 'SN-HP-001',
            'proveedor_id' => $this->proveedor->id,
        ]);
    }

    public function test_equipo_creation_requires_propietario(): void
    {
        $response = $this->actingAs($this->admin)->post(route('equipos.store'), [
            'nombre' => 'Sin Propietario',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-NOPROP-001',
        ]);

        $response->assertSessionHasErrors(['propietario_global']);
    }

    public function test_equipo_creation_rejects_duplicate_serie(): void
    {
        Equipo::create([
            'nombre' => 'Original',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-DUP-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('equipos.store'), [
            'nombre' => 'Duplicado',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-DUP-001',
            'propietario_global' => 'Cliente:'.$this->cliente->id,
        ]);

        $response->assertSessionHasErrors(['serie']);
    }

    public function test_admin_can_edit_equipo(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Original',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-EDIT-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('equipos.edit', $equipo));
        $response->assertStatus(200);
        $response->assertSee('Original');
    }

    public function test_admin_can_update_equipo(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Original',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-UPD-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('equipos.update', $equipo), [
            'nombre' => 'Actualizado',
            'marca' => 'Nueva Marca',
            'modelo' => 'Nuevo Modelo',
            'serie' => 'SN-UPD-001',
            'propietario_global' => 'Cliente:'.$this->cliente->id,
            'observacion' => 'Actualizado',
        ]);

        $response->assertRedirect(route('equipos.index'));
        $equipo->refresh();
        $this->assertEquals('Actualizado', $equipo->nombre);
        $this->assertEquals('Nueva Marca', $equipo->marca);
    }

    public function test_admin_can_anular_equipo(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Para Anular',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-ANUL-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('equipos.anular', $equipo), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $equipo->refresh();
        $this->assertFalse($equipo->active);
    }

    public function test_admin_can_reactivar_equipo(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Para Reactivar',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-REACT-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
            'active' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('equipos.anular', $equipo), [
            'password_confirm' => 'password',
        ]);

        $response->assertRedirect();
        $equipo->refresh();
        $this->assertTrue($equipo->active);
    }

    public function test_tecnico_requires_admin_password_to_anular_equipo(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Tecnico Anular',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-TEC-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
            'active' => true,
        ]);

        $response = $this->actingAs($this->tecnico)->post(route('equipos.anular', $equipo), [
            'admin_password' => 'password',
        ]);

        $response->assertRedirect();
        $equipo->refresh();
        $this->assertFalse($equipo->active);
    }

    public function test_admin_can_dar_de_baja_equipo(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Para Baja',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-BAJA-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
            'estado' => 'operativo',
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('equipos.dar-de-baja', $equipo), [
            'motivo_baja' => 'irreparable',
            'observacion_baja' => 'Placa base dañada',
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHas('success');
        $equipo->refresh();
        $this->assertEquals('dado_de_baja', $equipo->estado);
        $this->assertFalse($equipo->active);
        $this->assertEquals('irreparable', $equipo->motivo_baja);
    }

    public function test_admin_can_reactivar_equipo_from_baja(): void
    {
        $equipo = Equipo::create([
            'nombre' => 'Reactivar Baja',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'serie' => 'SN-REACT-BAJA-001',
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->admin->id,
            'estado' => 'dado_de_baja',
            'active' => false,
            'motivo_baja' => 'irreparable',
        ]);

        $response = $this->actingAs($this->admin)->post(route('equipos.reactivar', $equipo), [
            'password_confirm' => env('ADMIN_DEFAULT_PASSWORD', 'Admin123*'),
        ]);

        $response->assertSessionHas('success');
        $equipo->refresh();
        $this->assertEquals('operativo', $equipo->estado);
        $this->assertTrue($equipo->active);
        $this->assertNull($equipo->motivo_baja);
    }
}
