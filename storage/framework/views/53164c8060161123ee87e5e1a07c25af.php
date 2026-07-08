
<?php
    $p      = $proveedor ?? null;
    $selDep = old('departamento', $p?->departamento ?? '');
    $selMun = old('municipio',    $p?->municipio    ?? '');
    $selTipoId = old('tipo_identificacion', $p?->tipo_identificacion ?? 'nit');
    $selEnt    = old('tipo_entidad', $p?->tipo_entidad ?? 'persona');
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    
    <div>
        <label class="field-label">Tipo de Entidad *</label>
        <select name="tipo_entidad" required class="glass-input no-search w-full">
            <option value="persona"  <?php echo e($selEnt === 'persona'  ? 'selected' : ''); ?>>👤 Persona Natural</option>
            <option value="empresa"  <?php echo e($selEnt === 'empresa'  ? 'selected' : ''); ?>>🏢 Empresa / Sociedad</option>
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
        <label class="field-label">Tipo de Identificación *</label>
        <select name="tipo_identificacion" required class="glass-input no-search w-full">
            <?php $__currentLoopData = $tiposId; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($val); ?>" <?php echo e($selTipoId === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['tipo_identificacion'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Número de Identificación (NIT / Cédula) *</label>
        <input type="text" name="identificacion" required
            value="<?php echo e(old('identificacion', $p?->identificacion ?? '')); ?>"
            oninput="this.value = this.value.replace(/[^0-9\-]/g, '')"
            placeholder="Ej: 900123456-7"
            class="glass-input w-full <?php $__errorArgs = ['identificacion'];
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
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Nombre / Razón Social *</label>
        <input type="text" name="nombre_razon_social" required
            value="<?php echo e(old('nombre_razon_social', $p?->nombre_razon_social ?? '')); ?>"
            placeholder="Ej: Distribuciones ABC S.A.S."
            class="glass-input w-full <?php $__errorArgs = ['nombre_razon_social'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
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
        <label class="field-label">Teléfono Principal</label>
        <input type="text" name="telefono"
            value="<?php echo e(old('telefono', $p?->telefono ?? '')); ?>"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            placeholder="Ej: 3001234567"
            class="glass-input w-full <?php $__errorArgs = ['telefono'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
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
        <label class="field-label">Teléfono Alternativo / Fijo</label>
        <input type="text" name="telefono2"
            value="<?php echo e(old('telefono2', $p?->telefono2 ?? '')); ?>"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            placeholder="Ej: 6012345678"
            class="glass-input w-full">
    </div>

    
    <div>
        <label class="field-label">Nombre del Contacto Principal</label>
        <input type="text" name="contacto_nombre"
            value="<?php echo e(old('contacto_nombre', $p?->contacto_nombre ?? '')); ?>"
            placeholder="Ej: María González"
            class="glass-input w-full">
    </div>

    
    <div>
        <label class="field-label">Correo Electrónico</label>
        <input type="email" name="email"
            value="<?php echo e(old('email', $p?->email ?? '')); ?>"
            placeholder="proveedor@empresa.com"
            class="glass-input w-full <?php $__errorArgs = ['email'];
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
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Departamento</label>
        <select name="departamento" id="prov_departamento" class="glass-input no-search w-full"
                onchange="cargarMunicipiosProv(this.value)">
            <option value="">— Seleccionar departamento —</option>
            <?php $__currentLoopData = $departamentos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($dep); ?>" <?php echo e($selDep === $dep ? 'selected' : ''); ?>><?php echo e($dep); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['departamento'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div>
        <label class="field-label">Municipio / Ciudad</label>
        <select name="municipio" id="prov_municipio" class="glass-input no-search w-full">
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
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    
    <div class="md:col-span-2">
        <label class="field-label">Dirección</label>
        <textarea name="direccion" rows="2" class="glass-input w-full"
                  placeholder="Ej: Calle 45 #12-34, Bogotá"><?php echo e(old('direccion', $p?->direccion ?? '')); ?></textarea>
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
        <label class="field-label">Notas / Condiciones Comerciales</label>
        <textarea name="notas" rows="2" class="glass-input w-full"
                  placeholder="Ej: Crédito a 30 días, descuento del 5%..."><?php echo e(old('notas', $p?->notas ?? '')); ?></textarea>
    </div>

</div>

<script>
async function cargarMunicipiosProv(departamento, seleccionado = '') {
    const select = document.getElementById('prov_municipio');
    select.innerHTML = '<option value="">Cargando...</option>';
    select.disabled = true;

    if (!departamento) {
        select.innerHTML = '<option value="">— Primero selecciona un departamento —</option>';
        select.disabled = false;
        return;
    }

    try {
        const res  = await fetch(`<?php echo e(route('api.municipios')); ?>?departamento=` + encodeURIComponent(departamento));
        const muns = await res.json();
        select.innerHTML = '<option value="">— Seleccionar municipio —</option>';
        muns.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m; opt.textContent = m;
            if (m === seleccionado) opt.selected = true;
            select.appendChild(opt);
        });
    } catch(e) {
        select.innerHTML = '<option value="">Error cargando municipios</option>';
    }
    select.disabled = false;
}

document.addEventListener('DOMContentLoaded', function () {
    const dep = document.getElementById('prov_departamento').value;
    const munSeleccionado = '<?php echo e($selMun); ?>';
    if (dep) cargarMunicipiosProv(dep, munSeleccionado);
});
</script>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/proveedores/_form.blade.php ENDPATH**/ ?>