<?php

namespace Database\Seeders;

use App\Models\Abono;
use App\Models\BajaStock;
use App\Models\CierreCaja;
use App\Models\Cliente;
use App\Models\ConceptoCaja;
use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Electronica;
use App\Models\Equipo;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Mantenimiento;
use App\Models\MovimientoCaja;
use App\Models\Proveedor;
use App\Models\Stock;
use App\Models\Tecnico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Desactivar temporalmente eventos de Eloquent para inserción de alto rendimiento
        // y evitar sobrecargar la tabla de logs de auditoría durante la siembra masiva
        $users = User::all();
        if ($users->isEmpty()) {
            $this->call(DatabaseSeeder::class);
            $users = User::all();
        }
        $adminUser = $users->firstWhere('role', 'admin') ?? $users->first();
        $tecnicoUser = $users->firstWhere('role', 'tecnico') ?? $adminUser;
        $userIds = $users->pluck('id')->toArray();

        // Limpieza de tablas previas para siembra limpia e idempotente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MovimientoCaja::truncate();
        Abono::truncate();
        CierreCaja::truncate();
        FacturaItem::truncate();
        Factura::truncate();
        CotizacionItem::truncate();
        Cotizacion::truncate();
        DB::table('mantenimiento_stock')->truncate();
        DB::table('electronica_stock')->truncate();
        Mantenimiento::truncate();
        Electronica::truncate();
        BajaStock::truncate();
        Stock::truncate();
        Equipo::truncate();
        Tecnico::truncate();
        Cliente::truncate();
        Proveedor::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Asegurar conceptos de caja
        $conceptos = [
            'Pago de mantenimiento' => ConceptoCaja::firstOrCreate(['nombre' => 'Pago de mantenimiento'])->id,
            'Pago de servicios' => ConceptoCaja::firstOrCreate(['nombre' => 'Pago de servicios'])->id,
            'Servicio técnico' => ConceptoCaja::firstOrCreate(['nombre' => 'Servicio técnico'])->id,
            'Apertura de caja' => ConceptoCaja::firstOrCreate(['nombre' => 'Apertura de caja'])->id,
            'Venta de repuesto' => ConceptoCaja::firstOrCreate(['nombre' => 'Venta de repuesto'])->id,
            'Compra de insumos' => ConceptoCaja::firstOrCreate(['nombre' => 'Compra de insumos'])->id,
            'Arriendo' => ConceptoCaja::firstOrCreate(['nombre' => 'Arriendo'])->id,
            'Nómina / Pago técnico' => ConceptoCaja::firstOrCreate(['nombre' => 'Nómina / Pago técnico'])->id,
            'Gastos varios' => ConceptoCaja::firstOrCreate(['nombre' => 'Gastos varios'])->id,
            'Otros ingresos' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros ingresos'])->id,
            'Otros egresos' => ConceptoCaja::firstOrCreate(['nombre' => 'Otros egresos'])->id,
        ];

        // ════════════════════════════════════════════════════════════════
        // 1. BANCO DE DATOS REALISTAS COLOMBIANOS
        // ════════════════════════════════════════════════════════════════
        $nombresHombres = [
            'Carlos Andrés', 'Juan David', 'Diego Alejandro', 'Mateo', 'Santiago',
            'Sebastián', 'Andrés Felipe', 'Luis Fernando', 'Jorge Eliécer', 'Gabriel',
            'Mauricio', 'Julián', 'Felipe', 'Gustavo Adolfo', 'Camilo', 'Álvaro',
            'Nicolás', 'Daniel', 'Esteban', 'Javier Darío', 'Rodrigo', 'César Augusto',
            'Manuel', 'Héctor Fabio', 'Oscar Iván', 'Hernán', 'Rubén Darío', 'Cristian',
            'Guillermo', 'Fabio Nelson', 'Edgar', 'Leonardo', 'Víctor Manuel', 'Jaime',
        ];

        $nombresMujeres = [
            'Diana Marcela', 'Carolina', 'Daniela', 'Valentina', 'Sofía', 'Camila',
            'María José', 'Laura Tatiana', 'Andrea', 'Paula Andrea', 'Natalia',
            'Claudia Patricia', 'Sandra Milena', 'Luz Marina', 'Gloria Inés', 'Mónica',
            'Adriana', 'Esperanza', 'Angélica', 'Liliana', 'Paola Andrea', 'Bibiana',
            'Maritza', 'Ximena', 'Juliana', 'Marcela', 'Estefanía', 'Vanessa',
            'Ingrid Johana', 'Yuliana', 'Lorena', 'Silvia Juliana', 'Angela María',
        ];

        $apellidos = [
            'Gómez', 'Rodríguez', 'López', 'García', 'Martínez', 'Pérez', 'González',
            'Hernández', 'Sánchez', 'Ramírez', 'Torres', 'Díaz', 'Vargas', 'Castro',
            'Morales', 'Ríos', 'Osorio', 'Restrepo', 'Jaramillo', 'Zuluaga', 'Montoya',
            'Castrillón', 'Gaviria', 'Echeverri', 'Duque', 'Castaño', 'Henao', 'Bedoya',
            'Salazar', 'Valencia', 'Gutiérrez', 'Mendoza', 'Cardona', 'Mejía', 'Botero',
            'Ceballos', 'Quintero', 'Arango', 'Villegas', 'Blandón', 'Saldarriaga',
        ];

        $ubicaciones = [
            ['Risaralda', 'Pereira'],
            ['Risaralda', 'Dosquebradas'],
            ['Risaralda', 'Santa Rosa de Cabal'],
            ['Risaralda', 'La Virginia'],
            ['Caldas', 'Manizales'],
            ['Caldas', 'Chinchiná'],
            ['Caldas', 'Villamaría'],
            ['Quindío', 'Armenia'],
            ['Quindío', 'Calarcá'],
            ['Antioquia', 'Medellín'],
            ['Antioquia', 'Envigado'],
            ['Valle del Cauca', 'Cali'],
            ['Valle del Cauca', 'Cartago'],
        ];

        $generarDireccion = function () {
            $vias = ['Carrera', 'Calle', 'Avenida', 'Diagonal', 'Transversal'];
            $via = $vias[array_rand($vias)];
            $n1 = rand(3, 98);
            $n2 = rand(1, 85);
            $n3 = rand(2, 95);
            $extra = rand(0, 1) ? ' Apto '.rand(101, 804) : (rand(0, 1) ? ' Oficina '.rand(201, 508) : ' Local '.rand(1, 15));

            return "{$via} {$n1} # {$n2}-{$n3}{$extra}";
        };

        $generarTelefono = function ($tipo = 'movil') {
            if ($tipo === 'fijo') {
                return '(606) 3'.rand(10, 99).'-'.rand(1000, 9999);
            }
            $prefijos = ['310', '311', '312', '313', '314', '315', '316', '317', '318', '320', '321', '322', '300', '301'];

            return $prefijos[array_rand($prefijos)].rand(1000000, 9999999);
        };

        // ════════════════════════════════════════════════════════════════
        // 2. CREAR 100 PROVEEDORES (TODOS LOS CAMPOS LLENOS)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Proveedores con TODOS los campos llenos (telefono2, contacto_nombre, etc.)...');

        $empresasTech = [
            'Mayorista Tecnológico del Café S.A.S.', 'Electrónica y Microchips de Colombia S.A.',
            'Distribuidora Nacional de Partes y Repuestos S.A.S.', 'CompuPartes del Eje Ltda.',
            'Sistemas y Componentes Andinos S.A.S.', 'Importadora Digital de Risaralda S.A.S.',
            'TecnoInsumos del Otún S.A.S.', 'Chips & Boards de Colombia S.A.S.',
            'MegaBoards Pereira S.A.S.', 'Soluciones y Módulos de Potencia S.A.S.',
            'MicroCircuitos Industriales del Café S.A.S.', 'Pantallas y Displays de Colombia Ltda.',
            'Discos y Memorias Mayoristas S.A.S.', 'Insumos de Soldadura y Taller S.A.',
            'Consumibles y Cintas del Eje S.A.S.', 'Central de Repuestos para Portátiles S.A.S.',
            'Distribuciones Global Tech Colombia', 'Electrónica y Semiconductores del Valle',
            'Almacén Mayorista de Fuentes y Baterías', 'Cables y Accesorios Ópticos de Occidente',
            'Servicios y Tarjetas Madres del Café S.A.S.', 'Comercializadora Hardware Colombia S.A.',
            'Tecnología Integrada de los Andes S.A.S.', 'Redes y Servidores de Risaralda S.A.S.',
            'Periféricos y Gamers Mayoristas del Eje',
        ];

        $proveedores = [];
        for ($i = 1; $i <= 100; $i++) {
            $esEmpresa = ($i % 3 !== 0); // 67% empresa, 33% persona natural
            $ub = $ubicaciones[array_rand($ubicaciones)];
            $depto = $ub[0];
            $mpio = $ub[1];

            $nombreContacto = ($i % 2 === 0 ? $nombresHombres[array_rand($nombresHombres)] : $nombresMujeres[array_rand($nombresMujeres)])
                .' '.$apellidos[array_rand($apellidos)].' '.$apellidos[array_rand($apellidos)];

            if ($esEmpresa) {
                $nombreRazonSocial = $empresasTech[($i - 1) % count($empresasTech)].($i > count($empresasTech) ? " Sucursal {$i}" : '');
                $tipoEntidad = 'empresa';
                $tipoId = 'nit';
                $nitBase = 900000000 + ($i * 12345) % 99999999;
                $dv = rand(0, 9);
                $identificacion = number_format($nitBase, 0, '', '.')."-{$dv}";
                $telefonoPrincipal = $generarTelefono('fijo');
                $email = 'ventas.sucursal'.$i.'@'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($nombreRazonSocial, 0, 15))).'.com.co';
            } else {
                $nombreRazonSocial = ($i % 2 === 0 ? $nombresHombres[array_rand($nombresHombres)] : $nombresMujeres[array_rand($nombresMujeres)])
                    .' '.$apellidos[array_rand($apellidos)].' '.$apellidos[array_rand($apellidos)];
                $tipoEntidad = 'persona';
                $tipoId = ($i % 5 === 0) ? 'rut' : 'cedula_ciudadania';
                $identificacion = (string) (1088000000 + $i * 371);
                $telefonoPrincipal = $generarTelefono('movil');
                $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($nombreRazonSocial, 0, 12))).$i.'@gmail.com';
            }

            // REQUERIMIENTO EXPLÍCITO DEL USUARIO: telefono2 (Número alterno) y contacto_nombre (Contacto Principal) DEBEN ESTAR SIEMPRE LLENOS
            $telefonoAlterno = $generarTelefono('movil');

            $notas = 'Proveedor homologado categoría '.chr(65 + ($i % 4)).'. Tiempo de entrega promedio '.rand(24, 72).'h. Condiciones de crédito: '.(rand(0, 1) ? 'Contado comercial' : '30 días netos').". Contacto técnico asignado: {$nombreContacto}.";

            // 85% activos, 15% inactivos/anulados para probar filtros
            $activo = ($i <= 85);

            $proveedores[] = Proveedor::create([
                'tipo_entidad' => $tipoEntidad,
                'tipo_identificacion' => $tipoId,
                'identificacion' => $identificacion,
                'nombre_razon_social' => $nombreRazonSocial,
                'telefono' => $telefonoPrincipal,
                'telefono2' => $telefonoAlterno,           // << LLENO GARANTIZADO
                'contacto_nombre' => $nombreContacto,       // << LLENO GARANTIZADO
                'email' => $email,
                'direccion' => $generarDireccion(),
                'departamento' => $depto,
                'municipio' => $mpio,
                'notas' => $notas,
                'active' => $activo,
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 3. CREAR 100 CLIENTES (CAMPOS COMPLETOS)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Clientes con datos demográficos y fiscales...');

        $clientes = [];
        for ($i = 1; $i <= 100; $i++) {
            $esHombre = ($i % 2 === 0);
            $nombre = $esHombre ? $nombresHombres[array_rand($nombresHombres)] : $nombresMujeres[array_rand($nombresMujeres)];
            $ap1 = $apellidos[array_rand($apellidos)];
            $ap2 = $apellidos[array_rand($apellidos)];
            $genero = $esHombre ? 'masculino' : 'femenino';
            $ub = $ubicaciones[array_rand($ubicaciones)];

            // 75% clientes residenciales/finales, 25% técnicos externos/talleres aliados
            $tipoCliente = ($i % 4 === 0) ? 'tecnico' : 'cliente';

            $tipoId = ($i % 15 === 0) ? 'nit' : (($i % 25 === 0) ? 'cedula_extranjeria' : 'cedula_ciudadania');
            $identificacion = ($tipoId === 'nit')
                ? (901000000 + $i * 543).'-'.rand(0, 9)
                : (string) (1053000000 + $i * 482);

            $movil = $generarTelefono('movil');
            $email = strtolower(str_replace(' ', '.', $nombre)).'.'.strtolower($ap1).$i.'@'.($i % 3 === 0 ? 'hotmail.com' : 'gmail.com');

            // 88% activos, 12% inactivos
            $activo = ($i <= 88);

            $clientes[] = Cliente::create([
                'nombres' => $nombre,
                'apellidos' => "{$ap1} {$ap2}",
                'tipo_identificacion' => $tipoId,
                'identificacion' => $identificacion,
                'genero' => $genero,
                'tipo_cliente' => $tipoCliente,
                'movil' => $movil,
                'email' => $email,
                'direccion' => $generarDireccion(),
                'departamento' => $ub[0],
                'municipio' => $ub[1],
                'active' => $activo,
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 4. CREAR 100 TÉCNICOS
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Técnicos con especialidades de laboratorio y campo...');

        $especialidades = [
            'Microelectrónica y Reparación de Placas Madre',
            'Diagnóstico y Mantenimiento Correctivo de Laptops',
            'Mantenimiento Preventivo y Limpieza por Ultrasonido',
            'Sistemas Operativos, Recuperación de Datos y Backups',
            'Redes Corporativas, Servidores y Cableado Estructurado',
            'Reparación de Monitores, Displays LED y Fuentes Conmutadas',
            'Impresoras Multifuncionales, Inyección Térmica y Láser',
            'Consolas de Videojuegos y Hardware de Alto Rendimiento Gamer',
            'Electrónica de Potencia, UPS e Inversores Solares',
        ];

        $tecnicos = [];
        for ($i = 1; $i <= 100; $i++) {
            $esHombre = ($i % 4 !== 0);
            $nombre = ($esHombre ? $nombresHombres[array_rand($nombresHombres)] : $nombresMujeres[array_rand($nombresMujeres)])
                .' '.$apellidos[array_rand($apellidos)].' '.$apellidos[array_rand($apellidos)];
            $identificacion = (string) (1088300000 + $i * 219);
            $movil = $generarTelefono('movil');
            $email = 'tecnico.'.strtolower(preg_replace('/[^a-zA-Z0-9]/', '', substr($nombre, 0, 10))).$i.'@tecnisystemas.com';
            $activo = ($i <= 90);

            $tecnicos[] = Tecnico::create([
                'nombre' => $nombre,
                'identificacion' => $identificacion,
                'especialidad' => $especialidades[array_rand($especialidades)],
                'movil' => $movil,
                'email' => $email,
                'direccion' => $generarDireccion(),
                'active' => $activo,
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 5. CREAR 100 EQUIPOS (CLIENTES Y PROVEEDORES, OPERATIVOS Y BAJAS)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Equipos (operativos, en reparación y dados de baja)...');

        $equiposCatalogo = [
            ['Laptop ASUS TUF Gaming F15', 'ASUS', 'FX506LH', 'Portátil Gamer Core i5 10ma Gen 16GB RAM GTX 1650'],
            ['Portátil Dell Latitude 5420', 'Dell', 'Latitude 5420', 'Equipo corporativo Intel Core i7 11ma Gen 16GB NVMe 512GB'],
            ['PC Todo en Uno HP 200 G4 22', 'HP', '200 G4 AiO', 'All in One corporativo Core i3 10ma Gen 8GB RAM 1TB HDD'],
            ['MacBook Air M2 13.6"', 'Apple', 'A2681 (M2)', 'Portátil ultrafino Chip Apple M2 8-core CPU 256GB SSD'],
            ['PC Torre Gamer Ryzen 7', 'Custom PC', 'Ryzen 7 5700X', 'Gabinete ATX Cooler Master, Fuente 750W Gold, RTX 3060 12GB'],
            ['Portátil Lenovo ThinkPad E14', 'Lenovo', 'ThinkPad E14 Gen 4', 'Equipo empresarial Ryzen 5 5625U 16GB RAM SSD 512GB'],
            ['Impresora Multifuncional Epson EcoTank L3250', 'Epson', 'EcoTank L3250', 'Sistema continuo inalámbrico, escáner plano USB y Wi-Fi'],
            ['Consola Sony PlayStation 5 Slim', 'Sony', 'CFI-2000 Slim', 'Consola 1TB SSD con lector Ultra HD Blu-ray'],
            ['Servidor Dell PowerEdge T140', 'Dell', 'PowerEdge T140', 'Servidor de torre Intel Xeon E-2224 32GB RAM 2x2TB SAS RAID 1'],
            ['Portátil Acer Nitro 5', 'Acer', 'AN515-57', 'Laptop Gamer Intel Core i7 11800H RTX 3050 Ti 144Hz FHD'],
            ['Impresora Láser HP LaserJet Pro M404dn', 'HP', 'LaserJet M404dn', 'Impresora monocromática de alta velocidad dúplex red'],
            ['Consola Microsoft Xbox Series X', 'Microsoft', 'Series X 1TB', 'Consola 4K 120Hz 1TB SSD Velocity Architecture'],
            ['Monitor Gamer LG UltraGear 27"', 'LG', '27GN650-B', 'Monitor IPS 144Hz 1ms HDR10 FreeSync Premium'],
            ['Tablet Gráfica Wacom Cintiq 16', 'Wacom', 'DTK1660', 'Pantalla interactiva con lápiz Pro Pen 2 8192 niveles'],
        ];

        $motivosBaja = ['irreparable', 'desguace_repuestos', 'chatarrizacion', 'siniestro', 'abandonado'];
        $obsBajas = [
            'Placa principal chamuscada con perforación en multicapa, microcontrolador irrecuperable.',
            'Autorizado por el cliente para desarme y reutilización de componentes útiles en taller.',
            'Daño físico estructural irreversible por caída libre desde segundo nivel con fractura de chasis.',
            'Descarga eléctrica severa que quemó puente norte, CPU y pistas de alimentación.',
            'Equipo abandonado en taller por más de 180 días tras notificación reiterada según ley 1480.',
        ];

        $equipos = [];
        for ($i = 1; $i <= 100; $i++) {
            $cat = $equiposCatalogo[($i - 1) % count($equiposCatalogo)];
            $esCliente = ($i % 4 !== 0); // 75% equipos de clientes, 25% de proveedores (garantía / consignación)
            $clienteId = $esCliente ? $clientes[array_rand($clientes)]->id : null;
            $proveedorId = ! $esCliente ? $proveedores[array_rand($proveedores)]->id : null;

            // 50 operativos, 30 en reparación, 20 dados de baja
            $estado = ($i <= 50) ? 'operativo' : (($i <= 80) ? 'en_reparacion' : 'dado_de_baja');
            $estaDadoDeBaja = ($estado === 'dado_de_baja');

            $motivoBaja = $estaDadoDeBaja ? $motivosBaja[array_rand($motivosBaja)] : null;
            $obsBaja = $estaDadoDeBaja ? $obsBajas[array_rand($obsBajas)] : null;
            $fechaBaja = $estaDadoDeBaja ? Carbon::now()->subDays(rand(5, 60)) : null;
            $bajaUserId = $estaDadoDeBaja ? $userIds[array_rand($userIds)] : null;
            $active = ! $estaDadoDeBaja;

            $serie = strtoupper(substr($cat[1], 0, 3)).'-'.(2025 + ($i % 2)).'-'.str_pad($i, 5, '0', STR_PAD_LEFT);

            $equipos[] = Equipo::create([
                'nombre' => $cat[0],
                'marca' => $cat[1],
                'modelo' => $cat[2],
                'serie' => $serie,
                'observacion' => $cat[3].'. Registrado en recepción para trazabilidad técnica.',
                'user_id' => $userIds[array_rand($userIds)],
                'cliente_id' => $clienteId,
                'proveedor_id' => $proveedorId,
                'active' => $active,
                'estado' => $estado,
                'motivo_baja' => $motivoBaja,
                'observacion_baja' => $obsBaja,
                'fecha_baja' => $fechaBaja,
                'baja_user_id' => $bajaUserId,
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 6. CREAR 100 STOCKS DE INVENTARIO
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Artículos de Stock (categorías, costos, precios venta y técnico)...');

        $stockCatalogo = [
            ['Memoria RAM Kingston Fury Beast DDR4 16GB 3200MHz', 'Tecnologia', 'Memorias RAM', 120000, 35],
            ['Memoria RAM Corsair Vengeance LPX DDR4 8GB 3200MHz', 'Tecnologia', 'Memorias RAM', 65000, 38],
            ['Memoria RAM Crucial DDR5 16GB 4800MHz para Portátil', 'Tecnologia', 'Memorias RAM', 190000, 30],
            ['Disco Sólido SSD Kingston NV2 NVMe M.2 1TB PCIe 4.0', 'Tecnologia', 'Discos Sólidos', 230000, 32],
            ['Disco Sólido SSD Crucial BX500 480GB SATA 2.5"', 'Tecnologia', 'Discos Sólidos', 115000, 35],
            ['Disco Sólido SSD Western Digital Blue SN580 500GB', 'Tecnologia', 'Discos Sólidos', 145000, 30],
            ['Pasta Térmica Arctic MX-4 4g con Micropartículas de Carbono', 'Repuestos', 'Insumos de Taller', 28000, 50],
            ['Pasta Térmica Thermal Grizzly Kryonaut 1g Alto Rendimiento', 'Repuestos', 'Insumos de Taller', 42000, 45],
            ['Cargador Universal para Portátil Tipo C 65W GaN', 'Accesorios', 'Cargadores', 75000, 40],
            ['Cargador para Portátil HP 19.5V 3.33A Punta Azul 4.5x3.0mm', 'Accesorios', 'Cargadores', 45000, 45],
            ['Cargador para Portátil Dell 19.5V 3.34A Punta Delgada 4.5mm', 'Accesorios', 'Cargadores', 48000, 42],
            ['Pantalla LED 15.6" Slim 30 Pines FHD IPS sin brackets', 'Repuestos', 'Pantallas', 240000, 35],
            ['Pantalla LED 14.0" Slim 30 Pines HD Conector Inferior Derecho', 'Repuestos', 'Pantallas', 210000, 35],
            ['Teclado en Español para Lenovo ThinkPad E14 Retroiluminado', 'Repuestos', 'Teclados', 68000, 45],
            ['Teclado en Español para ASUS VivoBook 15 X512 Con Marco', 'Repuestos', 'Teclados', 58000, 45],
            ['Batería Interna Dell Latitude 5400 / 5410 4 Celdas 68Wh', 'Repuestos', 'Baterías', 135000, 35],
            ['Batería Interna HP HT03XL para Pavilion 15-CS 41.9Wh', 'Repuestos', 'Baterías', 115000, 35],
            ['Ventilador Cooler Fan CPU para ASUS TUF FX505 / FX506', 'Repuestos', 'Ventiladores', 35000, 50],
            ['Cable de Video HDMI 2.1 Ultra High Speed 4K 120Hz 2m', 'Accesorios', 'Cables y Adaptadores', 22000, 55],
            ['Adaptador USB Tipo C a Ethernet Gigabit RJ45 + 3x USB 3.0', 'Accesorios', 'Cables y Adaptadores', 45000, 45],
            ['Fuente de Poder Corsair CV650 650W 80 Plus Bronze ATX', 'Tecnologia', 'Fuentes de Poder', 245000, 25],
            ['Fuente de Poder EVGA 500 W1 500W Certificada Blanca', 'Tecnologia', 'Fuentes de Poder', 165000, 28],
            ['Tarjeta de Video Zotac Gaming GeForce RTX 3060 12GB GDDR6', 'Tecnologia', 'Tarjetas Gráficas', 1150000, 20],
            ['Cabezal de Impresión Original Epson L3110 / L3150 / L3250', 'Repuestos', 'Partes de Impresora', 185000, 30],
            ['Almohadillas de Tinta Residual con Chip Reset Epson L3210', 'Repuestos', 'Partes de Impresora', 28000, 60],
            ['Tinta Original Epson 544 Negra para EcoTank 65ml', 'Accesorios', 'Consumibles', 32000, 35],
            ['Tinta Original Epson 544 Pack Colores (Cian, Magenta, Amarillo)', 'Accesorios', 'Consumibles', 89000, 35],
            ['Kit de Soldadura en Pasta Sn63Pb37 con Flux para BGA 35g', 'Repuestos', 'Insumos de Taller', 38000, 50],
            ['Malla Desoldadora de Cobre Goot Wick 2.5mm x 1.5m', 'Repuestos', 'Insumos de Taller', 14000, 60],
            ['Alcohol Isopropílico de Alta Pureza 99.8% Frasco 1 Litro', 'Repuestos', 'Insumos de Taller', 18000, 55],
        ];

        $stocks = [];
        for ($i = 1; $i <= 100; $i++) {
            $item = $stockCatalogo[($i - 1) % count($stockCatalogo)];
            $proveedor = $proveedores[array_rand($proveedores)];

            $codigo = 'STK-'.(1000 + $i);
            $nombreProd = $item[0].($i > count($stockCatalogo) ? ' Rev.'.ceil($i / count($stockCatalogo)) : '');
            $precioCompra = $item[3] + rand(-5, 10) * 1000;
            $utilidad = $item[4];
            $precioVenta = round($precioCompra * (1 + $utilidad / 100), -2);
            $precioTecnico = round($precioCompra * (1 + ($utilidad / 2) / 100), -2);

            // Variabilidad de cantidades: algunos en 0 (alerta sin stock), otros bajos (1-4), otros normales (10-80)
            if ($i % 20 === 0) {
                $cantidad = 0; // Agotado
            } elseif ($i % 10 === 0) {
                $cantidad = rand(1, 4); // Stock crítico
            } else {
                $cantidad = rand(8, 75); // Stock normal
            }

            $activo = ($i <= 92); // 92 activos, 8 inactivos

            $stocks[] = Stock::create([
                'codigo' => $codigo,
                'producto' => $nombreProd,
                'categoria' => $item[1],
                'subcategoria' => $item[2],
                'cantidad' => $cantidad,
                'proveedor_id' => $proveedor->id,
                'precio_compra' => $precioCompra,
                'utilidad' => $utilidad,
                'precio_venta' => $precioVenta,
                'precio_tecnico' => $precioTecnico,
                'active' => $activo,
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 7. CREAR 100 REGISTROS DE BAJAS DE STOCK (MERMAS Y DEFECTOS)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Bajas de Stock con motivos técnicos y costos de pérdida...');

        $motivosBajasStock = ['defectuoso_fabrica', 'dano_taller', 'obsoleto', 'perdida_merma'];
        $obsBajasStock = [
            'Llegó con pines de contacto doblados y no pasa prueba de continuidad.',
            'Daño accidental con punta de cautín durante proceso de soldadura en placa.',
            'Componente descontinuado incompatible con nuevas placas de circuito.',
            'Diferencia detectada en conteo físico trimestral de inventario.',
            'Cargador presentó chispa interna y cortocircuito en primera conexión de prueba.',
            'Pantalla con línea vertical blanca de fábrica; proveedor no aceptó trámite por flete vencido.',
            'Batería hinchada con pérdida de celda química detectada en almacenamiento.',
        ];

        for ($i = 1; $i <= 100; $i++) {
            $stock = $stocks[array_rand($stocks)];
            $cantBaja = rand(1, 4);
            $costoUnitario = (float) $stock->precio_compra;
            $perdidaTotal = $cantBaja * $costoUnitario;
            $motivo = $motivosBajasStock[array_rand($motivosBajasStock)];

            BajaStock::create([
                'stock_id' => $stock->id,
                'user_id' => $userIds[array_rand($userIds)],
                'cantidad' => $cantBaja,
                'precio_compra_unitario' => $costoUnitario,
                'costo_total_perdida' => $perdidaTotal,
                'motivo' => $motivo,
                'observacion' => $obsBajasStock[array_rand($obsBajasStock)]." (Auditoría #{$i})",
                'created_at' => Carbon::now()->subDays(rand(1, 90))->subHours(rand(1, 12)),
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 8. CREAR 100 MANTENIMIENTOS (ANULACIONES, PAGADOS, PARCIALES, $0)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Mantenimientos con abonos vinculados y movimientos de caja...');

        $descripcionesMantenimiento = [
            'Mantenimiento preventivo completo, desmontaje de disipador, limpieza con ultrasonido y cambio de pasta térmica Arctic MX-4.',
            'Formateo en limpio con instalación de Windows 11 Pro 64-bit, activación legal, suite ofimática y respaldo de 120GB de datos personales.',
            'Optimización de arranque, limpieza de virus troyanos y malware residentes, actualización de BIOS y parches de seguridad críticos.',
            'Mantenimiento correctivo por sobrecalentamiento extremo; reconstrucción de anclajes de bisagras y lubricación de ventiladores.',
            'Clonación de disco HDD a SSD NVMe de 500GB, configuración de particiones y reubicación de perfil de usuario para alta velocidad.',
            'Mantenimiento integral a estación de trabajo, limpieza de fuente de poder certificada, ordenamiento de cables y prueba de estrés OCCT 1h.',
            'Cambio de teclado interno remachado en caliente, limpieza de restos de café derramado con alcohol isopropílico y prueba de teclado.',
            'Reinstalación limpia de macOS Sonoma, reseteo de NVRAM/SMC y diagnóstico de batería con informe de ciclos de carga.',
        ];

        for ($i = 1; $i <= 100; $i++) {
            $equipo = $equipos[($i - 1) % count($equipos)];
            $tecnico = $tecnicos[array_rand($tecnicos)];
            $user = $userIds[array_rand($userIds)];
            $idOrden = 'MANT-'.str_pad($i, 4, '0', STR_PAD_LEFT);
            $tipo = ($i % 3 === 0) ? 'preventivo' : 'correctivo';
            $reparacion = ($i % 2 === 0) ? 'software' : 'hardware';
            $costo = rand(8, 55) * 5000; // COP 40.000 a COP 275.000
            $diasAtras = rand(1, 75);
            $fechaEntrada = Carbon::now()->subDays($diasAtras);

            // Diversidad de operaciones:
            // 1 al 10: Anulados (10%)
            // 11 al 45: Terminados y Pagados al 100% (35%)
            // 46 al 80: En Proceso / Terminados con Pago Parcial (35%)
            // 81 al 100: Pendientes sin ningún pago ($0) (20%)
            if ($i <= 10) {
                $estado = 'pendiente';
                $anulado = true;
                $fechaSalida = null;
                $tipoPagoCaso = 'anulado';
            } elseif ($i <= 45) {
                $estado = 'terminado';
                $anulado = false;
                $fechaSalida = (clone $fechaEntrada)->addDays(rand(1, 4));
                $tipoPagoCaso = 'total';
            } elseif ($i <= 80) {
                $estado = ($i % 2 === 0) ? 'en_proceso' : 'terminado';
                $anulado = false;
                $fechaSalida = ($estado === 'terminado') ? (clone $fechaEntrada)->addDays(rand(1, 5)) : null;
                $tipoPagoCaso = 'parcial';
            } else {
                $estado = 'pendiente';
                $anulado = false;
                $fechaSalida = null;
                $tipoPagoCaso = 'cero';
            }

            $mantenimiento = Mantenimiento::create([
                'id_orden' => $idOrden,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida,
                'tipo' => $tipo,
                'reparacion' => $reparacion,
                'descripcion' => $descripcionesMantenimiento[array_rand($descripcionesMantenimiento)],
                'costo' => $costo,
                'estado' => $estado,
                'anulado' => $anulado,
                'equipo_id' => $equipo->id,
                'tecnico_id' => $tecnico->id,
                'user_id' => $user,
            ]);

            // Asignar stock en hardware
            if ($reparacion === 'hardware' && ! $anulado) {
                $stk = $stocks[array_rand($stocks)];
                $mantenimiento->stocks()->attach($stk->id, [
                    'cantidad' => 1,
                    'precio_unitario' => $stk->precio_venta,
                    'created_at' => $fechaEntrada,
                    'updated_at' => $fechaEntrada,
                ]);
            }

            // Generar Abonos y Movimientos de Caja según el caso de negocio
            if ($tipoPagoCaso === 'total') {
                // Pago total: 1 o 2 abonos que suman exactamente el costo
                $abono1Monto = ($costo > 80000 && rand(0, 1)) ? round($costo / 2, -3) : $costo;
                $abono2Monto = $costo - $abono1Monto;

                $ab1 = Abono::create([
                    'mantenimiento_id' => $mantenimiento->id,
                    'monto' => $abono1Monto,
                    'fecha' => $fechaEntrada,
                    'tipo_pago' => rand(0, 1) ? 'efectivo' : 'consignacion',
                    'descripcion' => "Anticipo de recepción Orden #{$idOrden}",
                    'user_id' => $user,
                    'anulado' => false,
                ]);

                MovimientoCaja::create([
                    'empresa' => 'Tecni Systemas',
                    'persona' => $equipo->cliente?->nombre ?? 'Cliente Particular',
                    'fecha' => $fechaEntrada,
                    'concepto_id' => $conceptos['Pago de mantenimiento'],
                    'tipo_movimiento' => 'ingreso',
                    'tipo_pago' => $ab1->tipo_pago,
                    'monto' => $abono1Monto,
                    'monto_total' => $costo,
                    'descripcion' => "Abono #1 a Orden MANT #{$idOrden} - ".($equipo->cliente?->nombre ?? 'Cliente'),
                    'estado' => 'activo',
                    'anulado' => false,
                    'user_id' => $user,
                    'abono_id' => $ab1->id,
                ]);

                if ($abono2Monto > 0) {
                    $fechaEntregaReal = $fechaSalida ?? (clone $fechaEntrada)->addDays(2);
                    $ab2 = Abono::create([
                        'mantenimiento_id' => $mantenimiento->id,
                        'monto' => $abono2Monto,
                        'fecha' => $fechaEntregaReal,
                        'tipo_pago' => 'efectivo',
                        'descripcion' => "Liquidación final contra entrega Orden #{$idOrden}",
                        'user_id' => $user,
                        'anulado' => false,
                    ]);

                    MovimientoCaja::create([
                        'empresa' => 'Tecni Systemas',
                        'persona' => $equipo->cliente?->nombre ?? 'Cliente Particular',
                        'fecha' => $fechaEntregaReal,
                        'concepto_id' => $conceptos['Pago de mantenimiento'],
                        'tipo_movimiento' => 'ingreso',
                        'tipo_pago' => 'efectivo',
                        'monto' => $abono2Monto,
                        'monto_total' => $costo,
                        'descripcion' => "Abono final saldo Orden MANT #{$idOrden} - ".($equipo->cliente?->nombre ?? 'Cliente'),
                        'estado' => 'activo',
                        'anulado' => false,
                        'user_id' => $user,
                        'abono_id' => $ab2->id,
                    ]);
                }
            } elseif ($tipoPagoCaso === 'parcial') {
                // Pago parcial: deja saldo pendiente
                $montoParcial = round($costo * (rand(30, 60) / 100), -3);
                $ab = Abono::create([
                    'mantenimiento_id' => $mantenimiento->id,
                    'monto' => $montoParcial,
                    'fecha' => $fechaEntrada,
                    'tipo_pago' => 'efectivo',
                    'descripcion' => "Abono parcial / anticipo para repuestos Orden #{$idOrden}",
                    'user_id' => $user,
                    'anulado' => false,
                ]);

                MovimientoCaja::create([
                    'empresa' => 'Tecni Systemas',
                    'persona' => $equipo->cliente?->nombre ?? 'Cliente Particular',
                    'fecha' => $fechaEntrada,
                    'concepto_id' => $conceptos['Pago de mantenimiento'],
                    'tipo_movimiento' => 'ingreso',
                    'tipo_pago' => 'efectivo',
                    'monto' => $montoParcial,
                    'monto_total' => $costo,
                    'descripcion' => "Abono parcial Orden MANT #{$idOrden} - Saldo pendiente: $".number_format($costo - $montoParcial, 0, ',', '.'),
                    'estado' => 'activo',
                    'anulado' => false,
                    'user_id' => $user,
                    'abono_id' => $ab->id,
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════
        // 9. CREAR 100 ELECTRÓNICAS (MICROELECTRÓNICA, PAGOS, ANULACIONES)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Órdenes de Electrónica con laboratorio y microelectrónica...');

        $descripcionesElectronica = [
            'Equipo no enciende por cortocircuito primario en línea principal de 19V; sustitución de MOSFETs canal P de entrada y capacitor cerámico SMD en corto.',
            'Fallo de encendido intermitente provocado por chip KBC/EC IT8586E dañado térmicamente; reemplazo y reprogramación de firmware SPI mediante programador RT809H.',
            'Sin imagen en pantalla con backlight encendido; reconstrucción de 3 pistas sulfatadas en circuito LVDS/eDP y cambio de fusible SMD de protección de 3A.',
            'Puerto de carga USB Tipo C chamuscado con deformación de pines; desoldadura por aire caliente a 380°C y soldadura de nuevo conector reforzado.',
            'Equipo enciende ventilador al máximo pero no da POST; reballing de procesador gráfico GPU con esferas de estaño-plomo Sn63Pb37 de 0.45mm.',
            'Corto en riel de memoria RAM DDR4 VDD 1.2V generado por capacitor multicapa perforado; sustitución de PWM controlador de fase y condensador de filtrado.',
            'Corrupción de BIOS UEFI; desoldadura de chip flash SOIC-8 de 16MB, lectura en socket, limpieza de región ME (Management Engine) y grabado verificado.',
            'Línea de alimentación de backlight de 40V abierta por caída de tensión; reemplazo de bobina toroidal y diodo Schottky de alta velocidad.',
        ];

        for ($i = 1; $i <= 100; $i++) {
            $equipo = $equipos[($i + 5) % count($equipos)];
            $tecnico = $tecnicos[array_rand($tecnicos)];
            $user = $userIds[array_rand($userIds)];
            $idOrden = 'ELC-'.str_pad($i, 4, '0', STR_PAD_LEFT);
            $costo = rand(15, 80) * 5000; // COP 75.000 a COP 400.000
            $diasAtras = rand(1, 80);
            $fechaEntrada = Carbon::now()->subDays($diasAtras);

            // Operaciones:
            // 1 al 10: Anuladas
            // 11 al 45: Terminadas y Pagadas al 100%
            // 46 al 80: En Proceso / Parciales
            // 81 al 100: Pendientes con saldo total ($0)
            if ($i <= 10) {
                $estado = 'pendiente';
                $anulado = true;
                $fechaSalida = null;
                $tipoPagoCaso = 'anulado';
            } elseif ($i <= 45) {
                $estado = 'terminado';
                $anulado = false;
                $fechaSalida = (clone $fechaEntrada)->addDays(rand(2, 6));
                $tipoPagoCaso = 'total';
            } elseif ($i <= 80) {
                $estado = ($i % 2 === 0) ? 'pendiente' : 'terminado';
                $anulado = false;
                $fechaSalida = ($estado === 'terminado') ? (clone $fechaEntrada)->addDays(rand(2, 6)) : null;
                $tipoPagoCaso = 'parcial';
            } else {
                $estado = 'pendiente';
                $anulado = false;
                $fechaSalida = null;
                $tipoPagoCaso = 'cero';
            }

            $electronica = Electronica::create([
                'id_orden' => $idOrden,
                'equipo_id' => $equipo->id,
                'descripcion_problema' => $descripcionesElectronica[array_rand($descripcionesElectronica)],
                'tipo' => ($i % 3 === 0) ? 'preventivo' : 'correctivo',
                'reparacion' => 'hardware',
                'costo' => $costo,
                'estado' => $estado,
                'anulado' => $anulado,
                'fecha_entrada' => $fechaEntrada,
                'fecha_salida' => $fechaSalida,
                'tecnico_id' => $tecnico->id,
                'user_id' => $user,
            ]);

            // Stocks asignados
            if (! $anulado && ($i % 2 === 0)) {
                $stk = $stocks[array_rand($stocks)];
                $electronica->stocks()->attach($stk->id, [
                    'cantidad' => 1,
                    'precio_unitario' => $stk->precio_venta,
                    'created_at' => $fechaEntrada,
                    'updated_at' => $fechaEntrada,
                ]);
            }

            // Abonos para Electrónica
            if ($tipoPagoCaso === 'total') {
                $ab = Abono::create([
                    'electronica_id' => $electronica->id,
                    'monto' => $costo,
                    'fecha' => $fechaSalida ?? $fechaEntrada,
                    'tipo_pago' => 'consignacion',
                    'descripcion' => "Pago total por servicio de microelectrónica Orden #{$idOrden}",
                    'user_id' => $user,
                    'anulado' => false,
                ]);

                MovimientoCaja::create([
                    'empresa' => 'Tecni Systemas',
                    'persona' => $equipo->cliente?->nombre ?? 'Cliente Laboratorio',
                    'fecha' => $fechaSalida ?? $fechaEntrada,
                    'concepto_id' => $conceptos['Servicio técnico'],
                    'tipo_movimiento' => 'ingreso',
                    'tipo_pago' => 'consignacion',
                    'monto' => $costo,
                    'monto_total' => $costo,
                    'descripcion' => "Abono total a Electrónica ELC #{$idOrden} - ".($equipo->cliente?->nombre ?? 'Cliente'),
                    'estado' => 'activo',
                    'anulado' => false,
                    'user_id' => $user,
                    'abono_id' => $ab->id,
                ]);
            } elseif ($tipoPagoCaso === 'parcial') {
                $montoParcial = round($costo * (rand(35, 55) / 100), -3);
                $ab = Abono::create([
                    'electronica_id' => $electronica->id,
                    'monto' => $montoParcial,
                    'fecha' => $fechaEntrada,
                    'tipo_pago' => 'efectivo',
                    'descripcion' => "Anticipo de laboratorio para componentes Orden #{$idOrden}",
                    'user_id' => $user,
                    'anulado' => false,
                ]);

                MovimientoCaja::create([
                    'empresa' => 'Tecni Systemas',
                    'persona' => $equipo->cliente?->nombre ?? 'Cliente Laboratorio',
                    'fecha' => $fechaEntrada,
                    'concepto_id' => $conceptos['Servicio técnico'],
                    'tipo_movimiento' => 'ingreso',
                    'tipo_pago' => 'efectivo',
                    'monto' => $montoParcial,
                    'monto_total' => $costo,
                    'descripcion' => "Abono parcial Electrónica ELC #{$idOrden} - Saldo pendiente: $".number_format($costo - $montoParcial, 0, ',', '.'),
                    'estado' => 'activo',
                    'anulado' => false,
                    'user_id' => $user,
                    'abono_id' => $ab->id,
                ]);
            }
        }

        // ════════════════════════════════════════════════════════════════
        // 10. CREAR 100 FACTURAS (VENTAS Y COMPRAS, ITEMS, ESTADOS)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Facturas comerciales (ventas y compras con ítems y movimientos)...');

        for ($i = 1; $i <= 100; $i++) {
            $numFactura = 'F-'.str_pad($i, 4, '0', STR_PAD_LEFT);
            $esVenta = ($i <= 60); // 60 ventas, 40 compras
            $tipoMovimiento = $esVenta ? 'venta' : 'compra';
            $user = $userIds[array_rand($userIds)];
            $fecha = Carbon::now()->subDays(rand(1, 90));

            if ($esVenta) {
                $facturable = $clientes[array_rand($clientes)];
                $facturableType = Cliente::class;
                $facturableNombre = $facturable->nombre;
            } else {
                $facturable = $proveedores[array_rand($proveedores)];
                $facturableType = Proveedor::class;
                $facturableNombre = $facturable->nombre_razon_social;
            }

            // Crear factura temporal para calcular ítems
            $factura = Factura::create([
                'numero_factura' => $numFactura,
                'tipo_movimiento' => $tipoMovimiento,
                'estado' => 'emitida', // provisional
                'facturable_id' => $facturable->id,
                'facturable_type' => $facturableType,
                'total_documento' => 0,
                'total_pagado' => 0,
                'observaciones' => "Factura comercial generada para {$facturableNombre}.",
                'fecha' => $fecha,
                'user_id' => $user,
            ]);

            // Crear entre 1 y 4 ítems de factura
            $cantItems = rand(1, 4);
            $totalDoc = 0;
            for ($k = 0; $k < $cantItems; $k++) {
                $stk = $stocks[array_rand($stocks)];
                $qty = rand(1, 3);
                $precioUnitario = $esVenta ? (float) $stk->precio_venta : (float) $stk->precio_compra;
                $totalDoc += ($qty * $precioUnitario);

                FacturaItem::create([
                    'factura_id' => $factura->id,
                    'stock_id' => $stk->id,
                    'cantidad' => $qty,
                    'precio_unitario' => $precioUnitario,
                ]);
            }

            // Operaciones de negocio en Facturas:
            // 1 al 10: Anuladas
            // 11 al 55: Pagadas al 100% ('emitida')
            // 56 al 85: Pago parcial ('pendiente_pago' con saldo)
            // 86 al 100: Totalmente pendientes ($0 pagado, 'pendiente_pago')
            if ($i <= 10) {
                $estado = 'anulada';
                $totalPagado = 0;
                $obs = "[ANULADA el {$fecha->format('d/m/Y')} por Error en facturación] Factura comercial.";
            } elseif ($i <= 55) {
                $estado = 'emitida';
                $totalPagado = $totalDoc;
                $obs = 'Factura cancelada en su totalidad. Documento cerrado.';

                // Generar movimiento de caja vinculado
                MovimientoCaja::create([
                    'empresa' => 'Tecni Systemas',
                    'persona' => $facturableNombre,
                    'fecha' => $fecha,
                    'concepto_id' => $esVenta ? $conceptos['Venta de repuesto'] : $conceptos['Compra de insumos'],
                    'tipo_movimiento' => $esVenta ? 'ingreso' : 'egreso',
                    'tipo_pago' => rand(0, 1) ? 'efectivo' : 'consignacion',
                    'monto' => $totalPagado,
                    'monto_total' => $totalDoc,
                    'descripcion' => "Pago total factura #{$numFactura} - {$facturableNombre}",
                    'estado' => 'activo',
                    'anulado' => false,
                    'user_id' => $user,
                    'factura_id' => $factura->id,
                ]);
            } elseif ($i <= 85) {
                $estado = 'pendiente_pago';
                $totalPagado = round($totalDoc * (rand(30, 65) / 100), -2);
                $saldo = $totalDoc - $totalPagado;
                $obs = 'Pago parcial registrado. ⚠️ SALDO PENDIENTE: $'.number_format($saldo, 0, ',', '.');

                // Generar movimiento parcial de caja
                MovimientoCaja::create([
                    'empresa' => 'Tecni Systemas',
                    'persona' => $facturableNombre,
                    'fecha' => $fecha,
                    'concepto_id' => $esVenta ? $conceptos['Venta de repuesto'] : $conceptos['Compra de insumos'],
                    'tipo_movimiento' => $esVenta ? 'ingreso' : 'egreso',
                    'tipo_pago' => 'efectivo',
                    'monto' => $totalPagado,
                    'monto_total' => $totalDoc,
                    'descripcion' => "Abono a factura #{$numFactura} - Saldo restante: $".number_format($saldo, 0, ',', '.'),
                    'estado' => 'activo',
                    'anulado' => false,
                    'user_id' => $user,
                    'factura_id' => $factura->id,
                ]);
            } else {
                $estado = 'pendiente_pago';
                $totalPagado = 0;
                $obs = 'Factura emitida a crédito comercial. ⚠️ SALDO PENDIENTE: $'.number_format($totalDoc, 0, ',', '.');
            }

            $factura->update([
                'total_documento' => $totalDoc,
                'total_pagado' => $totalPagado,
                'estado' => $estado,
                'observaciones' => $obs,
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 11. CREAR 100 COTIZACIONES (CLIENTES Y PROVEEDORES, ESTADOS)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 100 Cotizaciones con estados comerciales y líneas detalladas...');

        $estadosCot = ['aprobada', 'pendiente', 'rechazada', 'vencida'];

        for ($i = 1; $i <= 100; $i++) {
            $codigo = 'COT-'.str_pad($i, 4, '0', STR_PAD_LEFT);
            $esCliente = ($i <= 75); // 75 clientes, 25 proveedores
            $clienteId = $esCliente ? $clientes[array_rand($clientes)]->id : null;
            $proveedorId = ! $esCliente ? $proveedores[array_rand($proveedores)]->id : null;
            $user = $userIds[array_rand($userIds)];
            $fecha = Carbon::now()->subDays(rand(1, 60));
            $validezDias = [15, 30, 45, 60][array_rand([15, 30, 45, 60])];

            // 10 anuladas, el resto según distribución
            $anulado = ($i <= 10);
            $estado = $anulado ? 'rechazada' : $estadosCot[array_rand($estadosCot)];

            $cotizacion = Cotizacion::create([
                'codigo' => $codigo,
                'cliente_id' => $clienteId,
                'proveedor_id' => $proveedorId,
                'fecha' => $fecha,
                'validez_dias' => $validezDias,
                'total' => 0,
                'estado' => $estado,
                'anulado' => $anulado,
                'notas' => 'Cotización comercial sujeta a disponibilidad de inventario. Precios en COP no incluyen retenciones adicionales.',
                'user_id' => $user,
            ]);

            $cantLineas = rand(1, 4);
            $totalCot = 0;
            for ($k = 0; $k < $cantLineas; $k++) {
                $stk = $stocks[array_rand($stocks)];
                $qty = rand(1, 5);
                $precio = (float) $stk->precio_venta;
                $subtotal = $qty * $precio;
                $totalCot += $subtotal;

                CotizacionItem::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => ($k % 2 === 0) ? 'stock' : 'libre',
                    'item_id' => ($k % 2 === 0) ? $stk->id : null,
                    'descripcion' => $stk->producto,
                    'cantidad' => $qty,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ]);
            }

            $cotizacion->update(['total' => $totalCot]);
        }

        // ════════════════════════════════════════════════════════════════
        // 12. CREAR MOVIMIENTOS GENERALES DE CAJA (100+ TOTAL EN EL SISTEMA)
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando Movimientos adicionales de Caja general (ingresos y egresos operativos)...');

        $gastosOperativos = [
            ['Arriendo local comercial principal Mes en curso', $conceptos['Arriendo'], 'egreso', 1200000, 'Inmobiliaria del Café S.A.S.'],
            ['Pago servicio de energía eléctrica CHEC taller y vitrinas', $conceptos['Pago de servicios'], 'egreso', 285000, 'Central Hidroeléctrica de Caldas'],
            ['Factura servicio de internet fibra óptica 500MB simétrica', $conceptos['Pago de servicios'], 'egreso', 115000, 'Movistar Empresas'],
            ['Adquisición de insumos de aseo, cafetería y papelería técnica', $conceptos['Gastos varios'], 'egreso', 65000, 'Supermercado Central'],
            ['Honorarios quincenales por soporte y diagnóstico externo', $conceptos['Nómina / Pago técnico'], 'egreso', 650000, 'Técnico Especialista'],
            ['Apertura de base de caja para vueltos jornada matutina', $conceptos['Apertura de caja'], 'ingreso', 200000, 'Caja Principal'],
            ['Venta directa mostrador cargador universal y cable HDMI', $conceptos['Venta de repuesto'], 'ingreso', 97000, 'Cliente Mostrador'],
            ['Servicio de escaneo de virus y optimización flash en mostrador', $conceptos['Otros ingresos'], 'ingreso', 35000, 'Usuario Particular'],
            ['Compra de herramientas y puntas de precisión para taller', $conceptos['Otros egresos'], 'egreso', 84000, 'Ferretería Industrial'],
        ];

        for ($i = 1; $i <= 30; $i++) {
            $gasto = $gastosOperativos[($i - 1) % count($gastosOperativos)];
            $fecha = Carbon::now()->subDays(rand(1, 30));
            $tipoPago = ($gasto[2] === 'egreso' && $gasto[3] > 300000) ? 'consignacion' : 'efectivo';

            MovimientoCaja::create([
                'empresa' => 'Tecni Systemas',
                'persona' => $gasto[4],
                'fecha' => $fecha,
                'concepto_id' => $gasto[1],
                'tipo_movimiento' => $gasto[2],
                'tipo_pago' => $tipoPago,
                'monto' => $gasto[3],
                'monto_total' => $gasto[3],
                'descripcion' => $gasto[0]." (Ref. OP-{$i})",
                'estado' => 'activo',
                'anulado' => ($i === 28), // 1 anulado para prueba
                'user_id' => $userIds[array_rand($userIds)],
            ]);
        }

        // ════════════════════════════════════════════════════════════════
        // 13. CREAR 25 CIERRES DE CAJA CON CONCILIACIÓN FÍSICA
        // ════════════════════════════════════════════════════════════════
        $this->command?->info('Sembrando 25 Cierres de Caja con conciliaciones (cuadrados, faltantes, sobrantes)...');

        $motivosDiferencia = [
            'sobrante' => 'Cliente no esperó devuelta voluntariamente tras pago en efectivo.',
            'faltante' => 'Diferencia en conteo físico atribuida a error en cambio de billete de $50.000.',
        ];

        for ($d = 25; $d >= 1; $d--) {
            $fechaCierre = Carbon::now()->subDays($d)->format('Y-m-d');

            // Calcular movimientos reales de ese día o generar coherentes
            $ingresosDia = MovimientoCaja::where('fecha', $fechaCierre)
                ->where('tipo_movimiento', 'ingreso')
                ->where('anulado', false)
                ->sum('monto');

            $egresosDia = MovimientoCaja::where('fecha', $fechaCierre)
                ->where('tipo_movimiento', 'egreso')
                ->where('anulado', false)
                ->sum('monto');

            $efectivoDia = MovimientoCaja::where('fecha', $fechaCierre)
                ->where('tipo_pago', 'efectivo')
                ->where('tipo_movimiento', 'ingreso')
                ->where('anulado', false)
                ->sum('monto') - MovimientoCaja::where('fecha', $fechaCierre)
                ->where('tipo_pago', 'efectivo')
                ->where('tipo_movimiento', 'egreso')
                ->where('anulado', false)
                ->sum('monto');

            $efectivoDia = max(50000, $efectivoDia);
            $consignacionDia = max(0, $ingresosDia - $efectivoDia);
            $saldoFinal = $ingresosDia - $egresosDia;
            $numMovs = MovimientoCaja::where('fecha', $fechaCierre)->count() ?: rand(3, 10);

            // Variabilidad de conciliación física:
            // d % 5 == 0 -> Sobrante
            // d % 7 == 0 -> Faltante
            // d == 1 -> Sin conciliar
            // Resto -> Cuadrado exacto
            if ($d % 5 === 0) {
                $dif = 15000.00;
                $contado = $efectivoDia + $dif;
                $motivo = $motivosDiferencia['sobrante'];
            } elseif ($d % 7 === 0) {
                $dif = -20000.00;
                $contado = max(0, $efectivoDia + $dif);
                $motivo = $motivosDiferencia['faltante'];
            } elseif ($d === 1) {
                $dif = 0;
                $contado = null; // Sin conciliar
                $motivo = null;
            } else {
                $dif = 0;
                $contado = $efectivoDia; // Cuadrado perfecto
                $motivo = null;
            }

            CierreCaja::updateOrCreate(
                ['fecha' => $fechaCierre],
                [
                    'total_ingresos' => $ingresosDia,
                    'total_egresos' => $egresosDia,
                    'efectivo' => $efectivoDia,
                    'efectivo_real_contado' => $contado,
                    'diferencia' => $dif,
                    'consignacion' => $consignacionDia,
                    'saldo_final' => $saldoFinal,
                    'num_movimientos' => $numMovs,
                    'bloqueado' => true,
                    'observaciones' => "Cierre operativo verificado para la fecha {$fechaCierre}.",
                    'motivo_diferencia' => $motivo,
                    'user_id' => $adminUser->id,
                ]
            );
        }

        $this->command?->info('✅ ¡Siembra masiva de 100 registros por tabla completada con éxito!');
    }
}
