

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="<?php echo e(route('equipos.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Equipo</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos del dispositivo registrado</p>
 </div>
 </div>
 
 <form method="POST" action="<?php echo e(route('equipos.update', $equipo->id)); ?>" class="space-y-6">
 <?php echo csrf_field(); ?>
 <?php echo method_field('PUT'); ?>
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div class="md:col-span-2">
 <label class="field-label flex items-center gap-2"><span>👤</span> Cliente Propietario *</label>
 <select name="cliente_id" required class="glass-input mt-1 text-sm font-bold">
 <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cliente): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="<?php echo e($cliente->id); ?>" <?php echo e(old('cliente_id', $equipo->cliente_id) == $cliente->id ? 'selected' : ''); ?>>
 <?php echo e($cliente->nombre); ?> (<?php echo e($cliente->identificacion); ?>)
 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 </div>

 <div>
 <label class="field-label">Nombre del Equipo *</label>
 <input type="text" name="nombre" value="<?php echo e(old('nombre', $equipo->nombre)); ?>" required placeholder="Ej. PC Escritorio" class="glass-input mt-1 <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['nombre'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label">Marca *</label>
 <input type="text" name="marca" value="<?php echo e(old('marca', $equipo->marca)); ?>" required placeholder="Ej. HP, Dell" class="glass-input mt-1 <?php $__errorArgs = ['marca'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['marca'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label">Modelo *</label>
 <input type="text" name="modelo" value="<?php echo e(old('modelo', $equipo->modelo)); ?>" required placeholder="Ej. ProDesk 400" class="glass-input mt-1 <?php $__errorArgs = ['modelo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['modelo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label">Número de Serie *</label>
 <input type="text" name="serie" value="<?php echo e(old('serie', $equipo->serie)); ?>" required placeholder="S/N..." oninput="this.value = this.value.toUpperCase()" class="glass-input mt-1 uppercase <?php $__errorArgs = ['serie'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['serie'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 </div>

 <div>
 <label class="field-label flex items-center gap-2"><span>📝</span> Observaciones / Detalles</label>
 <textarea name="observacion" rows="3" class="glass-input mt-1 resize-y" placeholder="Cargador original, rayón en la tapa, etc..."><?php echo e(old('observacion', $equipo->observacion)); ?></textarea>
 </div>



 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="<?php echo e(route('equipos.index')); ?>" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="btn-save">
 🔄 Actualizar Equipo
 </button>
 </div>
 </form>
 </div>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/equipos/edit.blade.php ENDPATH**/ ?>