<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\Proveedor;
use App\Models\Equipo;
use App\Models\Stock;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\ArqueoCaja;
use Carbon\Carbon;

class SeedDemoData extends Command
{
    protected $signature = 'app:seed-demo-data';
    protected $description = 'Puebla la base de datos con información realista (5 registros por módulo) para pruebas de despliegue.';

    public function handle()
    {
        $this->info('Iniciando la siembra de datos de prueba realistas...');

        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->error('No se encontró un usuario administrador. Por favor, asegúrese de tener al menos un usuario en la tabla users.');
            return;
        }

        DB::beginTransaction();

        try {
            // 1. CLIENTES (5)
            $this->info('Creando 5 Clientes...');
            $clientesData = [
                ['nombres' => 'Carlos Alberto', 'apellidos' => 'Gómez Ruiz', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '1020304050', 'genero' => 'masculino', 'tipo_cliente' => 'cliente', 'movil' => '3001234567', 'email' => 'carlos.gomez@gmail.com', 'departamento' => 'Antioquia', 'municipio' => 'Medellín', 'active' => true],
                ['nombres' => 'María Fernanda', 'apellidos' => 'López Toro', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '1098765432', 'genero' => 'femenino', 'tipo_cliente' => 'cliente', 'movil' => '3109876543', 'email' => 'maria.lopez@hotmail.com', 'departamento' => 'Cundinamarca', 'municipio' => 'Bogotá', 'active' => true],
                ['nombres' => 'Empresa Soluciones IT', 'apellidos' => 'S.A.S', 'tipo_identificacion' => 'nit', 'identificacion' => '900123456-1', 'genero' => 'indefinido', 'tipo_cliente' => 'cliente', 'movil' => '3205554433', 'email' => 'contacto@solucionesit.com', 'departamento' => 'Valle del Cauca', 'municipio' => 'Cali', 'active' => true],
                ['nombres' => 'Jorge Iván', 'apellidos' => 'Pérez Soto', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '71555666', 'genero' => 'masculino', 'tipo_cliente' => 'cliente', 'movil' => '3157778899', 'email' => 'jorge.perez@yahoo.es', 'departamento' => 'Atlántico', 'municipio' => 'Barranquilla', 'active' => true],
                ['nombres' => 'Diana Marcela', 'apellidos' => 'Quintero', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '43222111', 'genero' => 'femenino', 'tipo_cliente' => 'cliente', 'movil' => '3012223344', 'email' => 'diana.quintero@outlook.com', 'departamento' => 'Santander', 'municipio' => 'Bucaramanga', 'active' => true],
            ];
            $clientes = [];
            foreach ($clientesData as $c) {
                $clientes[] = Cliente::create($c);
            }

            // 2. TÉCNICOS (5)
            $this->info('Creando 5 Técnicos...');
            $tecnicosData = [
                ['nombre' => 'Andrés Felipe Martínez', 'identificacion' => '102030101', 'especialidad' => 'Hardware y Redes', 'movil' => '3001112233', 'active' => true],
                ['nombre' => 'Roberto Sánchez', 'identificacion' => '102030102', 'especialidad' => 'Electrónica de precisión', 'movil' => '3102223344', 'active' => true],
                ['nombre' => 'Luis Fernando Osorio', 'identificacion' => '102030103', 'especialidad' => 'Mantenimiento Preventivo', 'movil' => '3153334455', 'active' => true],
                ['nombre' => 'Miguel Ángel Rojas', 'identificacion' => '102030104', 'especialidad' => 'Software y Sistemas Operativos', 'movil' => '3204445566', 'active' => true],
                ['nombre' => 'Héctor Fabio Castaño', 'identificacion' => '102030105', 'especialidad' => 'Impresoras y Periféricos', 'movil' => '3015556677', 'active' => true],
            ];
            $tecnicos = [];
            foreach ($tecnicosData as $t) {
                $tecnicos[] = Tecnico::create($t);
            }

            // 3. PROVEEDORES (5)
            $this->info('Creando 5 Proveedores...');
            $proveedoresData = [
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '800999888-2', 'nombre_razon_social' => 'Distribuidora TecnoPartes', 'telefono' => '3009998877', 'email' => 'ventas@tecnopartes.com', 'active' => true],
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '900888777-3', 'nombre_razon_social' => 'Mayorista Electrónica S.A', 'telefono' => '3108887766', 'email' => 'gerencia@mayoristaelec.com', 'active' => true],
                ['tipo_entidad' => 'persona', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '10203040', 'nombre_razon_social' => 'Importaciones Juan David', 'telefono' => '3157776655', 'email' => 'juandavid.import@gmail.com', 'active' => true],
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '901222333-4', 'nombre_razon_social' => 'Suministros Globales IT', 'telefono' => '3206665544', 'email' => 'pedidos@globalesit.com', 'active' => true],
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '800555444-1', 'nombre_razon_social' => 'Partes y Pantallas de Colombia', 'telefono' => '3014443322', 'email' => 'info@partesypantallas.com', 'active' => true],
            ];
            $proveedores = [];
            foreach ($proveedoresData as $p) {
                $proveedores[] = Proveedor::create($p);
            }

            // 4. EQUIPOS (5)
            $this->info('Creando 5 Equipos...');
            $equiposData = [
                ['nombre' => 'Portátil Corporativo', 'marca' => 'Dell', 'modelo' => 'Latitude 5420', 'serie' => 'DLL-5420-XYZ1', 'cliente_id' => $clientes[0]->id, 'observacion' => 'Equipo de trabajo principal', 'user_id' => $admin->id],
                ['nombre' => 'PC de Escritorio', 'marca' => 'HP', 'modelo' => 'ProDesk 600 G6', 'serie' => 'HP-PD600-ABC2', 'cliente_id' => $clientes[1]->id, 'observacion' => 'Lentitud al arrancar', 'user_id' => $admin->id],
                ['nombre' => 'Servidor Torre', 'marca' => 'Lenovo', 'modelo' => 'ThinkSystem ST250', 'serie' => 'LNV-ST250-9988', 'cliente_id' => $clientes[2]->id, 'observacion' => 'Servidor de base de datos de la empresa', 'user_id' => $admin->id],
                ['nombre' => 'Impresora Multifuncional', 'marca' => 'Epson', 'modelo' => 'L4150', 'serie' => 'EPS-L4150-5544', 'cliente_id' => $clientes[3]->id, 'observacion' => 'Atasco de papel constante', 'user_id' => $admin->id],
                ['nombre' => 'Consola de Videojuegos', 'marca' => 'Sony', 'modelo' => 'PlayStation 5', 'serie' => 'PS5-XCX-0011', 'cliente_id' => $clientes[4]->id, 'observacion' => 'No da video por HDMI', 'user_id' => $admin->id],
            ];
            $equipos = [];
            foreach ($equiposData as $e) {
                $equipos[] = Equipo::create($e);
            }

            // 5. STOCKS (5)
            $this->info('Creando 5 Artículos de Stock...');
            $stocksData = [
                ['producto' => 'Disco Duro SSD 500GB', 'marca' => 'Kingston', 'modelo' => 'A400', 'codigo' => 'SSD-500K', 'cantidad' => 15, 'precio_compra' => 120000, 'precio_venta' => 180000, 'proveedor_id' => $proveedores[0]->id, 'categoria' => 'Almacenamiento', 'user_id' => $admin->id],
                ['producto' => 'Memoria RAM 8GB DDR4', 'marca' => 'Crucial', 'modelo' => 'CB8GU', 'codigo' => 'RAM-8GBC', 'cantidad' => 20, 'precio_compra' => 80000, 'precio_venta' => 130000, 'proveedor_id' => $proveedores[1]->id, 'categoria' => 'Memorias', 'user_id' => $admin->id],
                ['producto' => 'Pantalla Portátil 15.6 LED', 'marca' => 'BOE', 'modelo' => 'NT156FHM', 'codigo' => 'PAN-156L', 'cantidad' => 5, 'precio_compra' => 220000, 'precio_venta' => 350000, 'proveedor_id' => $proveedores[2]->id, 'categoria' => 'Repuestos', 'user_id' => $admin->id],
                ['producto' => 'Puerto HDMI Consola PS5', 'marca' => 'Sony', 'modelo' => 'Genérico', 'codigo' => 'HDM-PS5', 'cantidad' => 10, 'precio_compra' => 25000, 'precio_venta' => 60000, 'proveedor_id' => $proveedores[3]->id, 'categoria' => 'Electrónica', 'user_id' => $admin->id],
                ['producto' => 'Botella Tinta Negra Epson', 'marca' => 'Epson', 'modelo' => 'T504', 'codigo' => 'TIN-504N', 'cantidad' => 30, 'precio_compra' => 28000, 'precio_venta' => 45000, 'proveedor_id' => $proveedores[4]->id, 'categoria' => 'Suministros', 'user_id' => $admin->id],
            ];
            $stocks = [];
            foreach ($stocksData as $s) {
                $stocks[] = Stock::create($s);
            }

            // 6. COTIZACIONES (5)
            $this->info('Creando 5 Cotizaciones...');
            $ordenService = app(\App\Services\OrdenService::class);
            for ($i = 1; $i <= 5; $i++) {
                $cot = Cotizacion::create([
                    'codigo' => $ordenService->siguiente('COT-', Cotizacion::class, 'codigo'),
                    'cliente_id' => $clientes[$i-1]->id,
                    'user_id' => $admin->id,
                    'fecha' => Carbon::now()->subDays(rand(1, 10))->toDateString(),
                    'validez_dias' => 15,
                    'total' => 0,
                    'estado' => $i === 1 ? 'aprobada' : ($i === 2 ? 'rechazada' : 'pendiente'),
                    'notas' => 'Cotización de prueba generada automáticamente.',
                ]);

                // Agregar 2 items a cada cotización
                $total = 0;
                $s1 = $stocks[rand(0, 4)];
                $s2 = $stocks[rand(0, 4)];
                
                CotizacionItem::create(['cotizacion_id' => $cot->id, 'tipo' => 'stock', 'item_id' => $s1->id, 'descripcion' => $s1->producto, 'cantidad' => 1, 'precio_unitario' => $s1->precio_venta]);
                $total += $s1->precio_venta;

                CotizacionItem::create(['cotizacion_id' => $cot->id, 'tipo' => 'libre', 'item_id' => null, 'descripcion' => 'Mano de obra especializada', 'cantidad' => 1, 'precio_unitario' => 85000]);
                $total += 85000;

                $cot->update(['total' => $total]);
            }

            // 7. OPERACIONES (FACTURAS): 3 Ventas, 2 Compras
            $this->info('Creando 5 Facturas (Ventas y Compras)...');
            for ($i = 1; $i <= 5; $i++) {
                $isVenta = $i <= 3;
                $facturableType = $isVenta ? Cliente::class : Proveedor::class;
                $facturableId = $isVenta ? $clientes[$i-1]->id : $proveedores[$i-3]->id;
                
                $factura = Factura::create([
                    'numero_factura' => Factura::siguienteNumero($isVenta ? 'VT-' : 'CP-'),
                    'tipo_movimiento' => $isVenta ? 'venta' : 'compra',
                    'estado' => 'emitida', // Completamente pagada
                    'facturable_id' => $facturableId,
                    'facturable_type' => $facturableType,
                    'total_documento' => 0,
                    'total_pagado' => 0,
                    'observaciones' => 'Operación generada automáticamente.',
                    'fecha' => Carbon::now()->subDays(rand(1, 5))->toDateString(),
                    'user_id' => $admin->id,
                ]);

                $s = $stocks[$i-1];
                $precio = $isVenta ? $s->precio_venta : $s->precio_compra;
                $cantidad = rand(1, 3);
                
                FacturaItem::create([
                    'factura_id' => $factura->id,
                    'stock_id' => $s->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                ]);

                $totalDoc = $precio * $cantidad;
                $factura->update(['total_documento' => $totalDoc, 'total_pagado' => $totalDoc]);
            }

            // 8. MANTENIMIENTOS (5)
            $this->info('Creando 5 Mantenimientos...');
            for ($i = 1; $i <= 5; $i++) {
                Mantenimiento::create([
                    'id_orden' => $ordenService->siguiente('ORD-', Mantenimiento::class),
                    'equipo_id' => $equipos[$i-1]->id,
                    'tecnico_id' => $tecnicos[rand(0,4)]->id,
                    'user_id' => $admin->id,
                    'tipo' => $i % 2 == 0 ? 'preventivo' : 'correctivo',
                    'reparacion' => 'hardware',
                    'descripcion' => 'Limpieza general, cambio de pasta térmica y revisión de voltajes.',
                    'costo' => 120000 + ($i * 10000),
                    'estado' => $i <= 3 ? 'terminado' : 'pendiente',
                    'fecha_entrada' => Carbon::now()->subDays(rand(2, 8))->toDateString(),
                    'fecha_salida' => $i <= 3 ? Carbon::now()->subDays(1)->toDateString() : null,
                ]);
            }

            // 9. ELECTRONICA (5)
            $this->info('Creando 5 Órdenes de Electrónica...');
            for ($i = 1; $i <= 5; $i++) {
                Electronica::create([
                    'id_orden' => $ordenService->siguiente('ELC-', Electronica::class),
                    'equipo_id' => $equipos[rand(0,4)]->id,
                    'tecnico_id' => $tecnicos[rand(0,4)]->id,
                    'user_id' => $admin->id,
                    'descripcion_problema' => 'El equipo no enciende. Posible corto en la placa base (Mainboard).',
                    'tipo' => 'correctivo',
                    'estado' => $i <= 2 ? 'terminado' : 'pendiente',
                    'costo' => 250000 + ($i * 15000),
                    'fecha_entrada' => Carbon::now()->subDays(rand(1, 7))->toDateString(),
                    'fecha_salida' => $i <= 2 ? Carbon::now()->toDateString() : null,
                ]);
            }

            // 10. CAJA: Conceptos, 5 Ingresos, 5 Egresos
            $this->info('Creando Conceptos y 10 Movimientos de Caja (5 Ingresos / 5 Egresos)...');
            
            // Asegurar que existan conceptos
            $conceptoIngreso = ConceptoCaja::firstOrCreate(['nombre' => 'Ventas de mostrador']);
            $conceptoEgreso = ConceptoCaja::firstOrCreate(['nombre' => 'Pago de servicios públicos']);
            $conceptoNomina = ConceptoCaja::firstOrCreate(['nombre' => 'Pago a técnicos']);

            for ($i = 1; $i <= 5; $i++) {
                // Ingreso
                MovimientoCaja::create([
                    'tipo' => 'ingreso',
                    'concepto_id' => $conceptoIngreso->id,
                    'monto' => rand(50, 150) * 1000,
                    'descripcion' => "Venta rápida de accesorios $i",
                    'persona' => 'Cliente Ocasional',
                    'fecha' => Carbon::now()->subDays(rand(0, 3))->toDateString(),
                    'hora' => Carbon::now()->subHours(rand(1, 10))->toTimeString(),
                    'user_id' => $admin->id,
                ]);

                // Egreso
                MovimientoCaja::create([
                    'tipo' => 'egreso',
                    'concepto_id' => $i % 2 == 0 ? $conceptoEgreso->id : $conceptoNomina->id,
                    'monto' => rand(20, 80) * 1000,
                    'descripcion' => "Pago operativo $i",
                    'persona' => 'Proveedor / Empleado',
                    'fecha' => Carbon::now()->subDays(rand(0, 3))->toDateString(),
                    'hora' => Carbon::now()->subHours(rand(1, 10))->toTimeString(),
                    'user_id' => $admin->id,
                ]);
            }

            // 11. ARQUEOS (6)
            $this->info('Creando 6 Arqueos de Caja históricos...');
            for ($i = 6; $i >= 1; $i--) {
                $fecha = Carbon::now()->subDays($i)->toDateString();
                ArqueoCaja::create([
                    'fecha' => $fecha,
                    'saldo_inicial' => 100000,
                    'total_ingresos' => 450000,
                    'total_egresos' => 120000,
                    'saldo_final_calculado' => 430000,
                    'saldo_real_informado' => 430000,
                    'diferencia' => 0,
                    'observaciones' => 'Arqueo cuadrado correctamente.',
                    'user_id' => $admin->id,
                ]);
            }

            DB::commit();
            $this->info('¡Datos de demostración sembrados exitosamente! Todo listo para pruebas completas.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ocurrió un error al sembrar los datos: ' . $e->getMessage());
        }
    }
}
