<?php $__env->startSection('content'); ?>
<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">👨🏻‍💻</span> Usuarios
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona los accesos y credenciales de los colaboradores del sistema</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
  <input type="text" id="search-usuarios" placeholder="🔍 Buscar..." class="search-input bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 text-sm font-semibold transition-all shadow-sm focus:outline-none w-48">
 <?php if(auth()->user()->isAdmin()): ?>
 <a href="<?php echo e(route('usuarios.create')); ?>" class="btn-primary">
 ➕ Nuevo Usuario
 </a>
 <?php endif; ?>
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-usuarios" class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="w-16 text-center">ID</th>
 <th class="w-16 text-center">Foto</th>
 <th>Nombre</th>
 <th>Email</th>
 <th>Rol</th>
 <th>Estado</th>
 <th>Creado</th>
 <th class="text-center w-32">Acciones</th>
 </tr>
 </thead>
 <tbody>
 <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
 <?php $dim = !$u->active ? 'opacity-60 grayscale' : ''; ?>
 <tr>
 <td class="text-center font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($u->id); ?></td>
 <td class="text-center <?php echo e($dim); ?>">
<?php if($u->photo): ?>
  <img src="<?php echo e(asset('storage/' . $u->photo)); ?>" width="40" height="40" class="rounded-xl object-cover mx-auto shadow-sm cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('<?php echo e(asset('storage/' . $u->photo)); ?>', '<?php echo e(addslashes($u->name)); ?>')">
  <?php else: ?>
 <div class="w-10 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-400 mx-auto text-xs font-bold shadow-sm">
 N/A
 </div>
 <?php endif; ?>
 </td>
 <td class="font-bold text-slate-800 dark:text-white <?php echo e($dim); ?>"><?php echo e($u->name); ?></td>
 <td class="<?php echo e($dim); ?>"><?php echo e($u->email); ?></td>
 <td class="<?php echo e($dim); ?>"><span class="capitalize"><?php echo e($u->role); ?></span></td>
 <td>
 <span class="pill <?php echo e($u->active ? 'pill-done' : 'pill-anulado'); ?>">
 <?php echo e($u->active ? 'Activo' : 'Inactivo'); ?>

 </span>
 </td>
 <td class="text-gray-500 <?php echo e($dim); ?>"><?php echo e($u->created_at->format('d/m/Y')); ?></td>
 <td class="text-center <?php echo e($dim); ?>">
 <div class="flex justify-center items-center gap-1 flex-wrap">
 <?php if(auth()->user()->isAdmin() || auth()->id() === $u->id): ?>
 <a href="<?php echo e(route('usuarios.edit', $u->id)); ?>" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600" title="Editar">
 ✏️
 </a>
 <?php else: ?>
 <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed" title="Solo lectura">
 👁️ Lectura
 </span>
 <?php endif; ?>
 
 <?php if(auth()->user()->isAdmin() && auth()->id() !== $u->id): ?>
                            <button type="button" onclick="openAnularModal('<?php echo e(route('usuarios.anular', $u->id)); ?>', <?php echo e(!$u->active ? 'true' : 'false'); ?>)" class="btn-ghost px-2.5 py-1.5 text-xs <?php echo e($u->active ? 'text-red-600' : 'text-emerald-600'); ?>" title="<?php echo e($u->active ? 'Anular Usuario' : 'Reactivar Usuario'); ?>">
 <?php echo e($u->active ? '🚫' : '✅'); ?>

 </button>
 <?php endif; ?>
 </div>
 </td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">👨🏻‍💻</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin otros usuarios</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Actualmente solo existes tú en el sistema. Puedes invitar a más colaboradores.</p>
 <?php if(auth()->user()->isAdmin()): ?>
 <a href="<?php echo e(route('usuarios.create')); ?>" class="btn-primary">
 ➕ Crear Nuevo Usuario
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
 <?php echo e($users->appends(request()->query())->links()); ?>

 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-usuarios', 'tabla-usuarios'));</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/usuarios/index.blade.php ENDPATH**/ ?>