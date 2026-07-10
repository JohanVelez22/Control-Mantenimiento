

<?php $__env->startSection('content'); ?>



<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🧾</span> Facturas de Inventario
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Historial de compras y ventas de artículos</p>
 </div>
 <div class="flex flex-wrap gap-3">
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('inventario.compra.create')); ?>" class="btn-compra" style="padding: 9px 18px; font-size: 13px;">
 📦 Nueva Compra
 </a>
 <a href="<?php echo e(route('inventario.venta.create')); ?>" class="btn-venta" style="padding: 9px 18px; font-size: 13px;">
 🛒 Nueva Venta
 </a>
 <?php endif; ?>
 </div>
 </div>

 
  <form method="GET" class="flex flex-wrap items-center gap-3 mb-6 p-5 glass-card no-print relative z-50">
 <select name="tipo" class="glass-input no-search w-48 font-semibold">
 <option value="todos" <?php echo e(request('tipo') === 'todos' || !request('tipo') ? 'selected' : ''); ?>>Todos los tipos</option>
 <option value="compra" <?php echo e(request('tipo') === 'compra' ? 'selected' : ''); ?>>📦 Compras</option>
 <option value="venta" <?php echo e(request('tipo') === 'venta' ? 'selected' : ''); ?>>🛒 Ventas</option>
 </select>
 <select name="estado" class="glass-input no-search w-48 font-semibold">
 <option value="todos" <?php echo e(request('estado') === 'todos' || !request('estado') ? 'selected' : ''); ?>>Todos los estados</option>
 <option value="emitida" <?php echo e(request('estado') === 'emitida' ? 'selected' : ''); ?>>✅ Emitida</option>
 <option value="pendiente_pago" <?php echo e(request('estado') === 'pendiente_pago' ? 'selected' : ''); ?>>⏳ Pendiente</option>
 <option value="anulada" <?php echo e(request('estado') === 'anulada' ? 'selected' : ''); ?>>🚫 Anulada</option>
 </select>
 <input type="text" name="valor_total" value="<?php echo e(request('valor_total')); ?>" placeholder="Valor Total" class="glass-input w-40 font-semibold" oninput="let val = this.value.replace(/\D/g, ''); this.value = val === '' ? '' : parseInt(val, 10).toLocaleString('es-CO');">
 <div class="flex items-center gap-2">
 <input type="date" name="fecha_desde" value="<?php echo e(request('fecha_desde', date('Y-m-01'))); ?>" class="glass-input w-44">
 <span class="text-gray-400 text-sm">a</span>
 <input type="date" name="fecha_hasta" value="<?php echo e(request('fecha_hasta', date('Y-m-d'))); ?>" class="glass-input w-44">
 </div>
 <button type="submit" class="btn-primary py-2 px-4 text-sm">🌪️ Filtrar</button>
 <a href="<?php echo e(route('inventario.facturas')); ?>" class="btn-clean text-sm">🧹 Limpiar</a>
 </form>

 <div class="overflow-x-auto pb-2">
 <table class="ts-table">
 <thead>
 <tr>
 <th class="text-center">Número</th>
 <th class="text-center">Tipo</th>
 <th>Entidad (Cliente/Prov.)</th>
 <th class="text-center">Fecha</th>
 <th class="text-right">Total</th>
 <th class="text-right">Pagado</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php $__empty_1 = true; $__currentLoopData = $facturas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
  <?php
    $dim = $f->estado === 'anulada' ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $dimLight = $f->estado === 'anulada' ? 'opacity-60' : '';
  ?>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
  <td class="text-center font-mono font-bold text-sm text-slate-700 dark:text-slate-300 <?php echo e($dim); ?>"><?php echo e($f->numero_factura); ?></td>
  <td class="text-center <?php echo e($dimLight); ?>">
  <span class="pill <?php echo e($f->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done'); ?>">
  <?php echo e($f->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta'); ?>

  </span>
  </td>
  <td class="<?php echo e($dim); ?>">
  <?php if($f->facturable): ?>
  <?php if(class_basename($f->facturable) === 'Cliente'): ?>
  <a href="<?php echo e(route('clientes.index')); ?>#cliente-<?php echo e($f->facturable->id); ?>" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  👤 <?php echo e($f->facturable->nombre); ?>

  </div>
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  <?php echo e($f->facturable->identificacion ?? 'Cliente'); ?>

  </div>
  </a>
  <?php else: ?>
  <a href="<?php echo e(route('proveedores.index')); ?>#proveedor-<?php echo e($f->facturable->id); ?>" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de proveedores">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
  🏢 <?php echo e($f->facturable->nombre_razon_social); ?>

  </div>
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  <?php echo e($f->facturable->identificacion ?? 'Proveedor'); ?>

  </div>
  </a>
  <?php endif; ?>
  <?php else: ?>
  <span class="text-gray-400 font-bold">—</span>
  <?php endif; ?>
  </td>
  <td class="text-center font-medium <?php echo e($dim); ?>"><?php echo e($f->fecha->format('d/m/Y')); ?></td>
  <td class="text-right font-black text-slate-800 dark:text-white text-base <?php echo e($dim); ?>">
  $<?php echo e(number_format($f->total_documento, 0, ',', '.')); ?>

  </td>
  <td class="text-right <?php echo e($dim); ?>">
  <span class="font-bold text-sm <?php echo e($f->saldo_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'); ?>">
  $<?php echo e(number_format($f->total_pagado, 0, ',', '.')); ?>

  </span>
  <?php if($f->saldo_pendiente > 0 && $f->estado !== 'anulada'): ?>
  <div class="text-[10px] text-red-500 uppercase tracking-tight mt-0.5 font-bold">Saldo: $<?php echo e(number_format($f->saldo_pendiente, 0, ',', '.')); ?></div>
  <?php endif; ?>
  </td>
  <td class="text-center">
  <?php
  $stClass = 'pill-pending';
  if($f->estado === 'emitida') $stClass = 'pill-done';
  if($f->estado === 'anulada') $stClass = 'pill-anulado';
  
  $label = ucfirst(str_replace('_', ' ', $f->estado));
  if($f->estado === 'pendiente_pago') $label = 'Pendiente';
  ?>
  <span class="pill <?php echo e($stClass); ?>">
  <?php echo e($label); ?>

  </span>
  </td>
  <td class="text-center <?php echo e($dim); ?>">
  <div class="flex justify-center md:justify-end gap-1.5 flex-wrap">
  <a href="<?php echo e(route('inventario.facturas.show', $f->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs text-indigo-600" title="Ver Detalles">👁️</a>
  <a href="<?php echo e(route('inventario.facturas.print', $f->id)); ?>" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs" title="Imprimir">🖨️</a>
  
  <?php if(!auth()->user()->isInvitado()): ?>
  <a href="<?php echo e(route('inventario.facturas.edit', $f->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600 border-yellow-500/20 hover:bg-yellow-500/10" title="Editar">✏️</a>
  
  <button type="button" onclick="openAnularModal('<?php echo e(route('inventario.facturas.anular', $f->id)); ?>', <?php echo e($f->estado === 'anulada' ? 'true' : 'false'); ?>)" class="btn-ghost px-2.5 py-1.5 text-xs <?php echo e($f->estado === 'anulada' ? 'text-emerald-600 border-emerald-500/20 hover:bg-emerald-500/10' : 'text-red-600 border-red-500/20 hover:bg-red-500/10'); ?>" title="<?php echo e($f->estado === 'anulada' ? 'Reactivar Factura' : 'Anular Factura'); ?>">
  <?php echo e($f->estado === 'anulada' ? '✅' : '🚫'); ?>

  </button>
  <?php endif; ?>
  </div>
  </td>
  </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🧾</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin facturas registradas</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">No se han realizado compras ni ventas de inventario.</p>
 <?php if(!auth()->user()->isInvitado()): ?>
 <div class="flex gap-3 justify-center">
 <a href="<?php echo e(route('inventario.compra.create')); ?>" class="btn-compra" style="padding: 9px 18px; font-size: 13px;">📦 Comprar</a>
 <a href="<?php echo e(route('inventario.venta.create')); ?>" class="btn-venta" style="padding: 9px 18px; font-size: 13px;">🛒 Vender</a>
 </div>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>
 
 <div class="mt-6 flex justify-end">
 <?php echo e($facturas->appends(request()->query())->links()); ?>

 </div>
</div>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/inventario/facturas/index.blade.php ENDPATH**/ ?>