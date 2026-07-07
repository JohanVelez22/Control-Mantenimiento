

<?php $__env->startSection('title', 'Comprobante de Caja ' . str_pad($movimiento->id, 5, '0', STR_PAD_LEFT)); ?>

<?php $__env->startSection('watermark_class', $movimiento->anulado ? 'anulado' : ''); ?>

<?php $__env->startSection('doc_title'); ?>
    COMPROBANTE DE <?php echo e(strtoupper($movimiento->tipo_movimiento)); ?> A CAJA - #<?php echo e(str_pad($movimiento->id, 5, '0', STR_PAD_LEFT)); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="info-grid">
    <div class="info-col">
        <p><strong>Persona / Contacto:</strong> <?php echo e($movimiento->persona ?: '—'); ?></p>
        <p><strong>Empresa:</strong> <?php echo e($movimiento->empresa ?: '—'); ?></p>
        <p><strong>Atendido por:</strong> <?php echo e($movimiento->user->name ?? 'Sistema'); ?></p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Transacción:</strong> <?php echo e(\Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y')); ?></p>
        <p><strong>Método de Pago:</strong> <span style="text-transform: uppercase;"><?php echo e($movimiento->tipo_pago); ?></span></p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;"><?php echo e($movimiento->estado); ?></span></p>
    </div>
</div>

<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; background: #f9f9f9; text-align: center;">
    <h3 style="margin: 0 0 10px; font-size: 11pt; text-transform: uppercase;">Concepto del Movimiento</h3>
    <p style="font-size: 12pt; font-weight: bold; margin: 0; color: #222;">
        <?php echo e($movimiento->concepto->nombre ?? 'Concepto Desconocido'); ?>

    </p>
</div>

<div class="clearfix" style="margin-top: 30px;">
    <div style="float: left; width: 45%; border: 1px solid #ccc; padding: 10px; font-size: 8pt; min-height: 80px;">
        <strong>Observaciones / Descripción:</strong><br>
        <?php echo nl2br(e($movimiento->descripcion ?: 'Sin observaciones.')); ?>

    </div>
    
    <table class="totals">
        <?php if($movimiento->monto_total && $movimiento->monto_total > $movimiento->monto): ?>
            <tr>
                <td class="lbl">MONTO TOTAL DEUDA:</td>
                <td class="val">$<?php echo e(number_format($movimiento->monto_total, 0, ',', '.')); ?></td>
            </tr>
            <tr>
                <td class="lbl">PAGADO HOY:</td>
                <td class="val" style="color: green;">$<?php echo e(number_format($movimiento->monto, 0, ',', '.')); ?></td>
            </tr>
            <tr class="grand-total">
                <td class="lbl">SALDO PENDIENTE:</td>
                <td class="val" style="color: red;">$<?php echo e(number_format($movimiento->saldo_pendiente, 0, ',', '.')); ?></td>
            </tr>
        <?php else: ?>
            <tr class="grand-total">
                <td class="lbl">MONTO TOTAL:</td>
                <td class="val" style="font-size: 14pt; <?php echo e($movimiento->tipo_movimiento === 'ingreso' ? 'color: green;' : 'color: red;'); ?>">
                    <?php echo e($movimiento->tipo_movimiento === 'ingreso' ? '+' : '-'); ?> $<?php echo e(number_format($movimiento->monto, 0, ',', '.')); ?>

                </td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<div style="margin-top: 60px; display: flex; justify-content: space-around;">
    <div style="text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px;">
        <strong>Firma Cliente / Recibe</strong>
    </div>
    <div style="text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.print', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/caja/print.blade.php ENDPATH**/ ?>