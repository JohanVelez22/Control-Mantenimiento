

<?php $__env->startSection('title', 'Factura ' . $electronica->id_orden); ?>

<?php $__env->startSection('watermark_class', $electronica->anulado ? 'anulado' : ''); ?>

<?php $__env->startSection('doc_title', 'ORDEN DE ELECTRÓNICA - ' . $electronica->id_orden); ?>

<?php $__env->startSection('content'); ?>
<div class="info-grid">
    <div class="info-col">
        <p><strong>Cliente:</strong> <?php echo e($electronica->equipo->cliente->nombre ?? 'N/A'); ?></p>
        <p><strong>Teléfono:</strong> <?php echo e($electronica->equipo->cliente->telefono ?? 'N/A'); ?></p>
        <p><strong>Técnico:</strong> <?php echo e($electronica->tecnico->nombre ?? 'N/A'); ?></p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Ingreso:</strong> <?php echo e($electronica->fecha_entrada ? \Carbon\Carbon::parse($electronica->fecha_entrada)->format('d/m/Y') : '—'); ?></p>
        <p><strong>Fecha Emisión:</strong> <?php echo e(now()->format('d/m/Y h:i A')); ?></p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;"><?php echo e($electronica->estado); ?></span></p>
    </div>
</div>

<div style="margin-bottom: 15px;">
    <strong>Detalles del Dispositivo:</strong><br>
    Equipo: <?php echo e($electronica->equipo->nombre ?? 'N/A'); ?> | 
    Marca/Modelo: <?php echo e(trim(($electronica->equipo->marca ?? '') . ' ' . ($electronica->equipo->modelo ?? '')) ?: '—'); ?> | 
    Serie: <?php echo e(Str::upper($electronica->equipo->serie ?? 'N/A')); ?>

</div>

<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; background: #fafafa;">
    <strong>Falla Principal:</strong> <?php echo e(Str::upper($electronica->falla_reportada ?: 'Sin reporte de falla.')); ?><br>
    <strong>Diagnóstico / Problema:</strong> <?php echo e(Str::upper($electronica->descripcion_problema ?: 'Pendiente de diagnóstico.')); ?>

</div>

<?php if($electronica->stocks->count() > 0): ?>
    <p class="font-bold mb-4">Repuestos / Componentes Utilizados:</p>
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 10%;">CANT</th>
                <th>DESCRIPCIÓN</th>
                <th class="text-right" style="width: 20%;">V. UNITARIO</th>
                <th class="text-right" style="width: 20%;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $electronica->stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td class="text-center"><?php echo e($stock->pivot->cantidad); ?></td>
                <td><?php echo e($stock->producto); ?></td>
                <td class="text-right">$<?php echo e(number_format($stock->pivot->precio_unitario, 0, ',', '.')); ?></td>
                <td class="text-right">$<?php echo e(number_format($stock->pivot->cantidad * $stock->pivot->precio_unitario, 0, ',', '.')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="clearfix">
    <table class="totals">
        <tr>
            <td class="lbl">Costo Total Estimado:</td>
            <td class="val">$<?php echo e(number_format($electronica->costo, 0, ',', '.')); ?></td>
        </tr>
        <?php if($electronica->abonos->count() > 0): ?>
            <tr>
                <td class="lbl">Total Abonado:</td>
                <td class="val" style="color: #c00;">- $<?php echo e(number_format($electronica->total_abonado, 0, ',', '.')); ?></td>
            </tr>
            <tr class="grand-total">
                <td class="lbl">SALDO PENDIENTE:</td>
                <td class="val" style="<?php echo e($electronica->saldo_pendiente == 0 ? 'color: green;' : 'color: red;'); ?>">
                    $<?php echo e(number_format($electronica->saldo_pendiente, 0, ',', '.')); ?>

                </td>
            </tr>
        <?php else: ?>
            <tr class="grand-total">
                <td class="lbl">SALDO PENDIENTE:</td>
                <td class="val" style="color: red;">$<?php echo e(number_format($electronica->costo, 0, ',', '.')); ?></td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<?php if($electronica->abonos->count() > 0): ?>
    <div style="margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px;">
        <p style="font-size: 8pt; font-weight: bold; margin-bottom: 5px;">Historial de Pagos:</p>
        <p style="font-size: 8pt; color: #555;">
            <?php $__currentLoopData = $electronica->abonos->sortBy('fecha'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $abono): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                • <?php echo e(\Carbon\Carbon::parse($abono->fecha)->format('d/m/Y')); ?> - <?php echo e(ucfirst($abono->tipo_pago)); ?>: $<?php echo e(number_format($abono->monto, 0, ',', '.')); ?><br>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </p>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.print', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/electronicas/factura.blade.php ENDPATH**/ ?>