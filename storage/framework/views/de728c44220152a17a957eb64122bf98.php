<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">

 
 <?php if($factura->estado === 'pendiente_pago'): ?>
 <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-yellow-500/10 border border-yellow-500/30">
 <div class="flex items-center gap-4">
 <div class="text-3xl">⏳</div>
 <div>
 <h3 class="font-black text-yellow-700 dark:text-yellow-400 uppercase tracking-tight">Pago Pendiente</h3>
 <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
 Saldo por <?php echo e($factura->tipo_movimiento === 'compra' ? 'pagar al proveedor' : 'cobrar al cliente'); ?>.
 </p>
 </div>
 </div>
 <div class="text-center md:text-right">
 <p class="text-[10px] font-bold text-yellow-600/70 uppercase tracking-widest">Saldo Actual</p>
 <p class="text-2xl font-black text-yellow-700 dark:text-yellow-400">$<?php echo e(number_format($factura->saldo_pendiente, 0, ',', '.')); ?></p>
 </div>
 </div>
 <?php endif; ?>
 
 <?php if($factura->estado === 'anulada'): ?>
 <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-center shadow-[0_4px_20px_rgba(239,68,68,0.15)]">
 <span class="text-2xl drop-shadow-md">🚫</span>
 <h3 class="font-black text-red-600 dark:text-red-400 mt-1 uppercase tracking-widest">Factura Anulada</h3>
 <p class="text-sm font-medium text-red-500/80 mt-1">Este documento carece de validez comercial y contable.</p>
 </div>
 <?php endif; ?>

 
 <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6">
  <div class="flex items-center gap-3">
  <a href="<?php echo e(route('inventario.facturas')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
  <div>
  <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
  <?php echo e($factura->numero_factura); ?>

  <span class="pill <?php echo e($factura->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done'); ?> text-sm py-1 px-3">
  <?php echo e($factura->tipo_movimiento === 'compra' ? '📦 COMPRA' : '🛒 VENTA'); ?>

  </span>
  </h2>
  <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-2"><?php echo e($factura->fecha->format('d \d\e F \d\e Y')); ?></p>
  </div>
  </div>
 
 <div class="flex items-center gap-3 shrink-0">
 <a href="<?php echo e(route('inventario.facturas.print', $factura->id)); ?>" target="_blank" class="btn-ghost border-blue-500/20 text-blue-600">
 🖨️ Imprimir
 </a>
 
	<?php if($factura->estado !== 'anulada' && !auth()->user()->isInvitado()): ?>
	<button type="button" onclick="openAnularModal('<?php echo e(route('inventario.facturas.anular', $factura->id)); ?>', false)" class="btn-danger">
		🚫 Anular
	</button>
	<?php endif; ?>
 </div>
 </div>

 
 <div class="mb-8 p-5 rounded-2xl bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md flex items-start gap-4 shadow-sm">
 <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-xl shadow-lg shrink-0">
 <?php echo e($factura->tipo_movimiento === 'compra' ? '🏭' : '👤'); ?>

 </div>
 <div>
 <p class="text-[10px] font-black text-indigo-500 tracking-widest uppercase mb-1"><?php echo e($factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente'); ?></p>
 <p class="font-black text-xl text-slate-800 dark:text-white leading-tight">
 <?php echo e($factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? '—'); ?>

 </p>
 <p class="text-sm font-semibold text-gray-500 mt-1">
 ID: <?php echo e($factura->facturable->identificacion ?? 'N/A'); ?> 
 <?php if(isset($factura->facturable->email)): ?> <span class="mx-2">•</span> Correo: <?php echo e($factura->facturable->email); ?> <?php endif; ?>
 </p>
 </div>
 </div>

 
 <div class="mb-8">
 <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Detalle del Documento</h3>
 <div class="overflow-x-auto pb-2">
 <table class="ts-table">
 <thead>
 <tr>
 <th>Producto</th>
 <th class="text-center w-24">Cant.</th>
 <th class="text-right w-36">Precio Unitario</th>
 <th class="text-right w-36">Subtotal</th>
 </tr>
 </thead>
 <tbody>
 <?php $__currentLoopData = $factura->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <tr>
 <td class="font-bold text-slate-800 dark:text-white"><?php echo e($item->stock->producto); ?></td>
 <td class="text-center font-bold"><?php echo e($item->cantidad); ?></td>
 <td class="text-right font-mono">$<?php echo e(number_format($item->precio_unitario, 0, ',', '.')); ?></td>
 <td class="text-right font-black text-blue-600 dark:text-cyan-400">$<?php echo e(number_format($item->subtotal, 0, ',', '.')); ?></td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </tbody>
 </table>
 </div>
 </div>
 
 
 <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-6">
 
 <div class="w-full md:w-1/2">
 <?php if($factura->observaciones): ?>
  <div class="p-5 bg-white/10 dark:bg-slate-900/25 border border-white/40 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Observaciones</p>
 <p class="text-sm font-medium text-slate-700 dark:text-slate-300"><?php echo e($factura->observaciones); ?></p>
 </div>
 <?php endif; ?>
 </div>
 
 
 <div class="w-full md:w-1/2 bg-white/50 dark:bg-slate-800/50 rounded-2xl p-5 border border-gray-200/50 dark:border-white/5 backdrop-blur-md">
 <div class="flex justify-between items-center mb-3">
 <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Documento</span>
 <span class="text-2xl font-black text-slate-800 dark:text-white">$<?php echo e(number_format($factura->total_documento, 0, ',', '.')); ?></span>
 </div>
 
 <div class="flex justify-between items-center py-2 border-t border-gray-200/50 dark:border-white/10">
 <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Total Pagado</span>
 <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">$<?php echo e(number_format($factura->total_pagado, 0, ',', '.')); ?></span>
 </div>
 
 <?php if($factura->saldo_pendiente > 0): ?>
 <div class="flex justify-between items-center py-2 border-t border-gray-200/50 dark:border-white/10">
 <span class="text-sm font-bold text-red-500">Saldo Pendiente</span>
 <span class="text-lg font-black text-red-500">$<?php echo e(number_format($factura->saldo_pendiente, 0, ',', '.')); ?></span>
 </div>
 <?php endif; ?>
 </div>
 </div>

 <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
 <span>Usuario: <?php echo e($factura->user->name ?? '—'); ?></span>
 <span>Registro: <?php echo e($factura->created_at->format('d/m/Y H:i:s')); ?></span>
 </div>
 </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/inventario/facturas/show.blade.php ENDPATH**/ ?>