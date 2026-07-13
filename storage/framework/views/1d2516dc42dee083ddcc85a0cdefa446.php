

<?php $__env->startSection('content'); ?>
<style>
/* Altura uniforme de 42px para todos los inputs y selectores (incluyendo TomSelect) */
select.glass-input,
.ts-wrapper.glass-input .ts-control,
select.glass-input + .ts-wrapper .ts-control,
input[type="number"].glass-input,
input[type="text"].glass-input {
  height: 42px !important;
  font-size: 14px !important;
}
/* Panel de precios: siempre visible, nunca animado, nunca transparente */
/* NOTA: sin transform:none para respetar -translate-y-1/2 de Tailwind */
.pricing-panel,
.pricing-panel * {
  opacity: 1 !important;
  visibility: visible !important;
  animation: none !important;
}
</style>

<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="<?php echo e(route('stocks.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📦 Agregar Producto al Stock</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Registra nuevos repuestos y artículos en el inventario</p>
 </div>
 </div>

 <form action="<?php echo e(route('stocks.store')); ?>" method="POST" class="space-y-6">
 <?php echo csrf_field(); ?>

 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div>
 <label for="codigo" class="field-label">Código (Opcional)</label>
 <input type="text" name="codigo" id="codigo" value="<?php echo e(old('codigo', $stock->codigo ?? '')); ?>"
  oninput="this.value = this.value.toUpperCase()" class="glass-input" placeholder="Ej: REF-001">
 <?php $__errorArgs = ['codigo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label for="producto" class="field-label">Nombre del Producto *</label>
 <input type="text" name="producto" id="producto" value="<?php echo e(old('producto', $stock->producto ?? '')); ?>"
  required class="glass-input" placeholder="Ej: Disco Duro SSD 1TB">
 <?php $__errorArgs = ['producto'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label for="categoria" class="field-label">Categoría *</label>
 <?php
     $categorias = \App\Models\CategoriaStock::where('tipo', 'categoria')->pluck('nombre');
     $subcategorias = \App\Models\CategoriaStock::where('tipo', 'subcategoria')->pluck('nombre');
 ?>
 <select name="categoria" id="categoria" required class="glass-input no-search">
    <option value="">Seleccione una categoría...</option>
    <?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($cat); ?>" <?php echo e(old('categoria', $stock->categoria ?? '') == $cat ? 'selected' : ''); ?>><?php echo e($cat); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 <?php $__errorArgs = ['categoria'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label for="subcategoria" class="field-label">Subcategoría *</label>
 <select name="subcategoria" id="subcategoria" required class="glass-input no-search">
    <option value="">Seleccione una subcategoría...</option>
    <?php $__currentLoopData = $subcategorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subcat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($subcat); ?>" <?php echo e(old('subcategoria', $stock->subcategoria ?? '') == $subcat ? 'selected' : ''); ?>><?php echo e($subcat); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 <?php $__errorArgs = ['subcategoria'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label for="cantidad" class="field-label">Cantidad Inicial *</label>
 <input type="number" name="cantidad" id="cantidad"
  value="<?php echo e(old('cantidad', $stock->cantidad ?? 0)); ?>"
  required min="0" class="glass-input font-bold dark:[color-scheme:dark]">
 <?php $__errorArgs = ['cantidad'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label for="proveedor_id" class="field-label">Proveedor *</label>
 <select name="proveedor_id" id="proveedor_id" required class="glass-input no-search">
 <option value="">Seleccione un proveedor...</option>
<?php $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $proveedor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <option value="<?php echo e($proveedor->id); ?>" <?php echo e(old('proveedor_id', $stock->proveedor_id ?? '') == $proveedor->id ? 'selected' : ''); ?>>
  <?php echo e($proveedor->nombre_razon_social); ?> (<?php echo e($proveedor->identificacion); ?>)
  </option>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
  <?php $__errorArgs = ['proveedor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
  </div>
  </div>

  
  <div class="md:col-span-2">
  <label class="field-label">Foto del Producto</label>
  <input type="file" name="photo" accept="image/*" class="glass-input">
  </div>

  
 <div class="pricing-panel p-5 bg-white/45 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl shadow-sm">
 <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
 <div>
 <label for="precio_compra_visual" class="field-label">P. Compra ($) *</label>
 <input type="text" id="precio_compra_visual"
  value="<?php echo e(old('precio_compra', isset($stock) && $stock->precio_compra ? number_format($stock->precio_compra, 0, '', '') : '')); ?>"
  required class="glass-input text-right font-bold text-slate-800 dark:text-white" placeholder="0">
 <input type="hidden" name="precio_compra" id="precio_compra_real"
  value="<?php echo e(old('precio_compra', isset($stock) ? intval($stock->precio_compra) : '')); ?>">
 <?php $__errorArgs = ['precio_compra'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 <div>
 <label for="utilidad" class="field-label">Utilidad (%) *</label>
 <div class="glass-input flex items-center justify-end pr-3">
  <input type="number" step="1" name="utilidad" id="utilidad"
   value="<?php echo e(old('utilidad', isset($stock) && $stock->utilidad !== null ? (int)$stock->utilidad : 30)); ?>" required min="0"
   class="w-12 bg-transparent border-none outline-none focus:ring-0 text-left pl-1 font-bold text-slate-800 dark:text-white dark:[color-scheme:dark] p-0">
  <span class="text-emerald-600 dark:text-emerald-400 font-bold text-sm ml-1">%</span>
 </div>
 <?php $__errorArgs = ['utilidad'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 <div>
 <label for="precio_venta_visual" class="field-label">P. Venta (Manual)</label>
 <input type="text" id="precio_venta_visual"
  value="<?php echo e(old('precio_venta', isset($stock) && $stock->precio_venta ? number_format($stock->precio_venta, 0, '', '') : '')); ?>"
  placeholder="Automatico" class="glass-input text-right font-bold text-blue-600 dark:text-cyan-400">
 <input type="hidden" name="precio_venta" id="precio_venta_real"
  value="<?php echo e(old('precio_venta', isset($stock) && $stock->precio_venta ? intval($stock->precio_venta) : '')); ?>">
 <?php $__errorArgs = ['precio_venta'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 <div>
 <label for="precio_tecnico_visual" class="field-label">P. Técnico (Manual)</label>
 <input type="text" id="precio_tecnico_visual"
  value="<?php echo e(old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? number_format($stock->precio_tecnico, 0, '', '') : '')); ?>"
  placeholder="Automatico" class="glass-input text-right font-bold text-purple-600 dark:text-purple-400">
 <input type="hidden" name="precio_tecnico" id="precio_tecnico_real"
  value="<?php echo e(old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? intval($stock->precio_tecnico) : '')); ?>">
 <?php $__errorArgs = ['precio_tecnico'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 </div>
 <p class="text-[10px] text-gray-500 mt-3 font-medium">Si dejas P. Venta y P. Técnico vacíos, se calculan automáticamente: Venta = Compra × (1 + Utilidad%), Técnico = Compra × (1 + Utilidad%/2).</p>
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="<?php echo e(route('stocks.index')); ?>" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">💾 Guardar Producto</button>
 </div>
 </form>
 </div>
</div>

<script>
(function () {
 function formatInput(visualId, realId) {
  var vis = document.getElementById(visualId);
  var real = document.getElementById(realId);
  if (!vis || !real) return;
  if (real.value && real.value !== '0' && real.value !== '') {
   vis.value = new Intl.NumberFormat('es-CO').format(parseInt(real.value, 10));
  }
  vis.addEventListener('input', function (e) {
   var raw = e.target.value.replace(/\D/g, '');
   real.value = raw;
   e.target.value = raw ? new Intl.NumberFormat('es-CO').format(parseInt(raw, 10)) : '';
  });
 }
 formatInput('precio_compra_visual', 'precio_compra_real');
 formatInput('precio_venta_visual', 'precio_venta_real');
 formatInput('precio_tecnico_visual', 'precio_tecnico_real');
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/stocks/create.blade.php ENDPATH**/ ?>