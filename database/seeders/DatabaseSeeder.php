<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Electronica;
use App\Models\ConceptoCaja;
use App\Models\MovimientoCaja;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\CierreCaja;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear un Administrador fijo para pruebas
        $admin = User::factory()->create([
            'name' => 'Admin Sistema',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin123*'),
            'role' => 'admin',
        ]);

        // 2. Crear Usuarios (9 adicionales para llegar a 10)
        $users = User::factory(9)->create(['role' => 'tecnico']);
        $users->push($admin);

        // 3. Crear Técnicos (10)
        $tecnicos = Tecnico::factory(10)->create();

        // 4. Crear Clientes (10)
        $clientes = Cliente::factory(10)->create();

        // 5. Crear Equipos (10)
        $equipos = Equipo::factory(10)->recycle($clientes)->recycle($users)->create();

        // 6. Crear Mantenimientos (10)
        Mantenimiento::factory(10)->recycle($equipos)->recycle($tecnicos)->recycle($users)->create();

        // 7. Crear Proveedores (10)
        for($i=1; $i<=10; $i++) {
            Proveedor::create([
                'tipo_entidad' => 'empresa',
                'identificacion' => 'NIT-10000000'.$i,
                'nombre_razon_social' => 'Proveedor de Prueba S.A. '.$i,
                'telefono' => '300000000'.$i,
                'email' => 'proveedor'.$i.'@ejemplo.com',
                'direccion' => 'Calle Falsa '.$i,
            ]);
        }

        // 8. Crear Stock / Inventario (10)
        $stocks = [];
        for($i=1; $i<=10; $i++) {
            $costo = rand(50, 200) * 100;
            $venta = $costo * 1.5;
            $stocks[] = Stock::create([
                'codigo' => 'PRD-00'.$i,
                'producto' => 'Repuesto Generico Parte '.$i,
                'cantidad' => rand(5, 50),
                'precio_compra' => $costo,
                'precio_venta' => $venta,
                'utilidad' => 50,
                'precio_tecnico' => $venta,
            ]);
        }

        // 9. Crear Electrónica (10)
        for($i=1; $i<=10; $i++) {
            Electronica::create([
                'id_orden' => 'ELC-'.$i,
                'equipo_id' => $equipos->random()->id,
                'user_id' => $admin->id,
                'tecnico_id' => $tecnicos->random()->id,
                'fecha_entrada' => now()->subDays(rand(1, 30)),
                'fecha_salida' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
                'estado' => rand(0, 1) ? 'terminado' : 'pendiente',
                'tipo' => rand(0, 1) ? 'preventivo' : 'correctivo',
                'costo' => rand(500, 2000) * 100,
                'descripcion_problema' => 'Descripción del problema de prueba '.$i,
            ]);
        }

        // 10. Conceptos de Caja (ya creados en la migración)
        $conceptoIngreso = ConceptoCaja::firstOrCreate(['nombre' => 'Pago de mantenimiento']);
        $conceptoEgreso = ConceptoCaja::firstOrCreate(['nombre' => 'Pago de servicios']);

        // 11. Movimientos de Caja (10)
        for($i=1; $i<=10; $i++) {
            $esIngreso = rand(0, 1);
            MovimientoCaja::create([
                'persona' => 'Persona de Prueba '.$i,
                'fecha' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                'tipo_movimiento' => $esIngreso ? 'ingreso' : 'egreso',
                'concepto_id' => $esIngreso ? $conceptoIngreso->id : $conceptoEgreso->id,
                'user_id' => $admin->id,
                'monto' => rand(100, 1000) * 100,
                'tipo_pago' => 'efectivo',
                'descripcion' => 'Movimiento de prueba '.$i,
            ]);
        }

        // 12. Facturas de Compra/Venta (10)
        for($i=1; $i<=10; $i++) {
            $tipo = rand(0, 1) ? 'compra' : 'venta';
            $facturable_type = $tipo === 'compra' ? Proveedor::class : Cliente::class;
            $facturable_id = $tipo === 'compra' ? Proveedor::inRandomOrder()->first()->id : Cliente::inRandomOrder()->first()->id;
            
            $factura = Factura::create([
                'numero_factura' => 'FACT-00'.$i,
                'tipo_movimiento' => $tipo,
                'estado' => 'emitida',
                'facturable_type' => $facturable_type,
                'facturable_id' => $facturable_id,
                'total_documento' => 0,
                'total_pagado' => 0,
                'observaciones' => 'Factura de prueba '.$i,
                'fecha' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                'user_id' => $admin->id,
            ]);

            // Add 1 random item
            $stock = $stocks[array_rand($stocks)];
            $precio = $tipo === 'compra' ? $stock->precio_compra : $stock->precio_venta;
            $cant = rand(1, 5);
            $total_item = $precio * $cant;

            FacturaItem::create([
                'factura_id' => $factura->id,
                'stock_id' => $stock->id,
                'cantidad' => $cant,
                'precio_unitario' => $precio,
            ]);

            $factura->update([
                'total_documento' => $total_item,
                'total_pagado' => $total_item,
            ]);
        }
        
        // 13. Cierre de Caja (10)
        for($i=1; $i<=10; $i++) {
            CierreCaja::create([
                'fecha' => now()->subDays($i)->format('Y-m-d'),
                'user_id' => $admin->id,
                'total_ingresos' => rand(500, 2000) * 100,
                'total_egresos' => rand(100, 500) * 100,
                'efectivo' => rand(200, 1000) * 100,
                'consignacion' => rand(100, 500) * 100,
                'saldo_final' => rand(400, 1500) * 100,
                'num_movimientos' => rand(5, 20),
                'bloqueado' => true,
                'observaciones' => 'Cierre automático '.$i,
            ]);
        }
    }
}
