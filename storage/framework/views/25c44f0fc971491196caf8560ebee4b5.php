

<?php
    $c          = $cliente ?? null;
    $selDep     = old('departamento', $c?->departamento ?? '');
    $selMun     = old('municipio',    $c?->municipio    ?? '');
    $selGenero  = old('genero',       $c?->genero       ?? 'indefinido');
    $selTipoId  = old('tipo_identificacion', $c?->tipo_identificacion ?? 'cedula_ciudadania');
    $selTipoCli = old('tipo_cliente', $c?->tipo_cliente ?? 'cliente');
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    
    <div class="md:col-span-2">
        <label class="field-label mb-2 block">Tipo de Persona *</label>
        <div class="flex gap-3">
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                <?php echo e($selTipoCli === 'cliente' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30'); ?>">
                <input type="radio" name="tipo_cliente" value="cliente" <?php echo e($selTipoCli === 'cliente' ? 'checked' : ''); ?> class="accent-blue-500 w-4 h-4" required>
                <span class="font-bold <?php echo e($selTipoCli === 'cliente' ? 'text-blue-700 dark:text-blue-400' : 'text-slate-600 dark:text-slate-400'); ?>">👤 Cliente Normal</span>
            </label>
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                <?php echo e($selTipoCli === 'tecnico' ? 'border-orange-500 bg-orange-50/50 dark:bg-orange-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30'); ?>">
                <input type="radio" name="tipo_cliente" value="tecnico" <?php echo e($selTipoCli === 'tecnico' ? 'checked' : ''); ?> class="accent-orange-500 w-4 h-4">
                <span class="font-bold <?php echo e($selTipoCli === 'tecnico' ? 'text-orange-700 dark:text-orange-400' : 'text-slate-600 dark:text-slate-400'); ?>">🔧 Técnico</span>
            </label>
        </div>
        <p class="text-[11px] text-gray-400 mt-1">Los técnicos acceden al <strong>precio técnico</strong> al facturar productos.</p>
        <?php $__errorArgs = ['tipo_cliente'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Nombres *</label>
        <input type="text" name="nombres"
               value="<?php echo e(old('nombres', $c?->nombres)); ?>"
               required maxlength="60"
               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
               placeholder="Ej: Juan Carlos"
               class="glass-input <?php $__errorArgs = ['nombres'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
        <?php $__errorArgs = ['nombres'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="field-label">Apellidos *</label>
        <input type="text" name="apellidos"
               value="<?php echo e(old('apellidos', $c?->apellidos)); ?>"
               required maxlength="80"
               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
               placeholder="Ej: García López"
               class="glass-input <?php $__errorArgs = ['apellidos'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
        <?php $__errorArgs = ['apellidos'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Género *</label>
        <select name="genero" required class="glass-input no-search">
            <option value="masculino"  <?php echo e($selGenero === 'masculino'  ? 'selected' : ''); ?>>♂ Masculino</option>
            <option value="femenino"   <?php echo e($selGenero === 'femenino'   ? 'selected' : ''); ?>>♀ Femenino</option>
            <option value="indefinido" <?php echo e($selGenero === 'indefinido' ? 'selected' : ''); ?>>⊘ Indefinido / No especifica</option>
        </select>
        <?php $__errorArgs = ['genero'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Tipo de Identificación *</label>
        <select name="tipo_identificacion" required class="glass-input no-search">
            <?php $__currentLoopData = $tiposId; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($val); ?>" <?php echo e($selTipoId === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['tipo_identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Número de Identificación *</label>
        <input type="text" name="identificacion"
               value="<?php echo e(old('identificacion', $c?->identificacion)); ?>"
               required maxlength="30"
               oninput="this.value=this.value.replace(/[^0-9\-]/g,'')"
               placeholder="Ej: 1234567890"
               class="glass-input <?php $__errorArgs = ['identificacion'];
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
        <label class="field-label">Teléfono Móvil *</label>
        <input type="tel" name="movil"
               value="<?php echo e(old('movil', $c?->movil)); ?>"
               required maxlength="30"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')"
               placeholder="Ej: 3001234567"
               class="glass-input <?php $__errorArgs = ['movil'];
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

    
    <div>
        <label class="field-label">Correo Electrónico</label>
        <input type="email" name="email"
               value="<?php echo e(old('email', $c?->email)); ?>"
               maxlength="100"
               placeholder="correo@ejemplo.com"
               class="glass-input <?php $__errorArgs = ['email'];
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

    
    <div>
        <label class="field-label">Departamento</label>
        <select name="departamento" id="select_departamento" class="glass-input no-search"
                onchange="cargarMunicipios(this.value)">
            <option value="">— Seleccionar departamento —</option>
            <?php $__currentLoopData = $departamentos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($dep); ?>" <?php echo e($selDep === $dep ? 'selected' : ''); ?>><?php echo e($dep); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['departamento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Municipio / Ciudad</label>
        <select name="municipio" id="select_municipio" class="glass-input no-search">
            <option value="">— Primero selecciona un departamento —</option>
            <?php if(!empty($municipios)): ?>
                <?php $__currentLoopData = $municipios; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mun): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($mun); ?>" <?php echo e($selMun === $mun ? 'selected' : ''); ?>><?php echo e($mun); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        </select>
        <?php $__errorArgs = ['municipio'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div class="md:col-span-2">
        <label class="field-label">Dirección</label>
        <textarea name="direccion" rows="2"
                  placeholder="Ej: Calle 45 #12-34, Barrio Centro"
                  class="glass-input resize-y"><?php echo e(old('direccion', $c?->direccion)); ?></textarea>
        <?php $__errorArgs = ['direccion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

</div>

<script>
async function cargarMunicipios(departamento, seleccionado = '') {
    const select = document.getElementById('select_municipio');
    const ts = select.tomselect; // Obtener instancia de TomSelect si existe

    if (ts) {
        ts.clearOptions();
        ts.clear();
        ts.disable();
        ts.addOption({value: '', text: 'Cargando...'});
        ts.setValue('');
    } else {
        select.innerHTML = '<option value="">Cargando...</option>';
        select.disabled = true;
    }

    if (!departamento) {
        if (ts) {
            ts.clearOptions();
            ts.addOption({value: '', text: '— Primero selecciona un departamento —'});
            ts.setValue('');
            ts.enable();
        } else {
            select.innerHTML = '<option value="">— Primero selecciona un departamento —</option>';
            select.disabled = false;
        }
        return;
    }

    try {
        const res = await fetch(`<?php echo e(route('api.municipios')); ?>?departamento=` + encodeURIComponent(departamento));
        const municipios = await res.json();

        if (ts) {
            ts.clearOptions();
            ts.addOption({value: '', text: '— Seleccionar municipio —'});
            municipios.forEach(m => {
                ts.addOption({value: m, text: m});
            });
            if (seleccionado && municipios.includes(seleccionado)) {
                ts.setValue(seleccionado);
            } else {
                ts.setValue('');
            }
        } else {
            select.innerHTML = '<option value="">— Seleccionar municipio —</option>';
            municipios.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                if (m === seleccionado) opt.selected = true;
                select.appendChild(opt);
            });
        }
    } catch(e) {
        if (ts) {
            ts.clearOptions();
            ts.addOption({value: '', text: 'Error cargando municipios'});
            ts.setValue('');
        } else {
            select.innerHTML = '<option value="">Error cargando municipios</option>';
        }
    }
    
    if (ts) {
        ts.enable();
    } else {
        select.disabled = false;
    }
}

// Al cargar la página, si ya hay un departamento seleccionado, cargar sus municipios
document.addEventListener('DOMContentLoaded', function() {
    const dep = document.getElementById('select_departamento').value;
    const munSeleccionado = '<?php echo e($selMun); ?>';
    if (dep) {
        cargarMunicipios(dep, munSeleccionado);
    }

    // Radio button dynamic styling
    const radios = document.querySelectorAll('input[name="tipo_cliente"]');
    if (radios.length > 0) {
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                const clienteLabel = document.querySelector('input[value="cliente"]').closest('label');
                const tecnicoLabel = document.querySelector('input[value="tecnico"]').closest('label');
                
                if (this.value === 'cliente') {
                    clienteLabel.className = "flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-blue-500 bg-blue-50/50 dark:bg-blue-900/20";
                    clienteLabel.querySelector('span').className = "font-bold text-blue-700 dark:text-blue-400";
                    clienteLabel.querySelector('span').innerHTML = "👤 Cliente Normal";
                    
                    tecnicoLabel.className = "flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30";
                    tecnicoLabel.querySelector('span').className = "font-bold text-slate-600 dark:text-slate-400";
                    tecnicoLabel.querySelector('span').innerHTML = "🔧 Técnico";
                } else {
                    tecnicoLabel.className = "flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-orange-500 bg-orange-50/50 dark:bg-orange-900/20";
                    tecnicoLabel.querySelector('span').className = "font-bold text-orange-700 dark:text-orange-400";
                    tecnicoLabel.querySelector('span').innerHTML = "🔧 Técnico";
                    
                    clienteLabel.className = "flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30";
                    clienteLabel.querySelector('span').className = "font-bold text-slate-600 dark:text-slate-400";
                    clienteLabel.querySelector('span').innerHTML = "👤 Cliente Normal";
                }
            });
        });
    }
});
</script>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/clientes/_form.blade.php ENDPATH**/ ?>