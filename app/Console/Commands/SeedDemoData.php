<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\Proveedor;
use App\Models\Equipo;
use App\Models\CategoriaStock;
use App\Models\Stock;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\Electronica;
use App\Models\MovimientoCaja;
use App\Models\ConceptoCaja;
use App\Models\Abono;
use App\Models\Configuracion;
use App\Services\OrdenService;
use Carbon\Carbon;

class SeedDemoData extends Command
{
    protected $signature = 'app:seed-demo-data {--force : Sobrescribe o limpia datos existentes antes de sembrar}';
    protected $description = 'Puebla la base de datos con 5 registros realistas y matemáticamente exactos por cada módulo del sistema.';

    public function handle(OrdenService $ordenService): int
    {
        $this->line('');
        $this->info('╔════════════════════════════════════════════════════════════════════════════╗');
        $this->info('║       TECNI SYSTEMAS - GENERADOR DE DATOS DE PRUEBA REALISTAS (5x5)        ║');
        $this->info('╚════════════════════════════════════════════════════════════════════════════╝');
        $this->line('');

        if ($this->option('force') || (Cliente::count() === 0 && Tecnico::count() === 0)) {
            // Limpieza controlada si se pasa --force
            if ($this->option('force')) {
                $this->warn('Limpiando tablas transaccionales...');
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                $tables = [
                    'clientes', 'tecnicos', 'proveedores', 'stocks', 'mantenimientos',
                    'electronicas', 'mantenimiento_stock', 'electronica_stock', 'equipos',
                    'facturas', 'factura_items', 'cotizaciones', 'cotizacion_items',
                    'movimiento_cajas', 'abonos', 'cierre_cajas', 'eventos'
                ];
                foreach ($tables as $t) {
                    DB::table($t)->truncate();
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        } else {
            $this->error('⚠️ La base de datos ya contiene registros.');
            $this->line('Para reiniciar y sembrar desde cero ejecute: <fg=yellow>php artisan app:seed-demo-data --force</>');
            return 1;
        }

        DB::beginTransaction();

        try {
            // 0. CONFIGURACIÓN Y USUARIOS BASE
            $this->info('⚙️ 0. Verificando Configuración de Empresa y Usuarios...');
            Configuracion::firstOrCreate(
                ['id' => 1],
                [
                    'nombre'             => 'Tecni Systemas',
                    'nit'                => '4.501.927',
                    'telefono'           => '3172697442 - 3165528637',
                    'direccion'          => 'Cra 4 # 20-81 Pereira',
                    'correo'             => 'tecnisystemaspereira@hotmail.com',
                    'logo_path'          => 'configuracion/logo_nuevo_tecnisystemas.png',
                    'pie_pagina_factura' => 'Gracias por su confianza. Garantía de servicio técnico de 30 días.',
                ]
            );

            // Asegurar usuarios base
            $admin = User::firstOrCreate(
                ['email' => 'administrador@tecnisystemas.com'],
                ['name' => 'Administrador Principal', 'password' => Hash::make('Admin123*'), 'role' => 'admin', 'active' => true]
            );

            $tecnico1 = User::firstOrCreate(
                ['email' => 'tecnico@tecnisystemas.com'],
                ['name' => 'Juan David Técnico', 'password' => Hash::make('Tecni123*'), 'role' => 'tecnico', 'active' => true]
            );

            $invitado = User::firstOrCreate(
                ['email' => 'invitado@tecnisystemas.com'],
                ['name' => 'Usuario Consulta', 'password' => Hash::make('Invit123*'), 'role' => 'invitado', 'active' => true]
            );

            // 1. CLIENTES (5 registros)
            $this->info('👤 1. Creando 5 Clientes...');
            $clientesData = [
                ['nombres' => 'Carlos Alberto', 'apellidos' => 'Gómez Ruiz', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '1020304050', 'genero' => 'masculino', 'tipo_cliente' => 'cliente', 'movil' => '3001234567', 'email' => 'carlos.gomez@gmail.com', 'departamento' => 'Risaralda', 'municipio' => 'Pereira', 'direccion' => 'Calle 21 # 5-14', 'active' => true],
                ['nombres' => 'María Fernanda', 'apellidos' => 'López Toro', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '1098765432', 'genero' => 'femenino', 'tipo_cliente' => 'tecnico', 'movil' => '3109876543', 'email' => 'maria.lopez@hotmail.com', 'departamento' => 'Risaralda', 'municipio' => 'Dosquebradas', 'direccion' => 'Av. Simón Bolívar # 35-20', 'active' => true],
                ['nombres' => 'Soluciones Digitales', 'apellidos' => 'S.A.S', 'tipo_identificacion' => 'nit', 'identificacion' => '900123456-1', 'genero' => 'indefinido', 'tipo_cliente' => 'cliente', 'movil' => '3205554433', 'email' => 'contacto@solucionesit.com', 'departamento' => 'Cundinamarca', 'municipio' => 'Bogotá', 'direccion' => 'Carrera 7 # 72-41', 'active' => true],
                ['nombres' => 'Jorge Iván', 'apellidos' => 'Pérez Soto', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '71555666', 'genero' => 'masculino', 'tipo_cliente' => 'cliente', 'movil' => '3157778899', 'email' => 'jorge.perez@yahoo.es', 'departamento' => 'Antioquia', 'municipio' => 'Medellín', 'direccion' => 'Circular 4 # 73-10', 'active' => true],
                ['nombres' => 'Diana Marcela', 'apellidos' => 'Quintero', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '43222111', 'genero' => 'femenino', 'tipo_cliente' => 'tecnico', 'movil' => '3012223344', 'email' => 'diana.quintero@outlook.com', 'departamento' => 'Caldas', 'municipio' => 'Manizales', 'direccion' => 'Cra 23 # 45-12', 'active' => true],
            ];
            $clientes = [];
            foreach ($clientesData as $c) {
                $clientes[] = Cliente::create($c);
            }

            // 2. PROVEEDORES (5 registros)
            $this->info('🏢 2. Creando 5 Proveedores...');
            $proveedoresData = [
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '800999888-2', 'nombre_razon_social' => 'Distribuidora TecnoPartes Mayorista', 'telefono' => '3009998877', 'email' => 'ventas@tecnopartes.com', 'departamento' => 'Risaralda', 'municipio' => 'Pereira', 'direccion' => 'Zona Industrial La Popa', 'active' => true],
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '900888777-3', 'nombre_razon_social' => 'Mayorista de Electrónica y Circuitos S.A', 'telefono' => '3108887766', 'email' => 'gerencia@mayoristaelec.com', 'departamento' => 'Cundinamarca', 'municipio' => 'Bogotá', 'direccion' => 'Unilago Local 201', 'active' => true],
                ['tipo_entidad' => 'persona', 'tipo_identificacion' => 'cedula_ciudadania', 'identificacion' => '10203040', 'nombre_razon_social' => 'Importaciones Directas Juan David', 'telefono' => '3157776655', 'email' => 'juandavid.import@gmail.com', 'departamento' => 'Antioquia', 'municipio' => 'Medellín', 'direccion' => 'El Hueco Centro', 'active' => true],
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '901222333-4', 'nombre_razon_social' => 'Suministros Globales IT Colombia', 'telefono' => '3206665544', 'email' => 'pedidos@globalesit.com', 'departamento' => 'Valle del Cauca', 'municipio' => 'Cali', 'direccion' => 'Av. Sexta # 22N', 'active' => true],
                ['tipo_entidad' => 'empresa', 'tipo_identificacion' => 'nit', 'identificacion' => '800555444-1', 'nombre_razon_social' => 'Pantallas y Repuestos Express', 'telefono' => '3014443322', 'email' => 'info@partesypantallas.com', 'departamento' => 'Santander', 'municipio' => 'Bucaramanga', 'direccion' => 'Calle 36 # 15-28', 'active' => true],
            ];
            $proveedores = [];
            foreach ($proveedoresData as $p) {
                $proveedores[] = Proveedor::create($p);
            }

            // 3. TÉCNICOS (5 registros)
            $this->info('👨🏻‍🔧 3. Creando 5 Técnicos...');
            $tecnicosData = [
                ['nombre' => 'Andrés Felipe Martínez', 'identificacion' => '102030101', 'especialidad' => 'Hardware y Laptops', 'movil' => '3001112233', 'active' => true],
                ['nombre' => 'Roberto Sánchez', 'identificacion' => '102030102', 'especialidad' => 'Microelectrónica y Placas', 'movil' => '3102223344', 'active' => true],
                ['nombre' => 'Luis Fernando Osorio', 'identificacion' => '102030103', 'especialidad' => 'Mantenimiento y Redes', 'movil' => '3153334455', 'active' => true],
                ['nombre' => 'Miguel Ángel Rojas', 'identificacion' => '102030104', 'especialidad' => 'Software y Sistemas', 'movil' => '3204445566', 'active' => true],
                ['nombre' => 'Héctor Fabio Castaño', 'identificacion' => '102030105', 'especialidad' => 'Impresoras y Periféricos', 'movil' => '3015556677', 'active' => true],
            ];
            $tecnicos = [];
            foreach ($tecnicosData as $t) {
                $tecnicos[] = Tecnico::create($t);
            }

            // 4. EQUIPOS (5 registros)
            $this->info('💻 4. Creando 5 Equipos...');
            $equiposData = [
                ['nombre' => 'Portátil Corporativo', 'marca' => 'Dell', 'modelo' => 'Latitude 5420', 'serie' => 'DLL-5420-XYZ1', 'cliente_id' => $clientes[0]->id, 'observacion' => 'Equipo de trabajo con cargador original', 'user_id' => $admin->id],
                ['nombre' => 'PC Gamer Escritorio', 'marca' => 'Asus', 'modelo' => 'ROG Strix G15', 'serie' => 'ASUS-ROG-8899', 'cliente_id' => $clientes[1]->id, 'observacion' => 'Lentitud y recalentamiento', 'user_id' => $admin->id],
                ['nombre' => 'Servidor Torre', 'marca' => 'Lenovo', 'modelo' => 'ThinkSystem ST250', 'serie' => 'LNV-ST250-9988', 'cliente_id' => $clientes[2]->id, 'observacion' => 'Servidor de base de datos de la empresa', 'user_id' => $admin->id],
                ['nombre' => 'Impresora Multifuncional', 'marca' => 'Epson', 'modelo' => 'EcoTank L4150', 'serie' => 'EPS-L4150-5544', 'cliente_id' => $clientes[3]->id, 'observacion' => 'Atasco de papel y limpieza de cabezales', 'user_id' => $admin->id],
                ['nombre' => 'Consola PlayStation 5', 'marca' => 'Sony', 'modelo' => 'PS5 Digital Edition', 'serie' => 'PS5-CFI-1115B', 'cliente_id' => $clientes[4]->id, 'observacion' => 'No da video por puerto HDMI', 'user_id' => $admin->id],
            ];
            $equipos = [];
            foreach ($equiposData as $e) {
                $equipos[] = Equipo::create($e);
            }

            // 5. CATEGORÍAS Y STOCKS (5 registros)
            $this->info('📦 5. Creando Categorías y 5 Artículos de Stock...');
            CategoriaStock::firstOrCreate(['nombre' => 'Tecnologia', 'tipo' => 'categoria']);
            CategoriaStock::firstOrCreate(['nombre' => 'Repuestos', 'tipo' => 'subcategoria']);
            CategoriaStock::firstOrCreate(['nombre' => 'Accesorios', 'tipo' => 'categoria']);
            CategoriaStock::firstOrCreate(['nombre' => 'Servicios', 'tipo' => 'categoria']);

            $stocksData = [
                ['producto' => 'Disco Duro SSD Kingston 480GB', 'marca' => 'Kingston', 'modelo' => 'A400 SATA 3', 'codigo' => 'SSD-K480', 'cantidad' => 15, 'precio_compra' => 110000, 'precio_venta' => 165000, 'proveedor_id' => $proveedores[0]->id, 'categoria' => 'Tecnologia', 'subcategoria' => 'Repuestos', 'user_id' => $admin->id],
                ['producto' => 'Memoria RAM Crucial 8GB DDR4 3200MHz', 'marca' => 'Crucial', 'modelo' => 'CB8GU3200', 'codigo' => 'RAM-8GD4', 'cantidad' => 20, 'precio_compra' => 75000, 'precio_venta' => 125000, 'proveedor_id' => $proveedores[1]->id, 'categoria' => 'Tecnologia', 'subcategoria' => 'Repuestos', 'user_id' => $admin->id],
                ['producto' => 'Pantalla Portátil 15.6 LED FHD Slim', 'marca' => 'BOE', 'modelo' => 'NT156FHM-N61', 'codigo' => 'PAN-156S', 'cantidad' => 6, 'precio_compra' => 210000, 'precio_venta' => 330000, 'proveedor_id' => $proveedores[4]->id, 'categoria' => 'Tecnologia', 'subcategoria' => 'Repuestos', 'user_id' => $admin->id],
                ['producto' => 'Puerto HDMI Original Sony PS5', 'marca' => 'Sony', 'modelo' => 'OEM PS5', 'codigo' => 'HDM-PS5', 'cantidad' => 12, 'precio_compra' => 22000, 'precio_venta' => 55000, 'proveedor_id' => $proveedores[1]->id, 'categoria' => 'Tecnologia', 'subcategoria' => 'Repuestos', 'user_id' => $admin->id],
                ['producto' => 'Pasta Térmica Arctic MX-4 (4g)', 'marca' => 'Arctic', 'modelo' => 'MX-4', 'codigo' => 'PST-MX4', 'cantidad' => 25, 'precio_compra' => 25000, 'precio_venta' => 42000, 'proveedor_id' => $proveedores[3]->id, 'categoria' => 'Accesorios', 'subcategoria' => 'Repuestos', 'user_id' => $admin->id],
            ];
            $stocks = [];
            foreach ($stocksData as $s) {
                $stocks[] = Stock::create($s);
            }

            // 6. COTIZACIONES (5 registros)
            $this->info('📝 6. Creando 5 Cotizaciones con Ítems (COT-1 a COT-5)...');
            for ($i = 1; $i <= 5; $i++) {
                $cot = Cotizacion::create([
                    'codigo' => "COT-{$i}",
                    'cliente_id' => $clientes[$i - 1]->id,
                    'user_id' => $admin->id,
                    'fecha' => Carbon::now()->subDays(rand(1, 10))->toDateString(),
                    'validez_dias' => 15,
                    'total' => 0,
                    'estado' => $i === 1 ? 'aprobada' : ($i === 2 ? 'rechazada' : 'pendiente'),
                    'notas' => 'Cotización formal emitida para mantenimiento e insumos.',
                ]);

                $s1 = $stocks[$i - 1];
                $cant = 1;
                $p1 = $s1->precio_venta;
                CotizacionItem::create([
                    'cotizacion_id' => $cot->id,
                    'tipo' => 'stock',
                    'item_id' => $s1->id,
                    'descripcion' => $s1->producto,
                    'cantidad' => $cant,
                    'precio_unitario' => $p1
                ]);

                $manoObra = 60000;
                CotizacionItem::create([
                    'cotizacion_id' => $cot->id,
                    'tipo' => 'libre',
                    'item_id' => null,
                    'descripcion' => 'Servicio técnico especializado y pruebas de rendimiento',
                    'cantidad' => 1,
                    'precio_unitario' => $manoObra
                ]);

                $cot->update(['total' => ($p1 * $cant) + $manoObra]);
            }

            // 7. FACTURAS (5 registros: 3 Ventas VT-1 a VT-3, 2 Compras CP-1 a CP-2)
            $this->info('🧾 7. Creando 5 Facturas (3 Ventas VT-1 a VT-3 / 2 Compras CP-1 a CP-2)...');
            for ($i = 1; $i <= 5; $i++) {
                $isVenta = $i <= 3;
                $num = $isVenta ? "VT-{$i}" : "CP-" . ($i - 3);
                $facturableType = $isVenta ? Cliente::class : Proveedor::class;
                $facturableId = $isVenta ? $clientes[$i - 1]->id : $proveedores[$i - 4]->id;

                $factura = Factura::create([
                    'numero_factura' => $num,
                    'tipo_movimiento' => $isVenta ? 'venta' : 'compra',
                    'estado' => $i === 2 ? 'pendiente_pago' : 'emitida',
                    'facturable_id' => $facturableId,
                    'facturable_type' => $facturableType,
                    'total_documento' => 0,
                    'total_pagado' => 0,
                    'observaciones' => $isVenta ? "Venta mostrador {$num}" : "Compra de reposición {$num}",
                    'fecha' => Carbon::now()->subDays(rand(1, 5))->toDateString(),
                    'user_id' => $admin->id,
                ]);

                $s = $stocks[$i - 1];
                $precio = $isVenta ? $s->precio_venta : $s->precio_compra;
                $cant = rand(1, 2);

                FacturaItem::create([
                    'factura_id' => $factura->id,
                    'stock_id' => $s->id,
                    'descripcion' => $s->producto,
                    'cantidad' => $cant,
                    'precio_unitario' => $precio,
                ]);

                $totDoc = $precio * $cant;
                // La factura 2 queda con pago parcial para probar saldos pendientes
                $totPag = ($i === 2) ? round($totDoc / 2) : $totDoc;

                $factura->update([
                    'total_documento' => $totDoc,
                    'total_pagado' => $totPag,
                ]);
            }

            // 8. MANTENIMIENTOS (5 registros: ORD-1 a ORD-5)
            $this->info('🛠️ 8. Creando 5 Órdenes de Mantenimiento (ORD-1 a ORD-5)...');
            for ($i = 1; $i <= 5; $i++) {
                $mnt = Mantenimiento::create([
                    'id_orden' => "ORD-{$i}",
                    'equipo_id' => $equipos[$i - 1]->id,
                    'tecnico_id' => $tecnicos[($i - 1) % 5]->id,
                    'user_id' => $admin->id,
                    'tipo' => $i % 2 == 0 ? 'preventivo' : 'correctivo',
                    'reparacion' => 'hardware',
                    'descripcion' => 'Mantenimiento general, cambio de pasta térmica Arctic MX-4 y optimización.',
                    'costo' => 90000 + ($i * 15000),
                    'estado' => $i <= 3 ? 'terminado' : 'pendiente',
                    'fecha_entrada' => Carbon::now()->subDays(rand(2, 7))->toDateString(),
                    'fecha_salida' => $i <= 3 ? Carbon::now()->subDays(1)->toDateString() : null,
                ]);

                // Asociar repuesto utilizado en mantenimientos terminados
                if ($i <= 3) {
                    $rep = $stocks[$i - 1];
                    $mnt->stocks()->attach($rep->id, [
                        'cantidad' => 1,
                        'precio_unitario' => $rep->precio_venta,
                    ]);
                    // Actualizar costo total sumando repuesto
                    $mnt->update(['costo' => $mnt->costo + $rep->precio_venta]);
                }
            }

            // 9. REPARACIONES ELECTRÓNICAS (5 registros: ELC-1 a ELC-5)
            $this->info('⚡ 9. Creando 5 Órdenes de Electrónica (ELC-1 a ELC-5)...');
            for ($i = 1; $i <= 5; $i++) {
                $elc = Electronica::create([
                    'id_orden' => "ELC-{$i}",
                    'equipo_id' => $equipos[$i - 1]->id,
                    'tecnico_id' => $tecnicos[1]->id, // Roberto Sánchez (Microelectrónica)
                    'user_id' => $admin->id,
                    'descripcion_problema' => 'Falla en circuito de alimentación / microcomponentes en corto.',
                    'tipo' => 'correctivo',
                    'reparacion' => 'hardware',
                    'estado' => $i <= 2 ? 'terminado' : 'pendiente',
                    'costo' => 180000 + ($i * 20000),
                    'fecha_entrada' => Carbon::now()->subDays(rand(1, 6))->toDateString(),
                    'fecha_salida' => $i <= 2 ? Carbon::now()->toDateString() : null,
                ]);

                // Asignar repuesto electrónico
                if ($i === 5) {
                    $hdmiStock = $stocks[3]; // Puerto HDMI PS5
                    $elc->stocks()->attach($hdmiStock->id, [
                        'cantidad' => 1,
                        'precio_unitario' => $hdmiStock->precio_venta,
                    ]);
                    $elc->update(['costo' => $elc->costo + $hdmiStock->precio_venta]);
                }
            }

            // 10. CAJA CHICA: Conceptos, 5 Ingresos y 5 Egresos
            $this->info('💰 10. Creando Conceptos y 10 Movimientos de Caja (5 Ingresos / 5 Egresos)...');
            $c1 = ConceptoCaja::firstOrCreate(['nombre' => 'Cobro de mantenimiento preventivo']);
            $c2 = ConceptoCaja::firstOrCreate(['nombre' => 'Venta de repuestos y accesorios']);
            $c3 = ConceptoCaja::firstOrCreate(['nombre' => 'Servicio de reparación electrónica']);
            $c4 = ConceptoCaja::firstOrCreate(['nombre' => 'Pago a proveedores de repuestos']);
            $c5 = ConceptoCaja::firstOrCreate(['nombre' => 'Pago de servicios públicos y arriendo']);

            // 5 Ingresos
            for ($i = 1; $i <= 5; $i++) {
                $conceptosIngreso = [$c1, $c2, $c3];
                MovimientoCaja::create([
                    'tipo_movimiento' => 'ingreso',
                    'tipo_pago' => $i % 2 === 0 ? 'consignacion' : 'efectivo',
                    'concepto_id' => $conceptosIngreso[($i - 1) % 3]->id,
                    'monto' => 85000 + ($i * 25000),
                    'monto_total' => 85000 + ($i * 25000),
                    'persona' => $clientes[$i - 1]->nombre,
                    'descripcion' => "Ingreso por servicio técnico ORD-{$i}",
                    'fecha' => Carbon::now()->subDays(rand(0, 3))->toDateString(),
                    'user_id' => $admin->id,
                    'anulado' => false,
                ]);
            }

            // 5 Egresos
            for ($i = 1; $i <= 5; $i++) {
                $conceptosEgreso = [$c4, $c5];
                MovimientoCaja::create([
                    'tipo_movimiento' => 'egreso',
                    'tipo_pago' => 'efectivo',
                    'concepto_id' => $conceptosEgreso[($i - 1) % 2]->id,
                    'monto' => 45000 + ($i * 15000),
                    'monto_total' => 45000 + ($i * 15000),
                    'persona' => $proveedores[$i - 1]->nombre_razon_social,
                    'descripcion' => "Gasto operativo / pago de insumos #{$i}",
                    'fecha' => Carbon::now()->subDays(rand(0, 3))->toDateString(),
                    'user_id' => $admin->id,
                    'anulado' => false,
                ]);
            }

            DB::commit();

            $this->line('');
            $this->info('╔════════════════════════════════════════════════════════════════════════════╗');
            $this->info('║  ✅ DATOS REALISTAS SEMBRADOS CON ÉXITO: 5 REGISTROS POR CADA MÓDULO        ║');
            $this->info('║  • Clientes: 5    • Proveedores: 5    • Técnicos: 5    • Equipos: 5        ║');
            $this->info('║  • Stocks: 5      • Cotizaciones: 5   • Facturas: 5    • Mantenimientos: 5 ║');
            $this->info('║  • Electrónica: 5 • Movimientos Caja: 10 (5 Ingresos / 5 Egresos)          ║');
            $this->info('╚════════════════════════════════════════════════════════════════════════════╝');
            $this->line('');

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Ocurrió un error al sembrar los datos: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
