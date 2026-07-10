<?php $__env->startSection('content'); ?>
<style>
 tr:target {
 background-color: rgba(59, 130, 246, 0.2) !important;
 outline: 2px solid #3b82f6;
 }
</style>

<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">👤</span> Clientes
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona el directorio de tus clientes corporativos y personales</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-clientes" placeholder="Buscar cliente..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('clientes.create')); ?>" class="btn-primary">➕ Nuevo Cliente</a>
 <?php endif; ?>
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-clientes" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th>Nombre</th>
 <th>Identificación</th>
 <th>Móvil</th>
 <th>Email</th>
 <th>Dirección</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-28">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php $__empty_1 = true; $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
 <?php $dim = !$cliente->active ? 'opacity-60 grayscale' : ''; ?>
 <tr id="cliente-<?php echo e($cliente->id); ?>" class="scroll-mt-[6.5rem]">
 <td class="text-center font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($cliente->id); ?></td>
 <td class="font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($cliente->nombre); ?></td>
 <td class="text-gray-600 dark:text-gray-300 <?php echo e($dim); ?>"><?php echo e($cliente->identificacion); ?></td>
 <td class="<?php echo e($dim); ?>"><?php echo e($cliente->movil); ?></td>
 <td class="<?php echo e($dim); ?>"><?php echo e($cliente->email ?? '-'); ?></td>
 <td class="<?php echo e($dim); ?>"><?php echo e($cliente->direccion ?? '-'); ?></td>
 <td class="text-center">
 <span class="pill <?php echo e($cliente->active ? 'pill-done' : 'pill-anulado'); ?>">
 <?php echo e($cliente->active ? 'Activo' : 'Inactivo'); ?>

 </span>
 </td>
 <td class="text-center <?php echo e($dim); ?>">
						<div class="flex justify-center items-center gap-1">
							<?php if(!auth()->user()->isInvitado()): ?>
							<a href="<?php echo e(route('clientes.edit', $cliente->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs" title="Editar">✏️</a>
							<button type="button" onclick="openAnularModal('<?php echo e(route('clientes.anular', $cliente->id)); ?>', <?php echo e(!$cliente->active ? 'true' : 'false'); ?>)" class="btn-ghost px-2.5 py-1.5 text-xs <?php echo e($cliente->active ? 'text-red-600' : 'text-emerald-600'); ?>" title="<?php echo e($cliente->active ? 'Anular Cliente' : 'Reactivar Cliente'); ?>">
								<?php echo e($cliente->active ? '🚫' : '✅'); ?>

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
 <div class="text-6xl drop-shadow-md mb-2">👤</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin clientes registrados</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra a tu primer cliente para comenzar a gestionar sus equipos y mantenimientos.</p>
 <?php if(!auth()->user()->isInvitado()): ?>
 <a href="<?php echo e(route('clientes.create')); ?>" class="btn-primary">➕ Registrar Primer Cliente</a>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
 <?php echo e($clientes->appends(request()->query())->links()); ?>

 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-clientes', 'tabla-clientes'));</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/clientes/index.blade.php ENDPATH**/ ?>