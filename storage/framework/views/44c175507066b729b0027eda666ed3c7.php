<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

 <div>
 <label class="field-label flex items-center gap-2"><span>🔖</span> N° Orden</label>
 <input type="text" name="id_orden" value="<?php echo e(old('id_orden', $electronica->id_orden ?? $nextOrden ?? '')); ?>" class="glass-input mt-1 bg-gray-200/50 dark:bg-gray-800/50 cursor-not-allowed text-gray-500 font-bold" readonly title="El consecutivo se genera automáticamente">
 <?php $__errorArgs = ['id_orden'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div class="min-w-0">
 <label class="field-label flex items-center gap-2"><span>👨‍🔧</span> Técnico Asignado *</label>
 <select name="tecnico_id" required class="glass-input mt-1">
 <option value=\"\">Seleccionar técnico...</option>
 <?php $__currentLoopData = $tecnicos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="<?php echo e($t->id); ?>" <?php echo e(old('tecnico_id', $electronica->tecnico_id ?? '') == $t->id ? 'selected' : ''); ?>><?php echo e($t->nombre); ?></option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 <?php $__errorArgs = ['tecnico_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 
 <div class="md:col-span-2 min-w-0 p-4 bg-purple-50/50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-500/20 rounded-2xl">
 <label class="field-label flex items-center gap-2"><span>💻</span> Seleccionar Dispositivo / Equipo *</label>
 <select name="equipo_id" required class="glass-input text-sm font-bold mt-1">
 <option value=\"\">Seleccione un equipo...</option>
 <?php $__currentLoopData = $equipos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $equipo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="<?php echo e($equipo->id); ?>" <?php echo e(old('equipo_id', $electronica->equipo_id ?? '') == $equipo->id ? 'selected' : ''); ?>>
 <?php echo e($equipo->nombre); ?> (<?php echo e($equipo->marca); ?> <?php echo e($equipo->modelo); ?>) • S/N: <?php echo e($equipo->serie); ?> • Cliente: <?php echo e($equipo->cliente->nombre ?? 'N/A'); ?>

 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 <?php $__errorArgs = ['equipo_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label">Estado *</label>
 <select name="estado" required class="glass-input no-search mt-1 font-bold <?php echo e(old('estado', $electronica->estado ?? 'pendiente') === 'terminado' ? 'text-emerald-600 dark:text-emerald-400' : 'text-yellow-600 dark:text-yellow-400'); ?>">
 <option value="pendiente" <?php echo e(old('estado', $electronica->estado ?? 'pendiente') === 'pendiente' ? 'selected' : ''); ?> class="text-yellow-600">⏳ Pendiente</option>
 <option value="terminado" <?php echo e(old('estado', $electronica->estado ?? '') === 'terminado' ? 'selected' : ''); ?> class="text-emerald-600">✅ Terminado</option>
 </select>
 </div>

 
 <div class="md:col-span-2">
 <label class="field-label">Tipo de Trabajo *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all <?php echo e(old('tipo', $electronica->tipo ?? 'correctivo') === 'correctivo' ? 'border-orange-500 bg-orange-50/50 dark:bg-orange-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md'); ?>" id="lbl_corr_elec">
 <input type="radio" name="tipo" value="correctivo" <?php echo e(old('tipo', $electronica->tipo ?? 'correctivo') === 'correctivo' ? 'checked' : ''); ?> class="accent-orange-500 w-4 h-4">
 <span class="font-bold <?php echo e(old('tipo', $electronica->tipo ?? 'correctivo') === 'correctivo' ? 'text-orange-700 dark:text-orange-400' : 'text-slate-600 dark:text-slate-400'); ?>">Correctivo</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all <?php echo e(old('tipo', $electronica->tipo ?? '') === 'preventivo' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md'); ?>" id="lbl_prev_elec">
 <input type="radio" name="tipo" value="preventivo" <?php echo e(old('tipo', $electronica->tipo ?? '') === 'preventivo' ? 'checked' : ''); ?> class="accent-emerald-500 w-4 h-4">
 <span class="font-bold <?php echo e(old('tipo', $electronica->tipo ?? '') === 'preventivo' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400'); ?>">Preventivo</span>
 </label>
 </div>
 </div>

 <div class="md:col-span-2">
 <label class="field-label">Descripción del Problema / Falla *</label>
 <textarea name="descripcion_problema" required rows="3" class="glass-input mt-1 resize-y <?php $__errorArgs = ['descripcion_problema'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"><?php echo e(old('descripcion_problema', $electronica->descripcion_problema ?? '')); ?></textarea>
 <?php $__errorArgs = ['descripcion_problema'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div class="grid grid-cols-2 gap-3 md:col-span-2">
 <div>
 <label class="field-label">Fecha Entrada *</label>
 <input type="date" name="fecha_entrada" required value="<?php echo e(old('fecha_entrada', isset($electronica) && $electronica->fecha_entrada ? \Carbon\Carbon::parse($electronica->fecha_entrada)->format('Y-m-d') : date('Y-m-d'))); ?>" class="glass-input mt-1">
 <?php $__errorArgs = ['fecha_entrada'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 <div>
 <label class="field-label flex justify-between">
 <span>Fecha Salida</span>
 <span class="text-[10px] font-normal text-gray-400 normal-case">(Opcional)</span>
 </label>
 <input type="date" name="fecha_salida" value="<?php echo e(old('fecha_salida', isset($electronica) && $electronica->fecha_salida ? \Carbon\Carbon::parse($electronica->fecha_salida)->format('Y-m-d') : '')); ?>" class="glass-input mt-1">
 <?php $__errorArgs = ['fecha_salida'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 </div>

 
 <div class="md:col-span-2 p-4 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm mt-2">
 <label class="field-label text-center mb-2 block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Costo Estimado / Final ($) *</label>
 <input type="text" id="costo_visual" value="<?php echo e(old('costo', isset($electronica) ? number_format($electronica->costo, 0, '', '') : 0)); ?>" class="glass-input bg-white/50 dark:bg-slate-900/60 border-gray-200/50 dark:border-white/5 text-3xl font-black text-center py-3 text-emerald-600 dark:text-emerald-400 shadow-sm transition-all focus:ring-4 focus:ring-emerald-500/20" placeholder="0">
 <input type="hidden" name="costo" id="costo_real" value="<?php echo e(old('costo', isset($electronica) ? intval($electronica->costo) : 0)); ?>">
 <?php $__errorArgs = ['costo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-2 text-center"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

</div>

<script>
 // Formateador de monto
 function formatInput(visualId, realId) {
 const inputVisual = document.getElementById(visualId);
 const inputReal = document.getElementById(realId);

 if(!inputVisual || !inputReal) return;

 // Inicializa visual
 if (inputReal.value) {
 inputVisual.value = new Intl.NumberFormat('es-CO').format(inputReal.value);
 }

 inputVisual.addEventListener('input', function(e) {
 let value = e.target.value.replace(/\D/g, "");
 if (value !== "") {
 inputReal.value = value;
 e.target.value = new Intl.NumberFormat('es-CO').format(value);
 } else {
 inputReal.value = "";
 }
 });
 
 // Ensure form submission updates real value if empty
 const form = inputVisual.closest('form');
 if (form) {
 form.addEventListener('submit', function() {
 if(inputReal.value === "") inputReal.value = 0;
 });
 }
 }
 formatInput('costo_visual', 'costo_real');

 // Manejo visual de radio buttons (Tipo Electrónica)
 document.querySelectorAll('input[name="tipo"]').forEach(radio => {
 radio.addEventListener('change', function() {
 const lblCorr = document.getElementById('lbl_corr_elec');
 const lblPrev = document.getElementById('lbl_prev_elec');
 
 // Reset styles
 [lblCorr, lblPrev].forEach(lbl => {
 lbl.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md';
 lbl.querySelector('span').className = 'font-bold text-slate-600 dark:text-slate-400';
 });
 
 // Apply active styles
 if (this.value === 'correctivo') {
 lblCorr.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-orange-500 bg-orange-50/50 dark:bg-orange-900/20';
 lblCorr.querySelector('span').className = 'font-bold text-orange-700 dark:text-orange-400';
 } else {
 lblPrev.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20';
 lblPrev.querySelector('span').className = 'font-bold text-emerald-700 dark:text-emerald-400';
 }
 });
 });

 // Colorear select de estado dinámicamente
 const estadoSelect = document.querySelector('select[name="estado"]');
 if (estadoSelect) {
 estadoSelect.addEventListener('change', function() {
 if(this.value === 'terminado') {
 this.classList.remove('text-yellow-600', 'dark:text-yellow-400');
 this.classList.add('text-emerald-600', 'dark:text-emerald-400');
 } else {
 this.classList.remove('text-emerald-600', 'dark:text-emerald-400');
 this.classList.add('text-yellow-600', 'dark:text-yellow-400');
 }
 });
 }

</script>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/electronicas/_form.blade.php ENDPATH**/ ?>