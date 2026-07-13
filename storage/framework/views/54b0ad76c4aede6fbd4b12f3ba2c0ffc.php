<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="<?php echo e(route('mantenimientos.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Mantenimiento: <?php echo e($mantenimiento->id_orden); ?></h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos de la orden de servicio</p>
 </div>
 </div>

 <form method="POST" action="<?php echo e(route('mantenimientos.update', $mantenimiento->id)); ?>" id="mantenimientoForm" class="space-y-6">
 <?php echo csrf_field(); ?>
 <?php echo method_field('PUT'); ?>
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 
 <div class="md:col-span-2">
 <label class="field-label">ID Orden</label>
 <input type="text" name="id_orden" value="<?php echo e($mantenimiento->id_orden); ?>" class="glass-input bg-gray-100 dark:bg-gray-800 text-gray-500 cursor-not-allowed font-bold" readonly>
 </div>

 
 <div class="md:col-span-2 min-w-0 p-4 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
 <label class="field-label flex items-center gap-2"><span>💻</span> Seleccionar Equipo *</label>
 <select name="equipo_id" required class="glass-input text-sm font-bold mt-1">
 <?php $__currentLoopData = $equipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $equipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="<?php echo e($equipo->id); ?>" <?php echo e((old('equipo_id', $mantenimiento->equipo_id) == $equipo->id) ? 'selected' : ''); ?>>
 <?php echo e($equipo->nombre); ?> (<?php echo e($equipo->marca); ?> <?php echo e($equipo->modelo); ?>) • S/N: <?php echo e($equipo->serie); ?>

 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 </div>

 
 <div class="min-w-0">
 <label class="field-label flex items-center gap-2"><span>👨‍🔧</span> Técnico Asignado *</label>
 <select name="tecnico_id" required class="glass-input mt-1">
 <?php $__currentLoopData = $tecnicos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tecnico): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="<?php echo e($tecnico->id); ?>" <?php echo e((old('tecnico_id', $mantenimiento->tecnico_id) == $tecnico->id) ? 'selected' : ''); ?>>
 <?php echo e($tecnico->nombre); ?> (<?php echo e($tecnico->identificacion); ?>)
 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 </div>

 
 <div>
 <label class="field-label flex items-center gap-2"><span>🏷️</span> Estado *</label>
 <select name="estado" required class="glass-input no-search mt-1 font-bold <?php echo e(old('estado', $mantenimiento->estado) === 'terminado' ? 'text-emerald-600 dark:text-emerald-400' : 'text-yellow-600 dark:text-yellow-400'); ?>">
 <option value="pendiente" <?php echo e(old('estado', $mantenimiento->estado) == 'pendiente' ? 'selected' : ''); ?> class="text-yellow-600">⏳ Pendiente</option>
 <option value="terminado" <?php echo e(old('estado', $mantenimiento->estado) == 'terminado' ? 'selected' : ''); ?> class="text-emerald-600">✅ Terminado</option>
 </select>
 </div>

 
 <div class="grid grid-cols-2 gap-3 md:col-span-2">
 <div>
 <label class="field-label">Fecha de Entrada *</label>
 <input type="date" name="fecha_entrada" value="<?php echo e(old('fecha_entrada', $mantenimiento->fecha_entrada ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('Y-m-d') : '')); ?>" required class="glass-input mt-1">
 </div>
 <div>
 <label class="field-label flex justify-between">
 <span>Fecha de Salida</span>
 <span class="text-[10px] font-normal text-gray-400 normal-case">(Opcional)</span>
 </label>
 <input type="date" name="fecha_salida" value="<?php echo e(old('fecha_salida', $mantenimiento->fecha_salida ? \Carbon\Carbon::parse($mantenimiento->fecha_salida)->format('Y-m-d') : '')); ?>" class="glass-input mt-1">
 </div>
 </div>

 
 <div>
 <label class="field-label">Tipo de Mantenimiento *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all <?php echo e(old('tipo', $mantenimiento->tipo) == 'preventivo' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md'); ?>" id="lbl_prev">
 <input type="radio" name="tipo" value="preventivo" <?php echo e(old('tipo', $mantenimiento->tipo) == 'preventivo' ? 'checked' : ''); ?> class="accent-emerald-500 w-4 h-4">
 <span class="font-bold <?php echo e(old('tipo', $mantenimiento->tipo) == 'preventivo' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'); ?>">Preventivo</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all <?php echo e(old('tipo', $mantenimiento->tipo) == 'correctivo' ? 'border-orange-500 bg-orange-50/50 dark:bg-orange-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md'); ?>" id="lbl_corr">
 <input type="radio" name="tipo" value="correctivo" <?php echo e(old('tipo', $mantenimiento->tipo) == 'correctivo' ? 'checked' : ''); ?> class="accent-orange-500 w-4 h-4">
 <span class="font-bold <?php echo e(old('tipo', $mantenimiento->tipo) == 'correctivo' ? 'text-orange-700 dark:text-orange-400' : 'text-slate-600 dark:text-slate-400'); ?>">Correctivo</span>
 </label>
 </div>
 </div>

 
 <div>
 <label class="field-label">Tipo de Reparación *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all <?php echo e(old('reparacion', $mantenimiento->reparacion) == 'software' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md'); ?>" id="lbl_soft">
 <input type="radio" name="reparacion" value="software" <?php echo e(old('reparacion', $mantenimiento->reparacion) == 'software' ? 'checked' : ''); ?> class="accent-indigo-500 w-4 h-4">
 <span class="font-bold <?php echo e(old('reparacion', $mantenimiento->reparacion) == 'software' ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400'); ?>">Software</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all <?php echo e(old('reparacion', $mantenimiento->reparacion) == 'hardware' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md'); ?>" id="lbl_hard">
 <input type="radio" name="reparacion" value="hardware" <?php echo e(old('reparacion', $mantenimiento->reparacion) == 'hardware' ? 'checked' : ''); ?> class="accent-rose-500 w-4 h-4">
 <span class="font-bold <?php echo e(old('reparacion', $mantenimiento->reparacion) == 'hardware' ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400'); ?>">Hardware</span>
 </label>
 </div>
 </div>

 
 <div class="md:col-span-2">
 <label class="field-label">Descripción del problema / trabajo *</label>
 <textarea name="descripcion" required rows="3" class="glass-input mt-1 resize-y <?php $__errorArgs = ['descripcion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('descripcion', $mantenimiento->descripcion)); ?></textarea>
 <?php $__errorArgs = ['descripcion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs font-bold mt-1 block"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

  
  <div class="md:col-span-2 p-4 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm mt-2">
  <label class="field-label text-center mb-2 block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Costo Estimado / Final ($) *</label>
  <input type="text" name="costo_visual" id="costo_visual" value="<?php echo e(number_format(old('costo', $mantenimiento->costo), 0, ',', '.')); ?>" required class="glass-input bg-white/50 dark:bg-slate-900/60 border-gray-200/50 dark:border-white/5 text-3xl font-black text-center py-3 text-emerald-600 dark:text-emerald-400 shadow-sm transition-all focus:ring-4 focus:ring-emerald-500/20">
  <input type="hidden" name="costo" id="costo_real" value="<?php echo e(old('costo', $mantenimiento->costo)); ?>">
  </div>

 </div>

  <?php if(auth()->user()->isTecnico()): ?>
  <div class="md:col-span-2">
    <label class="field-label flex items-center gap-2"><span>🔐</span> Contraseña de Administrador *</label>
    <input type="password" name="admin_password" class="glass-input mt-1" placeholder="Requerida para guardar cambios" autocomplete="off">
    <p class="text-xs text-gray-400 mt-1">Como técnico, debes confirmar con la contraseña de un administrador para editar.</p>
  </div>
  <?php endif; ?>

  <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
  <a href="<?php echo e(route('mantenimientos.index')); ?>" class="btn-cancel">↩️ Cancelar</a>
  <button type="submit" class="btn-save">
  🔄 Actualizar Mantenimiento
  </button>
  </div>
 </form>
 </div>
</div>

<script>
 // Manejo visual de radio buttons (Tipo)
 document.querySelectorAll('input[name="tipo"]').forEach(radio => {
 radio.addEventListener('change', function() {
 const lblPrev = document.getElementById('lbl_prev');
 const lblCorr = document.getElementById('lbl_corr');
 
 // Reset styles
 [lblPrev, lblCorr].forEach(lbl => {
 lbl.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md';
 lbl.querySelector('span').className = 'font-bold text-slate-600 dark:text-slate-400';
 });
 
 // Apply active styles
 if (this.value === 'preventivo') {
 lblPrev.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20';
 lblPrev.querySelector('span').className = 'font-bold text-emerald-700 dark:text-emerald-400';
 } else {
 lblCorr.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-orange-500 bg-orange-50/50 dark:bg-orange-900/20';
 lblCorr.querySelector('span').className = 'font-bold text-orange-700 dark:text-orange-400';
 }
 });
 });

 // Manejo visual de radio buttons (Reparación)
 document.querySelectorAll('input[name="reparacion"]').forEach(radio => {
 radio.addEventListener('change', function() {
 const lblSoft = document.getElementById('lbl_soft');
 const lblHard = document.getElementById('lbl_hard');
 
 // Reset styles
 [lblSoft, lblHard].forEach(lbl => {
 lbl.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md';
 lbl.querySelector('span').className = 'font-bold text-slate-600 dark:text-slate-400';
 });
 
 // Apply active styles
 if (this.value === 'software') {
 lblSoft.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20';
 lblSoft.querySelector('span').className = 'font-bold text-indigo-700 dark:text-indigo-400';
 } else {
 lblHard.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-rose-500 bg-rose-50/50 dark:bg-rose-900/20';
 lblHard.querySelector('span').className = 'font-bold text-rose-700 dark:text-rose-400';
 }
 });
 });

 // Colorear select de estado dinámicamente
 const estadoSelect = document.querySelector('select[name="estado"]');
 estadoSelect.addEventListener('change', function() {
 if(this.value === 'terminado') {
 this.classList.remove('text-yellow-600', 'dark:text-yellow-400');
 this.classList.add('text-emerald-600', 'dark:text-emerald-400');
 } else {
 this.classList.remove('text-emerald-600', 'dark:text-emerald-400');
 this.classList.add('text-yellow-600', 'dark:text-yellow-400');
 }
 });

 // Manejo de formato de moneda
 const inputVisual = document.getElementById('costo_visual');
 const inputReal = document.getElementById('costo_real');

 inputVisual.addEventListener('input', function(e) {
 let value = e.target.value.replace(/\D/g, "");
 if (value !== "") {
 inputReal.value = value;
 e.target.value = new Intl.NumberFormat('es-CO').format(value);
 } else {
 inputReal.value = "";
 }
 });

 document.getElementById('mantenimientoForm').addEventListener('submit', function() {
 if(inputReal.value === "") inputReal.value = 0;
 });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/mantenimientos/edit.blade.php ENDPATH**/ ?>