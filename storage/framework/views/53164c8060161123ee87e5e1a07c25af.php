
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Entidad *</label>
 <select name="tipo_entidad" required
 class="glass-input no-search w-full">
 <option value="persona" <?php echo e(old('tipo_entidad', $proveedor->tipo_entidad ?? 'persona') === 'persona' ? 'selected' : ''); ?>>👤 Persona Natural</option>
 <option value="empresa" <?php echo e(old('tipo_entidad', $proveedor->tipo_entidad ?? '') === 'empresa' ? 'selected' : ''); ?>>🏢 Empresa</option>
 </select>
 <?php $__errorArgs = ['tipo_entidad'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Identificación (NIT / Cédula) *</label>
 <input type="text" name="identificacion" required
 value="<?php echo e(old('identificacion', $proveedor->identificacion ?? '')); ?>"
 oninput="this.value = this.value.replace(/[^0-9-]/g, '')"
 placeholder="Ej: 900123456-7"
 class="glass-input w-full">
 <?php $__errorArgs = ['identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div class="md:col-span-2">
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre / Razón Social *</label>
 <input type="text" name="nombre_razon_social" required
 value="<?php echo e(old('nombre_razon_social', $proveedor->nombre_razon_social ?? '')); ?>"
 placeholder="Ej: Distribuciones ABC S.A.S."
 class="glass-input w-full">
 <?php $__errorArgs = ['nombre_razon_social'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
 <input type="text" name="telefono"
 value="<?php echo e(old('telefono', $proveedor->telefono ?? '')); ?>"
 oninput="this.value = this.value.replace(/[^0-9]/g, '')"
 placeholder="Ej: 3001234567"
 class="glass-input w-full">
 <?php $__errorArgs = ['telefono'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
 <input type="email" name="email"
 value="<?php echo e(old('email', $proveedor->email ?? '')); ?>"
 placeholder="proveedor@empresa.com"
 class="glass-input w-full">
 <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div class="md:col-span-2">
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Dirección</label>
 <textarea name="direccion" rows="2"
 class="glass-input w-full"
 placeholder="Ej: Calle 45 #12-34, Bogotá"><?php echo e(old('direccion', $proveedor->direccion ?? '')); ?></textarea>
 <?php $__errorArgs = ['direccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div class="md:col-span-2">
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notas / Condiciones comerciales</label>
 <textarea name="notas" rows="2"
 class="glass-input w-full"
 placeholder="Ej: Crédito a 30 días, descuento del 5%..."><?php echo e(old('notas', $proveedor->notas ?? '')); ?></textarea>
 </div>
</div>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/proveedores/_form.blade.php ENDPATH**/ ?>