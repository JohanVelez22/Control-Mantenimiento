<?php $__env->startSection('title', 'Factura ' . $factura->numero_factura); ?>

<?php $__env->startSection('watermark_class', $factura->estado === 'anulada' ? 'anulado' : ''); ?>

<?php $__env->startSection('doc_title'); ?>
    FACTURA DE <?php echo e(Str::upper($factura->tipo_movimiento)); ?> - <?php echo e($factura->numero_factura); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="info-grid">
    <div class="info-col">
        <p><strong><?php echo e($factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente'); ?>:</strong> <?php echo e($factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? 'N/A'); ?></p>
        <p><strong>Identificación:</strong> <?php echo e($factura->facturable->nit_documento ?? $factura->facturable->documento ?? 'N/A'); ?></p>
        <p><strong>Teléfono:</strong> <?php echo e($factura->facturable->telefono ?? 'N/A'); ?></p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Emisión:</strong> <?php echo e(\Carbon\Carbon::parse($factura->fecha)->format('d/m/Y')); ?></p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;"><?php echo e(str_replace('_', ' ', $factura->estado)); ?></span></p>
        <p><strong>Vendedor:</strong> <?php echo e($factura->user->name ?? 'Sistema'); ?></p>
    </div>
</div>

<table class="items-table">
    <thead>
        <tr>
            <th class="text-center" style="width: 10%;">CANT</th>
            <th>DESCRIPCIÓN / PRODUCTO</th>
            <th class="text-right" style="width: 20%;">V. UNITARIO</th>
            <th class="text-right" style="width: 20%;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        <?php $__currentLoopData = $factura->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td class="text-center"><?php echo e($item->cantidad); ?></td>
            <td><?php echo e($item->stock->producto ?? 'Producto Desconocido'); ?></td>
            <td class="text-right">$<?php echo e(number_format($item->precio_unitario, 0, ',', '.')); ?></td>
            <td class="text-right">$<?php echo e(number_format($item->cantidad * $item->precio_unitario, 0, ',', '.')); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>

<div class="clearfix">
    <div style="float: left; width: 45%; border: 1px solid #ccc; padding: 10px; background: #fafafa; font-size: 8pt; margin-top: 10px;">
        <strong>Observaciones:</strong><br>
        <?php echo nl2br(e($factura->observaciones ?: 'Sin observaciones.')); ?>

    </div>
    
    <table class="totals">
        <tr>
            <td class="lbl">Total Documento:</td>
            <td class="val">$<?php echo e(number_format($factura->total_documento, 0, ',', '.')); ?></td>
        </tr>
        <tr>
            <td class="lbl">Total Pagado:</td>
            <td class="val" style="color: green;">$<?php echo e(number_format($factura->total_pagado, 0, ',', '.')); ?></td>
        </tr>
        <tr class="grand-total">
            <td class="lbl">SALDO PENDIENTE:</td>
            <td class="val" style="<?php echo e($factura->saldo_pendiente <= 0 ? 'color: green;' : 'color: red;'); ?>">
                $<?php echo e(number_format($factura->saldo_pendiente, 0, ',', '.')); ?>

            </td>
        </tr>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.print', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/inventario/facturas/print.blade.php ENDPATH**/ ?>