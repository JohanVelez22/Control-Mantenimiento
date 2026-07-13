<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['facturas', 'factura_items', 'movimiento_cajas', 'mantenimientos', 'electronicas', 'stocks', 'abonos', 'clientes', 'proveedores', 'equipos', 'cierre_cajas'];
$indexes = [
    'idx_facturas_estado_tipo', 'idx_facturas_facturable', 'idx_facturas_fecha_estado', 'idx_facturas_saldo_pendiente',
    'idx_fact_tipo_fecha', 'idx_fact_tipo_estado', 'facturas_facturable_type_facturable_id_index',
    'idx_movcaja_tipo_fecha', 'idx_movcaja_estado_anulado', 'idx_movcaja_parent', 'idx_movcaja_concepto_fecha',
    'idx_mant_estado_anulado', 'idx_mant_fecha_anulado', 'idx_mant_equipo_anulado', 'idx_mant_tecnico_anulado',
    'idx_elec_estado_anulado', 'idx_elec_fecha_anulado', 'idx_elec_equipo_anulado', 'idx_elec_tecnico_anulado',
    'idx_stock_active_categoria', 'idx_stocks_active_cantidad', 'idx_stocks_proveedor_active', 'idx_stocks_categoria',
    'idx_abonos_fecha', 'idx_abonos_mant_fecha', 'idx_abonos_elec_fecha',
    'idx_clientes_active', 'idx_proveedores_active', 'idx_equipos_cliente_active',
    'idx_cierrecaja_fecha', 'idx_facturaitems_factura', 'idx_facturaitems_stock',
];

foreach ($indexes as $idx) {
    foreach ($tables as $t) {
        try { 
            DB::statement("ALTER TABLE `$t` DROP INDEX `$idx`"); 
            echo "Dropped $idx on $t\n"; 
        } catch (Exception $e) {}
    }
}
echo 'Done\n';