<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $__env->yieldContent('title', 'Documento'); ?></title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        @page {
            size: A4 portrait;
            margin: 5mm 6mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #111;
            background: #fff;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .invoice-wrapper {
            position: relative;
            height: 138mm; /* Exact half A4 height (148.5mm) minus 10mm margins */
            box-sizing: border-box;
            border-bottom: 1px dashed #aaa; /* Cut line */
            padding-bottom: 30px; /* Space for footer */
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header-logo {
            max-width: 220px;
            max-height: 80px;
            object-fit: contain;
        }
        .header-info {
            text-align: right;
        }
        .header-info h1 {
            margin: 0 0 3px;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-info p {
            margin: 1px 0;
            font-size: 8.5pt;
            color: #333;
        }
        .doc-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            background-color: #f3f4f6;
            padding: 4px;
            border: 1px solid #ddd;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-col p {
            margin: 2px 0;
            font-size: 9pt;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 8.5pt;
        }
        table.items-table th {
            background-color: #f3f4f6;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.items-table td.text-right { text-align: right; }
        table.items-table th.text-right { text-align: right; }
        table.items-table td.text-center { text-align: center; }
        table.items-table th.text-center { text-align: center; }

        .totals {
            width: 45%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .totals td {
            padding: 3px 5px;
            font-size: 9pt;
        }
        .totals td.lbl { font-weight: bold; text-align: right; }
        .totals td.val { text-align: right; border-bottom: 1px solid #ddd; }
        .totals tr.grand-total td { font-size: 10pt; font-weight: bold; border-top: 2px solid #000; border-bottom: none; }
        
        .clearfix::after { content: ""; clear: both; display: table; }

        .footer {
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            padding-top: 5px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 7.5pt;
            color: #555;
        }
        
        .watermark-container { position: relative; height: 100%; }
        .watermark-container.anulado::after {
            content: "ANULADO";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 6rem;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.15);
            z-index: 1000;
            pointer-events: none;
            white-space: nowrap;
        }
        @media print {
            .watermark-container.anulado::after {
                color: rgba(200, 0, 0, 0.2) !important;
            }
        }
        .text-center { text-align: center; }
        .mt-4 { margin-top: 15px; }
        .mb-4 { margin-bottom: 15px; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
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

    <div class="invoice-wrapper">
        <div class="watermark-container <?php echo $__env->yieldContent('watermark_class'); ?>">
            <div class="header">
                <?php if($logoBase64): ?>
                    <div style="display: inline-block; width: 38%; vertical-align: top;">
                        <img src="<?php echo e($logoBase64); ?>" alt="Logo" class="header-logo">
                    </div>
                <?php else: ?>
                    <div style="display: inline-block; width: 38%; vertical-align: top;">
                        <div class="header-logo" style="width: 180px; height: 75px; line-height: 75px; text-align: center; background: #eee; font-size: 10pt; color: #666; font-weight: bold;">SIN LOGO</div>
                    </div>
                <?php endif; ?>
                <div class="header-info" style="display: inline-block; width: 60%; text-align: right; vertical-align: top;">
                    <?php if(!$logoBase64): ?>
                        <h1><?php echo e(Str::upper($empresa->nombre)); ?></h1>
                    <?php endif; ?>
                    <?php if($empresa->nit): ?><p><strong>NIT:</strong> <?php echo e($empresa->nit); ?></p><?php endif; ?>
                    <?php if($empresa->telefono): ?><p><strong>Tel:</strong> <?php echo e($empresa->telefono); ?></p><?php endif; ?>
                    <?php if($empresa->direccion): ?><p><strong>Dir:</strong> <?php echo e($empresa->direccion); ?></p><?php endif; ?>
                    <?php if($empresa->correo): ?><p><strong>Email:</strong> <?php echo e($empresa->correo); ?></p><?php endif; ?>
                </div>
            </div>

        <div class="doc-title">
            <?php echo $__env->yieldContent('doc_title', 'DOCUMENTO'); ?>
        </div>

        <?php echo $__env->yieldContent('content'); ?>

        <div class="footer">
            <p><?php echo e($empresa->pie_pagina_factura ?? 'Gracias por preferirnos.'); ?></p>
        </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/layouts/print.blade.php ENDPATH**/ ?>