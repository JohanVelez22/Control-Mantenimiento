<?php $__env->startSection('title', 'Ficha de Producto - ' . ($stock->codigo ?? $stock->producto)); ?>

<?php $__env->startSection('watermark_class', !$stock->active ? 'anulado' : ''); ?>

<?php $__env->startSection('doc_title'); ?>
    FICHA DE CONTROL DE STOCK / INVENTARIO
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="info-grid">
    <div class="info-col">
        <p><strong>Producto:</strong> <?php echo e($stock->producto); ?></p>
        <p><strong>Categoría:</strong> <?php echo e($stock->categoria ?: 'General'); ?> <?php echo e($stock->subcategoria ? ' / ' . $stock->subcategoria : ''); ?></p>
        <p><strong>Proveedor:</strong> <?php echo e($proveedor->nombre_razon_social ?? '—'); ?></p>
    </div>
    <div class="info-col">
        <p><strong>Código / Ref:</strong> <span><?php echo e($stock->codigo ?: '—'); ?></span></p>
        <p><strong>Existencias:</strong> <?php echo e($stock->cantidad); ?> Unidades</p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;"><?php echo e($stock->active ? 'ACTIVO' : 'INACTIVO'); ?></span></p>
    </div>
</div>

<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; background: #fafafa;">
    <strong>Información de Registro:</strong><br>
    Fecha de Registro: <?php echo e($stock->created_at ? \Carbon\Carbon::parse($stock->created_at)->format('d/m/Y h:i A') : '—'); ?> &nbsp;|&nbsp; 
    Identificación Proveedor: <?php echo e($proveedor->identificacion ?? '—'); ?>

</div>

<p class="font-bold mb-4">Estructura de Precios y Costos:</p>
<table class="items-table">
    <thead>
        <tr>
            <th>CONCEPTO / TARIFA</th>
            <th class="text-center" style="width: 25%;">UTILIDAD / MARGEN</th>
            <th class="text-right" style="width: 25%;">VALOR UNITARIO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Costo de Compra (Precio Proveedor)</td>
            <td class="text-center text-gray-500">—</td>
            <td class="text-right">$<?php echo e(number_format($stock->precio_compra, 0, ',', '.')); ?></td>
        </tr>
        <tr>
            <td>Precio Especial a Técnico</td>
            <?php
                $utilidadTecnico = $stock->precio_compra > 0 ? (($stock->precio_tecnico - $stock->precio_compra) / $stock->precio_compra) * 100 : 0;
            ?>
            <td class="text-center" style="font-weight: bold;">+<?php echo e(number_format($utilidadTecnico, 0)); ?>%</td>
            <td class="text-right" style="font-weight: bold;">$<?php echo e(number_format($stock->precio_tecnico, 0, ',', '.')); ?></td>
        </tr>
        <tr>
            <td class="font-bold" style="background-color: #fafafa;">Precio de Venta Público (PVP)</td>
            <td class="text-center font-bold" style="background-color: #fafafa;">+<?php echo e(number_format($stock->utilidad ?? 0, 0)); ?>%</td>
            <td class="text-right font-bold" style="background-color: #fafafa;">$<?php echo e(number_format($stock->precio_venta, 0, ',', '.')); ?></td>
        </tr>
    </tbody>
</table>

<div class="clearfix" style="margin-top: 70px; margin-bottom: 10px;">
    <div style="float: left; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px; font-size: 8.5pt;">
        <strong>Responsable de Inventario</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px; font-size: 8.5pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.print', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/stocks/print.blade.php ENDPATH**/ ?>