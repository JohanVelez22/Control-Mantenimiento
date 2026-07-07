<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="<?php echo e(route('caja.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Movimiento: #<?php echo e($movimiento->id); ?></h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Modifica los datos del registro de caja</p>
 </div>
 </div>
 <form action="<?php echo e(route('caja.update', $movimiento->id)); ?>" method="POST" class="space-y-6">
 <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
 <?php echo $__env->make('caja._form', ['movimiento' => $movimiento], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
 
 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="<?php echo e(route('caja.index')); ?>" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">
 🔄 Actualizar Movimiento
 </button>
 </div>
 </form>
 </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/caja/edit.blade.php ENDPATH**/ ?>