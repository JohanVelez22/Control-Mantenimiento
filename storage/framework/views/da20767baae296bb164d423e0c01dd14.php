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
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🏭</span> Proveedores
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona personas y empresas que te suministran productos</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-proveedores" placeholder="Buscar proveedor..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('proveedores.create')); ?>" class="btn-primary ml-2 ">
 ➕ Nuevo Proveedor
 </a>
 <?php endif; ?>
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-proveedores" class="ts-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th>Tipo</th>
 <th>Identificación</th>
 <th>Nombre / Razón Social</th>
 <th>Teléfono</th>
 <th>Email</th>
 <th class="text-center">Stock Asociado</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php $__empty_1 = true; $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
 <?php $dim = !$p->active ? 'opacity-60 grayscale' : ''; ?>
 <tr id="proveedor-<?php echo e($p->id); ?>">
 <td class="text-center font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($p->id); ?></td>
 <td class="<?php echo e($dim); ?>">
 <span class="pill <?php echo e($p->tipo_entidad === 'empresa' ? 'pill-done' : 'pill-pending'); ?>">
 <?php echo e($p->tipo_entidad === 'empresa' ? '🏢 Empresa' : '👤 Persona'); ?>

 </span>
 </td>
 <td class="font-bold text-sm tracking-tight text-slate-700 dark:text-slate-300 <?php echo e($dim); ?>"><?php echo e($p->identificacion); ?></td>
 <td class="font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($p->nombre_razon_social); ?></td>
 <td class="font-medium <?php echo e($dim); ?>"><?php echo e($p->telefono ?? '—'); ?></td>
 <td class="text-sm font-medium <?php echo e($dim); ?>"><?php echo e($p->email ?? '—'); ?></td>
 <td class="text-center font-black text-blue-600 dark:text-cyan-400 <?php echo e($dim); ?>">
 <?php echo e($p->stocks_count ?? $p->stocks()->count()); ?>

 </td>
 <td class="text-center">
 <span class="pill <?php echo e($p->active ? 'pill-done' : 'pill-anulado'); ?>">
 <?php echo e($p->active ? 'Activo' : 'Inactivo'); ?>

 </span>
 </td>
 <td class="<?php echo e($dim); ?>">
 <div class="flex justify-center gap-2">
 <a href="<?php echo e(route('proveedores.show', $p->id)); ?>" class="btn-ghost px-3 py-1.5 text-xs text-indigo-600" title="Ver Detalles">👁️</a>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('proveedores.edit', $p->id)); ?>" class="btn-ghost px-3 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
                                    <button type="button" onclick="openAnularModal('<?php echo e(route('proveedores.anular', $p->id)); ?>', <?php echo e(!$p->active ? 'true' : 'false'); ?>)" class="btn-ghost px-2.5 py-1.5 text-xs <?php echo e($p->active ? 'text-red-600' : 'text-emerald-600'); ?>" title="<?php echo e($p->active ? 'Anular Proveedor' : 'Reactivar Proveedor'); ?>">
 <?php echo e($p->active ? '🚫' : '✅'); ?>

 </button>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
 <tr>
 <td colspan="9" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🏭</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin proveedores registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Agrega el primer proveedor para gestionar el abastecimiento de inventario.</p>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('proveedores.create')); ?>" class="btn-primary">➕ Agregar Proveedor</a>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>

 <div class="mt-6 flex justify-end">
 <?php echo e($proveedores->appends(request()->query())->links()); ?>

 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-proveedores', 'tabla-proveedores'));</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/proveedores/index.blade.php ENDPATH**/ ?>