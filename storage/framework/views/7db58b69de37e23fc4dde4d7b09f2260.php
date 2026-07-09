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
    <title>Reporte de Mantenimientos – <?php echo e($empresa->nombre); ?></title>
    <style>
        @page {
            size: A4 <?php echo e($orientation ?? 'landscape'); ?>;
            margin: 20px 25px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: sans-serif;
            font-size: 8.2px;
            color: #000000;
            background: #fff;
            line-height: 1.35;
            margin: 20px 25px !important;
        }

        /* ─── HEADER ─── */
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
            color: #000000;
            line-height: 1.3;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 11px;
            font-weight: 600;
            color: #000000;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }
        .header-meta {
            font-size: 7.5px;
            color: #000000;
            margin-top: 5px;
        }
        .header-badge {
            display: inline-block;
            background: #2d3748;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 0px;
            letter-spacing: 0.5px;
        }
        .header-count {
            font-size: 9px;
            color: #000000;
            margin-top: 4px;
        }

        /* ─── SUMMARY ROW ─── */
        .summary-bar {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 0px;
            padding: 6px 10px;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
        .summary-label { font-size: 6.5px; color: #000000; text-transform: uppercase; letter-spacing: 0.4px; }
        .summary-value { font-size: 11px; font-weight: 800; color: #000000; }
        .summary-divider {
            display: table-cell;
            width: 1px;
            background: #cbd5e1;
            padding: 0;
        }

        /* ─── TABLE ─── */
        table { width: 100%; border-collapse: collapse; }

        thead tr th {
            background: #2d3748;
            color: #fff;
            padding: 5px 4px;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #cbd5e0;
        }
        thead tr th:first-child { border-radius: 0; }

        tbody tr td {
            border: 1px solid #cbd5e0;
            padding: 4px 4px;
            vertical-align: middle;
            font-size: 7.8px;
            color: #000000;
        }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tbody tr.anulado td {
            opacity: 0.55;
            font-style: italic;
            background: #fff5f5 !important;
        }
        tbody tr { page-break-inside: avoid; }

        .col-center { text-align: center; }
        .col-right  { text-align: right; }
        .col-bold   { font-weight: 700; }

        /* ─── BADGES ─── */
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 0px;
            font-size: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none !important;
            background: transparent !important;
            color: #000000 !important;
            white-space: nowrap;
        }
        .badge-preventivo { background:transparent; color:#000000; }
        .badge-correctivo { background:transparent; color:#000000; }
        .badge-pendiente  { background:transparent; color:#000000; }
        .badge-en_proceso { background:transparent; color:#000000; }
        .badge-reparado   { background:transparent; color:#000000; }
        .badge-terminado  { background:transparent; color:#000000; }
        .badge-entregado  { background:transparent; color:#000000; }
        .badge-activo     { background:transparent; color:#000000; }
        .badge-anulado    { background:transparent; color:#000000; }

        .sub-text { font-size: 7px; color: #000000; margin-top: 1px; }

        /* ─── FOOTER TABLE ─── */
        tfoot tr td {
            background: #2d3748;
            color: #fff;
            font-weight: 700;
            font-size: 7.8px;
            border: none !important;
            padding: 5px 4px;
        }

        .monto-cell { color: #000000; font-weight: 700; }

        /* ─── DOCUMENT FOOTER ─── */
        .doc-footer {
            margin-top: 14px;
            padding-top: 7px;
            border-top: 1px solid #cbd5e0;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; font-size: 7px; color: #000000; font-style: italic; }
        .footer-right { display: table-cell; text-align: right; font-size: 7px; color: #000000; }

        .page-number:before { content: "Página " counter(page) " de " counter(pages); }
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
                <span style="font-size: 14px; font-weight: bold; color: #2d3748; text-transform: uppercase;"><?php echo e($empresa->nombre); ?></span>
            <?php endif; ?>
        </div>
        <div class="header-info-cell">
            <div style="font-size: 11px; font-weight: bold; color: #2d3748; text-transform: uppercase; margin-bottom: 3px;">REPORTE DETALLADO DE MANTENIMIENTOS</div>
            <?php if($empresa->nit): ?><div><strong>NIT:</strong> <?php echo e($empresa->nit); ?></div><?php endif; ?>
            <?php if($empresa->telefono): ?><div><strong>Tel:</strong> <?php echo e($empresa->telefono); ?></div><?php endif; ?>
            <?php if($empresa->direccion): ?><div><strong>Dir:</strong> <?php echo e($empresa->direccion); ?></div><?php endif; ?>
        </div>
    </div>

    <div style="font-size: 9px; color: #000000; margin-bottom: 12px; padding: 4px 0;">
        <strong>Generado:</strong> <?php echo e(\Carbon\Carbon::now()->format('d/m/Y H:i')); ?> &nbsp;|&nbsp;
        <strong>Registros:</strong> <?php echo e(count($mantenimientos)); ?>

    </div>

    
    <?php
        $totalCosto    = $mantenimientos->sum('costo');
        $totalActivos  = $mantenimientos->where('anulado', false)->count();
        $totalAnulados = $mantenimientos->where('anulado', true)->count();
        $totalEntregados = $mantenimientos->where('estado', 'entregado')->count();
    ?>
    <div class="summary-bar">
        <div class="summary-item">
            <div class="summary-label">Total Órdenes</div>
            <div class="summary-value"><?php echo e(count($mantenimientos)); ?></div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Activos</div>
            <div class="summary-value" style="color:#000000"><?php echo e($totalActivos); ?></div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Anulados</div>
            <div class="summary-value" style="color:#000000"><?php echo e($totalAnulados); ?></div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Entregados</div>
            <div class="summary-value" style="color:#000000"><?php echo e($totalEntregados); ?></div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Costo Total</div>
            <div class="summary-value" style="color:#000000; font-size:9px">$<?php echo e(number_format($totalCosto, 0, ',', '.')); ?></div>
        </div>
    </div>

    
    <table>
        <thead>
            <tr>
                <th style="width:6%">Orden</th>
                <th style="width:13%">Cliente</th>
                <th style="width:14%">Equipo / Info</th>
                <th style="width:10%">Técnico</th>
                <th style="width:8%">Tipo</th>
                <th style="width:9%">Reparación</th>
                <th style="width:9%">Progreso</th>
                <th style="width:7%">Estado</th>
                <th style="width:8%">Entrada</th>
                <th style="width:8%">Salida</th>
                <th style="width:8%; text-align:right">Costo</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $mantenimientos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $isAnulado = !empty($m->anulado);
                $progreso  = strtolower($m->estado ?? '');
                $tipo      = strtolower($m->tipo ?? '');
                $reparacion = $m->reparacion ?? '';
            ?>
            <tr class="<?php echo e($isAnulado ? 'anulado' : ''); ?>">
                <td class="col-center col-bold"><?php echo e($m->id_orden); ?></td>
                <td>
                    <div class="col-bold"><?php echo e($m->equipo->cliente->nombre ?? 'N/A'); ?></div>
                    <div class="sub-text"><?php echo e($m->equipo->cliente->identificacion ?? '-'); ?></div>
                </td>
                <td>
                    <div class="col-bold"><?php echo e($m->equipo->nombre ?? 'N/A'); ?></div>
                    <div class="sub-text"><?php echo e($m->equipo->marca ?? ''); ?> <?php echo e($m->equipo->modelo ?? ''); ?> &mdash; <?php echo e(strtoupper($m->equipo->serie ?? '')); ?></div>
                </td>
                <td class="col-center"><?php echo e($m->tecnico->nombre ?? 'N/A'); ?></td>
                <td class="col-center">
                    <span class="badge badge-<?php echo e($tipo ?: 'correctivo'); ?>"><?php echo e(ucfirst($tipo) ?: '—'); ?></span>
                </td>
                <td class="col-center" style="font-size:7.5px"><?php echo e(ucfirst($reparacion) ?: '—'); ?></td>
                <td class="col-center">
                    <span class="badge badge-<?php echo e($progreso ?: 'pendiente'); ?>"><?php echo e(ucfirst($progreso) ?: '—'); ?></span>
                </td>
                <td class="col-center">
                    <span class="badge <?php echo e($isAnulado ? 'badge-anulado' : 'badge-activo'); ?>">
                        <?php echo e($isAnulado ? 'Anulado' : 'Activo'); ?>

                    </span>
                </td>
                <td class="col-center"><?php echo e(\Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y')); ?></td>
                <td class="col-center"><?php echo e($m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '—'); ?></td>
                <td class="col-right monto-cell">$<?php echo e(number_format($m->costo, 0, '', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="11" style="text-align:center; padding:20px; color:#94a3b8; font-style:italic;">
                    No hay registros para mostrar con los filtros aplicados.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
        <?php if(count($mantenimientos) > 0): ?>
        <tfoot>
            <tr>
                <td colspan="10" style="text-align:left; letter-spacing:0.5px; text-transform:uppercase; padding: 5px 6px; border:none !important;">
                    <span style="float:left; font-weight:700;">TOTAL: <?php echo e(count($mantenimientos)); ?> REGISTROS</span>
                    <span style="float:right; font-weight:700;">COSTO ACUMULADO:</span>
                </td>
                <td style="text-align:right; font-weight:800; padding: 5px 6px; border:none !important;">
                    $<?php echo e(number_format($mantenimientos->sum('costo'), 0, '', '.')); ?>

                </td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

</body>
</html>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/mantenimientos/pdf.blade.php ENDPATH**/ ?>