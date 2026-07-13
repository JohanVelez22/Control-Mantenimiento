

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="<?php echo e(route('tecnicos.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📝 Editar Técnico</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos del técnico registrado</p>
 </div>
 </div>
 
 <form method="POST" action="<?php echo e(route('tecnicos.update', $tecnico->id)); ?>" enctype="multipart/form-data" class="space-y-6">
 <?php echo csrf_field(); ?>
 <?php echo method_field('PUT'); ?>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div>
 <label class="field-label">Nombre Completo *</label>
 <input type="text" name="nombre" value="<?php echo e(old('nombre', $tecnico->nombre)); ?>" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')" class="glass-input mt-1 <?php $__errorArgs = ['nombre'];
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
 <label class="field-label">Identificación (DNI/NIT) *</label>
 <input type="text" name="identificacion" value="<?php echo e(old('identificacion', $tecnico->identificacion)); ?>" required oninput="this.value = this.value.replace(/[^0-9-]/g, '')" class="glass-input mt-1 <?php $__errorArgs = ['identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label">Especialidad *</label>
 <select name="especialidad" required class="glass-input no-search mt-1 text-sm font-bold">
 <option value="Hardware" <?php echo e(old('especialidad', $tecnico->especialidad) == 'Hardware' ? 'selected' : ''); ?>>Hardware</option>
 <option value="Software" <?php echo e(old('especialidad', $tecnico->especialidad) == 'Software' ? 'selected' : ''); ?>>Software</option>
 <option value="Electrónica" <?php echo e(old('especialidad', $tecnico->especialidad) == 'Electrónica' ? 'selected' : ''); ?>>Electrónica</option>
 <option value="Redes" <?php echo e(old('especialidad', $tecnico->especialidad) == 'Redes' ? 'selected' : ''); ?>>Redes</option>
 <option value="General" <?php echo e(old('especialidad', $tecnico->especialidad) == 'General' ? 'selected' : ''); ?>>General</option>
 </select>
 <?php $__errorArgs = ['especialidad'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label">Teléfono Móvil *</label>
 <input type="tel" name="movil" value="<?php echo e(old('movil', $tecnico->movil)); ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="glass-input mt-1 <?php $__errorArgs = ['movil'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['movil'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div class="md:col-span-2">
 <label class="field-label">Email</label>
 <input type="email" name="email" value="<?php echo e(old('email', $tecnico->email)); ?>" class="glass-input mt-1 <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
 <?php $__errorArgs = ['email'];
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
 <label class="field-label">Dirección</label>
 <textarea name="direccion" rows="3" class="glass-input mt-1 resize-y"><?php echo e(old('direccion', $tecnico->direccion)); ?></textarea>
 </div>

<div>
  <label class="field-label">Foto del Técnico</label>
  <?php if($tecnico->photo): ?>
  <div class="mb-3">
  <img src="<?php echo e(asset('storage/' . $tecnico->photo)); ?>" width="100" height="100" class="rounded-xl object-cover border-2 border-gray-300 dark:border-gray-600 shadow-md cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('<?php echo e(asset('storage/' . $tecnico->photo)); ?>', '<?php echo e(addslashes($tecnico->nombre)); ?>', this)">
  </div>
  <?php endif; ?>
  <input type="file" name="photo" accept="image/*" class="glass-input mt-1 <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
  <p class="text-xs text-gray-500 mt-1">Sube una nueva imagen para actualizar la foto.</p>
  <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>
  <?php endif; ?>
  <input type="file" name="photo" accept="image/*" class="glass-input mt-1 <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
  <p class="text-xs text-gray-500 mt-1">Sube una nueva imagen para actualizar la foto.</p>
  <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>



 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="<?php echo e(route('tecnicos.index')); ?>" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="btn-save">
 💾 Actualizar Técnico
 </button>
 </div>
 </form>
 </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/tecnicos/edit.blade.php ENDPATH**/ ?>