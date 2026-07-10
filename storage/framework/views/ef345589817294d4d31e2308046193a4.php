<?php $__env->startSection('content'); ?>
<style>
  /* Fila resaltada al llegar por ancla (#equipo-id) */
  tr:target {
  background-color: rgba(59, 130, 246, 0.2) !important;
  outline: 2px solid #3b82f6;
  }
</style>

<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🖥️</span> Equipos
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Administra y vincula los equipos a tus clientes</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-equipos" placeholder="Buscar equipo..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('equipos.create')); ?>" class="btn-primary">
 ➕ Nuevo Equipo
 </a>
 <?php endif; ?>
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-equipos" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th>Equipo</th>
 <th>Serie</th>
 <th>Cliente</th>
 <th>Observación</th>
 <th>Registrado por</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-28">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php $__empty_1 = true; $__currentLoopData = $equipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $equipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
 <?php $dim = !$equipo->active ? 'opacity-60 grayscale' : ''; ?>
 <tr id="equipo-<?php echo e($equipo->id); ?>" class="scroll-mt-[6.5rem]">
 <td class="text-center font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($equipo->id); ?></td>
 <td class="<?php echo e($dim); ?>">
 <div class="font-bold text-slate-800 dark:text-white leading-tight"><?php echo e($equipo->nombre); ?></div>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5"><?php echo e($equipo->marca); ?> <?php echo e($equipo->modelo); ?></div>
 </td>
 <td class="uppercase text-gray-600 dark:text-gray-300 <?php echo e($dim); ?>"><?php echo e($equipo->serie); ?></td>
 <td class="font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($equipo->cliente->nombre ?? '-'); ?></td>
 <td class="<?php echo e($dim); ?>"><p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" title="<?php echo e($equipo->observacion); ?>"><?php echo e($equipo->observacion ?? '-'); ?></p></td>
 <td class="<?php echo e($dim); ?>"><span class="font-medium text-slate-700 dark:text-slate-300"><?php echo e($equipo->user->name ?? '-'); ?></span></td>
 <td class="text-center">
 <span class="pill <?php echo e($equipo->active ? 'pill-done' : 'pill-anulado'); ?>">
 <?php echo e($equipo->active ? 'Activo' : 'Inactivo'); ?>

 </span>
 </td>
 <td class="text-center <?php echo e($dim); ?>">
 <div class="flex justify-center items-center gap-1">
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('equipos.edit', $equipo->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
                            <button type="button" onclick="openAnularModal('<?php echo e(route('equipos.anular', $equipo->id)); ?>', <?php echo e(!$equipo->active ? 'true' : 'false'); ?>)" class="btn-ghost px-2.5 py-1.5 text-xs <?php echo e($equipo->active ? 'text-red-600' : 'text-emerald-600'); ?>" title="<?php echo e($equipo->active ? 'Anular Equipo' : 'Reactivar Equipo'); ?>">
 <?php echo e($equipo->active ? '🚫' : '✅'); ?>

 </button>
 <?php else: ?>
 <span class="text-gray-400 text-sm">👁️ Lectura</span>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🖥️</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin equipos registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Comienza vinculando un equipo a un cliente para iniciar el seguimiento.</p>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('equipos.create')); ?>" class="btn-primary">➕ Registrar Primer Equipo</a>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
 <?php echo e($equipos->appends(request()->query())->links()); ?>

 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-equipos', 'tabla-equipos'));</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/equipos/index.blade.php ENDPATH**/ ?>