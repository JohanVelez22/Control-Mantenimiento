<?php $__env->startSection('content'); ?>
<style>
  /* Fila resaltada al llegar por ancla (#electronica-id) */
  tr.active-target td {
    background-color: rgba(168, 85, 247, 0.08) !important;
    border-top: 1.5px solid rgba(168, 85, 247, 0.3) !important;
    border-bottom: 1.5px solid rgba(168, 85, 247, 0.3) !important;
  }
  tr.active-target td:first-child {
    border-left: 4px solid #a855f7 !important;
  }
  .dark tr.active-target td {
    background-color: rgba(168, 85, 247, 0.15) !important;
    border-top: 1.5px solid rgba(192, 132, 252, 0.4) !important;
    border-bottom: 1.5px solid rgba(192, 132, 252, 0.4) !important;
  }
  .dark tr.active-target td:first-child {
    border-left: 4px solid #c084fc !important;
  }
</style>

<div class="glass-card p-6">

  <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
  <div>
  <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
  <span class="text-3xl">⚡</span> Registros de Electrónica
  </h2>
  <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona las reparaciones de electrónica de consumo</p>
  </div>
  <div class="flex flex-wrap items-center gap-3">
   <div class="relative">
   <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
   <input type="text" id="search-electronicas" placeholder="Buscar cliente, dispositivo..." class="glass-input pl-9 w-48 sm:w-64">
   </div>
  <?php if(!auth()->user()->isInvitado()): ?>
  <a href="<?php echo e(route('electronicas.create')); ?>" class="btn-primary text-sm">
  ➕ Nuevo Registro
  </a>
  <?php endif; ?>
  </div>
  </div>

  <div class="overflow-x-auto pb-2">
  <table id="tabla-electronicas" class="ts-table table-electronica responsive-table w-full">
  <thead>
  <tr>
  <th class="w-20 text-center">Orden</th>
  <th class="text-center">Equipo</th>
  <th class="text-center">Cliente</th>
  <th class="text-center">Técnico</th>
  <th class="text-center">Tipo</th>
  <th class="text-center">Observación</th>
  <th class="text-center">Costo</th>
  <th class="text-center">Progreso</th>
  <th class="text-center">Estado</th>
  <th class="text-center w-24">Entrada</th>
  <th class="text-center w-24">Salida</th>
  <th class="text-center w-32">Acciones</th>
  </tr>
  </thead>
  <tbody>
  <?php $__empty_1 = true; $__currentLoopData = $electronicas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
  <?php 
    $dim = $e->anulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $dimLight = $e->anulado ? 'opacity-60' : '';
  ?>
  <tr id="electronica-<?php echo e($e->id); ?>" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
  <td data-label="Orden:" class="font-bold text-center whitespace-nowrap <?php echo e($dim); ?>">
  <a href="#electronica-<?php echo e($e->id); ?>" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 hover:underline transition-colors">
  <?php echo e($e->id_orden); ?>

  </a>
  </td>
  
  <td data-label="Equipo:" class="<?php echo e($dim); ?>">
  <a href="<?php echo e(route('equipos.index')); ?>#equipo-<?php echo e($e->equipo_id); ?>" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
  <?php echo e($e->equipo->nombre ?? '-'); ?>

  </div>
  <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  <?php echo e($e->equipo->marca ?? ''); ?> <?php echo e($e->equipo->modelo ?? ''); ?>

  </div>
  <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase">
  SN: <?php echo e($e->equipo->serie ?? 'N/A'); ?>

  </div>
  </a>
  </td>
  
  <td data-label="Cliente:" class="<?php echo e($dim); ?>">
  <a href="<?php echo e(route('clientes.index')); ?>#cliente-<?php echo e($e->equipo->cliente_id ?? ''); ?>" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
  <?php echo e($e->equipo->cliente->nombre ?? 'N/A'); ?>

  </div>
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  <?php echo e($e->equipo->cliente->identificacion ?? 'N/A'); ?>

  </div>
  </a>
  </td>
  
  <td data-label="Técnico:" class="text-center <?php echo e($dim); ?>">
  <span class="font-medium text-slate-700 dark:text-slate-300"><?php echo e($e->tecnico->nombre ?? 'N/A'); ?></span>
  </td>
  
  <td data-label="Tipo:" class="text-center <?php echo e($dimLight); ?>">
  <span class="pill <?php echo e($e->tipo === 'correctivo' ? 'pill-correctivo' : 'pill-preventivo'); ?> <?php echo e($e->anulado ? 'opacity-70' : ''); ?>">
  <?php echo e(ucfirst($e->tipo)); ?>

  </span>
  </td>
  
  <td data-label="Observación:" class="max-w-[250px] <?php echo e($dim); ?>">
  <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-normal break-words leading-relaxed font-medium">
  <?php echo e($e->descripcion_problema); ?>

  </p>
  </td>
 
  <td data-label="Costo:" class="text-right font-black text-purple-600 dark:text-purple-400 <?php echo e($dim); ?>">
  $<?php echo e(number_format($e->costo, 0, ',', '.')); ?>

  </td>
  
  <td data-label="Estado:" class="text-center <?php echo e($dimLight); ?>">
  <?php
  $estadoIcon = '⏳';
  if($e->estado === 'terminado') $estadoIcon = '✅';
  ?>
  <span class="pill <?php echo e($e->estado === 'terminado' ? 'pill-done' : 'pill-pending'); ?> <?php echo e($e->anulado ? 'opacity-70' : ''); ?>">
  <?php echo e($estadoIcon); ?> <?php echo e(ucfirst($e->estado)); ?>

  </span>
  </td>
  
  <td data-label="Estado:" class="text-center">
  <span class="pill <?php echo e($e->anulado ? 'pill-anulado' : 'pill-done'); ?>">
  <?php echo e($e->anulado ? 'ANULADO' : 'ACTIVO'); ?>

  </span>
  </td>
  
  <td data-label="Entrada:" class="text-center text-slate-800 dark:text-slate-200 <?php echo e($dim); ?>">
  <?php echo e(\Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y')); ?>

  <?php 
  $fechaEntrada = \Carbon\Carbon::parse($e->fecha_entrada)->startOfDay();
  $fechaFin = $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->startOfDay() : \Carbon\Carbon::now()->startOfDay();
  $dias = $fechaEntrada->diffInDays($fechaFin);
  ?>
  <div class="mt-1 text-xs font-bold <?php echo e($dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400')); ?>">
  (<?php echo e($dias); ?> d)
  </div>
  </td>
  
  <td data-label="Salida:" class="text-center <?php echo e($e->fecha_salida ? 'text-slate-800 dark:text-white' : 'text-gray-400 italic'); ?> <?php echo e($dim); ?>">
  <?php echo e($e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : '-'); ?>

  </td>
  
  <td data-label="Acciones:" class="text-center <?php echo e($dim); ?>">
  <div class="flex justify-end md:justify-center gap-1.5 flex-wrap">
  <a href="<?php echo e(route('electronicas.show', $e->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs text-indigo-600" title="Ver detalle">👁️</a>
 
  <?php if($e->estado === 'terminado' && $e->fecha_salida): ?>
  <a href="<?php echo e(route('electronicas.factura', $e->id)); ?>" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 hover:text-green-700 hover:bg-green-50/50" title="Imprimir Factura">🖨️</a>
  <?php elseif($e->estado === 'terminado'): ?>
  <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed" title="Requiere fecha de salida para facturar">🖨️</span>
  <?php endif; ?>
  
  <?php if(!auth()->user()->isInvitado()): ?>
  <a href="<?php echo e(route('electronicas.edit', $e->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
  
  <button type="button" onclick="openAnularModal('<?php echo e(route('electronicas.anular', $e->id)); ?>', <?php echo e($e->anulado ? 'true' : 'false'); ?>)" class="btn-ghost px-2.5 py-1.5 text-xs <?php echo e($e->anulado ? 'text-emerald-600 border-emerald-500/20 hover:bg-emerald-500/10' : 'text-red-600 border-red-500/20 hover:bg-red-500/10'); ?>" title="<?php echo e($e->anulado ? 'Reactivar orden' : 'Anular orden'); ?>">
  <?php echo e($e->anulado ? '✅' : '🚫'); ?>

  </button>
  <?php endif; ?>
  </div>
  </td>
  </tr>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
  <tr>
  <td colspan="12" class="p-16 text-center">
  <div class="flex flex-col items-center justify-center gap-3">
  <div class="text-6xl drop-shadow-md mb-2">⚡</div>
  <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin registros de electrónica</h3>
  <p class="text-gray-500 font-medium max-w-sm mb-4">Registra la primera reparación electrónica para iniciar el control.</p>
  <?php if(!auth()->user()->isInvitado()): ?>
  <a href="<?php echo e(route('electronicas.create')); ?>" class="btn-primary ">
  ➕ Nuevo Registro
  </a>
  <?php endif; ?>
  </div>
  </td>
  </tr>
  <?php endif; ?>
  </tbody>
  </table>
  </div>

  <div class="mt-6 flex justify-end">
  <?php echo e($electronicas->appends(request()->query())->links()); ?>

  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', () => {
  if(typeof filterTable === 'function') {
  filterTable('search-electronicas', 'tabla-electronicas');
  }
  });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/electronicas/index.blade.php ENDPATH**/ ?>