<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TecnicoPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_repuesto_aplica_precio_tecnico_si_cliente_es_tecnico(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clienteTecnico = Cliente::create([
            'nombres' => 'Juan',
            'apellidos' => 'Técnico',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '12345678',
            'genero' => 'masculino',
            'tipo_cliente' => 'tecnico',
            'movil' => '3001234567',
        ]);

        $equipo = Equipo::create([
            'nombre' => 'Laptop Gamer',
            'marca' => 'Asus',
            'modelo' => 'TUF',
            'serie' => 'SN-112233',
            'cliente_id' => $clienteTecnico->id,
            'user_id' => $admin->id,
        ]);

        $tecnicoStaff = Tecnico::create([
            'nombre' => 'Tecnico Empleado',
            'identificacion' => '111222',
            'especialidad' => 'General',
            'movil' => '3000000000',
            'active' => true,
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-100',
            'equipo_id' => $equipo->id,
            'user_id' => $admin->id,
            'tecnico_id' => $tecnicoStaff->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Mantenimiento prueba',
            'costo' => 50000,
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'estado' => 'pendiente',
        ]);

        $stock = Stock::create([
            'codigo' => 'MEM-16GB',
            'producto' => 'Memoria RAM 16GB',
            'categoria' => 'Repuestos',
            'subcategoria' => 'Memorias',
            'cantidad' => 10,
            'precio_compra' => 100000,
            'utilidad' => 50,
            'precio_venta' => 150000,
            'precio_tecnico' => 125000,
        ]);

        $response = $this->post(route('mantenimientos.stocks.store', $mantenimiento), [
            'stock_id' => $stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();

        // Verificar que en pivot se asignó el precio_tecnico (125000) y no precio_venta (150000)
        $mantenimiento->refresh();
        $this->assertEquals(1, $mantenimiento->stocks()->count());
        $pivot = $mantenimiento->stocks()->first()->pivot;
        $this->assertEquals(125000, (float)$pivot->precio_unitario);
        $this->assertEquals(175000, (float)$mantenimiento->costo); // 50000 + 125000
    }

    public function test_repuesto_aplica_precio_venta_si_cliente_es_normal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clienteNormal = Cliente::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Normal',
            'tipo_identificacion' => 'cedula_ciudadania',
            'identificacion' => '87654321',
            'genero' => 'masculino',
            'tipo_cliente' => 'cliente',
            'movil' => '3007654321',
        ]);

        $equipo = Equipo::create([
            'nombre' => 'PC Oficina',
            'marca' => 'Dell',
            'modelo' => 'Optiplex',
            'serie' => 'SN-998877',
            'cliente_id' => $clienteNormal->id,
            'user_id' => $admin->id,
        ]);

        $tecnicoStaff = Tecnico::create([
            'nombre' => 'Tecnico Empleado 2',
            'identificacion' => '333444',
            'especialidad' => 'General',
            'movil' => '3000000001',
            'active' => true,
        ]);

        $mantenimiento = Mantenimiento::create([
            'id_orden' => 'ORD-101',
            'equipo_id' => $equipo->id,
            'user_id' => $admin->id,
            'tecnico_id' => $tecnicoStaff->id,
            'fecha_entrada' => now(),
            'descripcion' => 'Mantenimiento normal prueba',
            'costo' => 40000,
            'tipo' => 'correctivo',
            'reparacion' => 'hardware',
            'estado' => 'pendiente',
        ]);

        $stock = Stock::create([
            'codigo' => 'SSD-500GB',
            'producto' => 'Disco SSD 500GB',
            'categoria' => 'Repuestos',
            'subcategoria' => 'Almacenamiento',
            'cantidad' => 10,
            'precio_compra' => 100000,
            'utilidad' => 50,
            'precio_venta' => 150000,
            'precio_tecnico' => 125000,
        ]);

        $response = $this->post(route('mantenimientos.stocks.store', $mantenimiento), [
            'stock_id' => $stock->id,
            'cantidad' => 1,
        ]);

        $response->assertRedirect();

        // Verificar que en pivot se asignó el precio_venta (150000)
        $mantenimiento->refresh();
        $this->assertEquals(1, $mantenimiento->stocks()->count());
        $pivot = $mantenimiento->stocks()->first()->pivot;
        $this->assertEquals(150000, (float)$pivot->precio_unitario);
        $this->assertEquals(190000, (float)$mantenimiento->costo); // 40000 + 150000
    }
}
