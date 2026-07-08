<?php
    $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
    $logoBase64 = null;
    if ($empresa->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->logo_path)) {
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($empresa->logo_path);
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe Financiero</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { margin: 25px 30px; }
        body { font-family: sans-serif; font-size: 9px; color: #1a202c; background: #fff; margin: 25px 30px !important; }

        .report-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #2d3748;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header-logo-cell {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
        }
        .header-info-cell {
            display: table-cell;
            width: 60%;
            text-align: right;
            vertical-align: middle;
            font-size: 8px;
            color: #4a5568;
            line-height: 1.3;
        }

        /* Tarjetas resumen */
        .summary-grid { display: table; width: 100%; margin-bottom: 16px; border-spacing: 6px; }
        .summary-grid .card { display: table-cell; width: 25%; border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center; vertical-align: middle; }
        .card .card-label { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #718096; margin-bottom: 3px; }
        .card .card-value { font-size: 13px; font-weight: 800; }
        .card.ingresos .card-value { color: #000000; }
        .card.egresos  .card-value { color: #000000; }
        .card.mantenimientos .card-value { color: #000000; }
        .card.anulados .card-value { color: #000000; }

        /* Tabla */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table { width: 100%; border-collapse: collapse; }
        thead tr th {
            background-color: #2d3748;
            color: #ffffff;
            padding: 5px 6px;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #cbd5e0;
        }
        tbody tr td {
            border: 1px solid #cbd5e0;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 8.5px;
        }
        tbody tr:nth-child(even) { background-color: #f7fafc; }
        tbody tr.anulado { opacity: 0.55; }

        td.tipo-badge { text-align: left; }
        .badge {
            font-size: 8.5px;
            font-weight: normal;
            text-transform: uppercase;
        }
        .pill-active  { color: #000000; font-weight: normal; }
        .pill-anulado { color: #000000; font-weight: normal; font-style: italic; }

        td.monto { text-align: center; font-weight: 700; }
        td.monto.positivo { color: #000000; }
        td.monto.negativo { color: #000000; }

        tfoot tr td {
            background: #edf2f7;
            font-weight: 800;
            font-size: 9px;
            border: 1px solid #cbd5e0;
            padding: 5px 6px;
        }

        .footer { margin-top: 16px; text-align: right; font-size: 8px; color: #718096; font-style: italic; }
    </style>
</head>
<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 7.5;
            $color = array(0.44, 0.50, 0.59); // rgb(113, 128, 150)
            $y = $pdf->get_height() - 24;
            $x = $pdf->get_width() - 85;
            $pdf->page_text($x, $y, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, $size, $color);
        }
    </script>
    <div class="report-header">
        <div class="header-logo-cell">
            <?php if($logoBase64): ?>
                <img src="<?php echo e($logoBase64); ?>" alt="Logo" style="max-height: 50px; max-width: 160px; object-fit: contain;">
            <?php else: ?>
                <span style="font-size: 14px; font-weight: bold; color: #1a202c; text-transform: uppercase;"><?php echo e($empresa->nombre); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-info-cell">
            <div style="font-size: 11px; font-weight: bold; color: #1a202c; text-transform: uppercase; margin-bottom: 3px;">INFORME FINANCIERO ACUMULADO</div>
            <?php if($empresa->nit): ?><div><strong>NIT:</strong> <?php echo e($empresa->nit); ?></div><?php endif; ?>
            <?php if($empresa->telefono): ?><div><strong>Tel:</strong> <?php echo e($empresa->telefono); ?></div><?php endif; ?>
            <?php if($empresa->direccion): ?><div><strong>Dir:</strong> <?php echo e($empresa->direccion); ?></div><?php endif; ?>
        </div>
    </div>

    <div style="font-size: 9px; color: #4a5568; margin-bottom: 12px; padding: 4px 0;">
        <?php if(isset($fecha)): ?><strong>Período:</strong> <?php echo e($fecha); ?> &nbsp;|&nbsp; <?php endif; ?>
        <strong>Generado:</strong> <?php echo e(date('d/m/Y h:i A')); ?>

    </div>

    <?php if(isset($acumulado)): ?>
    <div class="summary-grid" style="margin-bottom: 8px;">
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Mantenimientos</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['facturado_mant'] ?? 0, 0, ',', '.')); ?></div>
        </div>
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Electrónica</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['facturado_elec'] ?? 0, 0, ',', '.')); ?></div>
        </div>
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Compras</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['compras_inventario'] ?? 0, 0, ',', '.')); ?></div>
        </div>
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Ventas</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['ventas_inventario'] ?? 0, 0, ',', '.')); ?></div>
        </div>
    </div>
    <div class="summary-grid">
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Ingresos Reales</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['ingresos_caja'] ?? 0, 0, ',', '.')); ?></div>
        </div>
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Egresos Reales</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['egresos_caja'] ?? 0, 0, ',', '.')); ?></div>
        </div>
        <div class="card" style="border-color: #e2e8f0;">
            <div class="card-label">Balance Neto</div>
            <div class="card-value" style="color: #000000;">$<?php echo e(number_format($acumulado['balance_neto'] ?? 0, 0, ',', '.')); ?></div>
        </div>
    </div>
    <?php endif; ?>

        <p class="section-title">Resumen Consolidado del Período</p>

    <table>
        <thead>
            <tr>
                <th style="width:40%; text-align:left">Categoría</th>
                <th style="width:30%; text-align:center">Cantidad de Movimientos</th>
                <th style="width:30%; text-align:center">Costo Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Mantenimientos</td>
                <td style="text-align:center"><?php echo e($acumulado['total_mantenimientos'] ?? 0); ?></td>
                <td class="monto" style="text-align:center">$<?php echo e(number_format($acumulado['facturado_mant'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
            <tr>
                <td>Electrónica</td>
                <td style="text-align:center"><?php echo e($acumulado['total_electronicas'] ?? 0); ?></td>
                <td class="monto" style="text-align:center">$<?php echo e(number_format($acumulado['facturado_elec'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
            <tr>
                <td>Compras de Inventario</td>
                <td style="text-align:center"><?php echo e($acumulado['total_compras'] ?? 0); ?></td>
                <td class="monto" style="text-align:center">$<?php echo e(number_format($acumulado['compras_inventario'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
            <tr>
                <td>Ventas de Inventario</td>
                <td style="text-align:center"><?php echo e($acumulado['total_ventas'] ?? 0); ?></td>
                <td class="monto" style="text-align:center">$<?php echo e(number_format($acumulado['ventas_inventario'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
            <tr>
                <td>Ingresos Reales (Caja)</td>
                <td style="text-align:center"><?php echo e($acumulado['total_ingresos'] ?? 0); ?></td>
                <td class="monto" style="text-align:center">$<?php echo e(number_format($acumulado['ingresos_caja'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
            <tr>
                <td>Egresos Reales (Caja)</td>
                <td style="text-align:center"><?php echo e($acumulado['total_egresos'] ?? 0); ?></td>
                <td class="monto" style="text-align:center">$<?php echo e(number_format($acumulado['egresos_caja'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
            <tr class="anulado">
                <td style="color:#718096">Movimientos Anulados</td>
                <td style="text-align:center; color:#718096"><?php echo e($acumulado['total_anulados'] ?? 0); ?></td>
                <td style="text-align:center; color:#000000;">$<?php echo e(number_format($acumulado['total_costo_anulados'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">Balance Neto del Período:</td>
                <td style="text-align:center; color: #000000; font-weight: bold;">$<?php echo e(number_format($acumulado['balance_neto'] ?? 0, 0, ',', '.')); ?></td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/reportes_financieros/pdf_acumulado.blade.php ENDPATH**/ ?>