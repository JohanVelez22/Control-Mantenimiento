<?php $__env->startSection('content'); ?>
<style>
  tr.active-target td {
    background-color: rgba(59, 130, 246, 0.08) !important;
    border-top: 1.5px solid rgba(59, 130, 246, 0.3) !important;
    border-bottom: 1.5px solid rgba(59, 130, 246, 0.3) !important;
  }
  tr.active-target td:first-child {
    border-left: 4px solid #3b82f6 !important;
  }
  .dark tr.active-target td {
    background-color: rgba(59, 130, 246, 0.15) !important;
    border-top: 1.5px solid rgba(96, 165, 250, 0.4) !important;
    border-bottom: 1.5px solid rgba(96, 165, 250, 0.4) !important;
  }
  .dark tr.active-target td:first-child {
    border-left: 4px solid #60a5fa !important;
  }
</style>
<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">📦</span> Inventario (Stock)
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Control de repuestos y productos</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-stocks" placeholder="Buscar producto, cod..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 <?php if(!auth()->user()->isInvitado()): ?>
 <div class="flex gap-3 ml-2">
 <a href="<?php echo e(route('stocks.categorias.index')); ?>" class="btn-concepts flex items-center gap-2" style="padding: 9px 18px; font-size: 13px;">
 🏷️ <span class="hidden sm:inline">Gestionar Categorías</span>
 </a>
 <a href="<?php echo e(route('stocks.create')); ?>" class="btn-primary flex items-center gap-2 shadow-lg shadow-indigo-500/30" style="padding: 9px 18px; font-size: 13px;">
 <span>➕</span> <span class="hidden sm:inline">Nuevo Producto</span>
 </a>
 </div>
 <?php endif; ?>
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-stocks" class="ts-table responsive-table w-full">
 <thead>
 <tr>
 <th>Cód.</th>
 <th>Producto</th>
 <th class="text-center">Cant.</th>
 <th class="text-right">P. Compra</th>
 <th class="text-center">Utilidad</th>
 <th class="text-right">P. Venta</th>
 <th class="text-right">P. Técnico</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
 <?php $dim = !$stock->active ? 'opacity-60 grayscale' : ''; ?>
 <tr id="stock-<?php echo e($stock->id); ?>">
 <td data-label="Código:" class="text-sm font-bold text-slate-500 dark:text-slate-400 <?php echo e($dim); ?>">
 <?php echo e($stock->codigo ?? '-'); ?>

 </td>
 <td data-label="Producto:" class="<?php echo e($dim); ?>">
 <div class="font-bold text-slate-800 dark:text-white leading-tight">
 <?php echo e($stock->producto); ?>

 </div>
 <?php if($stock->categoria || $stock->subcategoria): ?>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-1">
 <?php echo e($stock->categoria ?? 'Sin Categoría'); ?> <?php echo e($stock->subcategoria ? ' / ' . $stock->subcategoria : ''); ?>

 </div>
 <?php endif; ?>
 </td>
 <td data-label="Cantidad:" class="text-center <?php echo e($dim); ?>">
 <span class="pill <?php echo e($stock->cantidad > 5 ? 'pill-done' : 'pill-anulado'); ?>">
 <?php echo e($stock->cantidad); ?>

 </span>
 </td>
 <td data-label="P. Compra:" class="text-right font-medium <?php echo e($dim); ?>">
 $<?php echo e(number_format($stock->precio_compra, 0, ',', '.')); ?>

 </td>
 <?php
 $utilidadPesos = $stock->precio_venta - $stock->precio_compra;
 $utilidadPct = $stock->utilidad ?? 0;
 ?>
 <td data-label="Utilidad:" class="text-center <?php echo e($dim); ?>">
 <div class="flex flex-col items-center gap-0.5 justify-end md:justify-center w-full" title="Margen sobre precio de compra">
 <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
 💹 +<?php echo e(number_format($utilidadPct, 0)); ?>%
 </span>
 <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
 +$<?php echo e(number_format($utilidadPesos, 0, ',', '.')); ?>

 </span>
 </div>
 </td>
 <td data-label="P. Venta:" class="text-right font-black text-blue-600 dark:text-cyan-400 text-base <?php echo e($dim); ?>">
 $<?php echo e(number_format($stock->precio_venta, 0, ',', '.')); ?>

 </td>
 <td data-label="P. Técnico:" class="text-right font-bold text-purple-600 dark:text-purple-400 <?php echo e($dim); ?>">
 $<?php echo e(number_format($stock->precio_tecnico, 0, ',', '.')); ?>

 </td>
 <td data-label="Estado:" class="text-center">
 <span class="pill <?php echo e($stock->active ? 'pill-done' : 'pill-anulado'); ?>">
 <?php echo e($stock->active ? 'Activo' : 'Inactivo'); ?>

 </span>
 </td>
 <td data-label="Acciones:" class="text-center <?php echo e($dim); ?>">
 <div class="flex justify-end md:justify-center gap-2">
 <a href="<?php echo e(route('stocks.show', $stock->id)); ?>" class="btn-ghost px-3 py-1.5 text-xs text-blue-600" title="Ver Detalles">👁️</a>
 <a href="<?php echo e(route('stocks.print', $stock->id)); ?>" target="_blank" class="btn-ghost px-3 py-1.5 text-xs text-gray-600" title="Imprimir">🖨️</a>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('stocks.edit', $stock->id)); ?>" class="btn-ghost px-3 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
                            <button type="button" onclick="openAnularModal('<?php echo e(route('stocks.anular', $stock->id)); ?>', <?php echo e(!$stock->active ? 'true' : 'false'); ?>)" class="btn-ghost px-3 py-1.5 text-xs <?php echo e($stock->active ? 'text-red-600' : 'text-emerald-600'); ?>" title="<?php echo e($stock->active ? 'Anular Producto' : 'Reactivar Producto'); ?>">
 <?php echo e($stock->active ? '🚫' : '✅'); ?>

 </button>
 <?php else: ?>
 <span class="text-gray-400 text-sm">👁️ Lectura</span>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
 <tr>
 <td colspan="9" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">📦</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Inventario Vacío</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra tu primer repuesto o producto en el stock.</p>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('stocks.create')); ?>" class="btn-primary">➕ Agregar Producto</a>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>

 <div class="mt-6 flex justify-end">
 <?php echo e($stocks->appends(request()->query())->links()); ?>

 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-stocks', 'tabla-stocks'));</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/stocks/index.blade.php ENDPATH**/ ?>