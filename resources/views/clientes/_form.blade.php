{{-- resources/views/clientes/_form.blade.php --}}
{{-- Variables: $cliente (optional), $departamentos, $tiposId, $municipios (optional) --}}
@php
    $c          = $cliente ?? null;
    $selDep     = old('departamento', $c?->departamento ?? '');
    $selMun     = old('municipio',    $c?->municipio    ?? '');
    $selGenero  = old('genero',       $c?->genero       ?? 'indefinido');
    $selTipoId  = old('tipo_identificacion', $c?->tipo_identificacion ?? 'cedula_ciudadania');
    $selTipoCli = old('tipo_cliente', $c?->tipo_cliente ?? 'cliente');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- ── Tipo de Cliente ─────────────────────────────────────────── --}}
    <div>
        <label class="field-label flex items-center gap-1.5 font-bold">
            Tipo de Persona <span class="text-red-500 font-bold">*</span>
        </label>
        <div class="flex gap-2.5 mt-1">
            <label id="label_cliente" class="flex-1 ts-select-card hover-blue {{ $selTipoCli === 'cliente' ? 'active-blue' : '' }}">
                <input type="radio" name="tipo_cliente" value="cliente" required id="tipo_cliente_normal" {{ $selTipoCli === 'cliente' ? 'checked' : '' }} class="ts-radio-dot">
                <span id="text_cliente">👤 Cliente Normal</span>
            </label>
            <label id="label_tecnico" class="flex-1 ts-select-card hover-orange {{ $selTipoCli === 'tecnico' ? 'active-orange' : '' }}">
                <input type="radio" name="tipo_cliente" value="tecnico" id="tipo_cliente_tecnico" {{ $selTipoCli === 'tecnico' ? 'checked' : '' }} class="ts-radio-dot">
                <span id="text_tecnico">🛠️ Técnico</span>
            </label>
        </div>
        @error('tipo_cliente') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Género ──────────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Género <span class="text-red-500 font-bold">*</span></label>
        <select name="genero" required class="glass-input">
            <option value="masculino"  {{ $selGenero === 'masculino'  ? 'selected' : '' }}>♂ Masculino</option>
            <option value="femenino"   {{ $selGenero === 'femenino'   ? 'selected' : '' }}>♀ Femenino</option>    
        </select>
        @error('genero') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Nombre y Apellidos ──────────────────────────────────────── --}}
    <div>
        <label class="field-label">Nombres <span class="text-red-500 font-bold">*</span></label>
        <input type="text" name="nombres"
               value="{{ old('nombres', $c?->nombres) }}"
               required maxlength="60"
               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
               placeholder="Ej: Juan Carlos"
               class="glass-input @error('nombres') border-red-500 @enderror">
        @error('nombres') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">Apellidos <span class="text-red-500 font-bold">*</span></label>
        <input type="text" name="apellidos"
               value="{{ old('apellidos', $c?->apellidos) }}"
               required maxlength="80"
               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
               placeholder="Ej: García López"
               class="glass-input @error('apellidos') border-red-500 @enderror">
        @error('apellidos') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Tipo de Identificación ──────────────────────────────────── --}}
    <div>
        <label class="field-label">Tipo de Identificación <span class="text-red-500 font-bold">*</span></label>
        <select name="tipo_identificacion" required class="glass-input">
            @foreach($tiposId as $val => $label)
                <option value="{{ $val }}" {{ $selTipoId === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('tipo_identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Número de Identificación ────────────────────────────────── --}}
    <div>
        <label class="field-label">Número de Identificación <span class="text-red-500 font-bold">*</span></label>
        <input type="text" name="identificacion"
               value="{{ old('identificacion', $c?->identificacion) }}"
               required maxlength="30"
               oninput="this.value=this.value.replace(/[^0-9\-]/g,'')"
               placeholder="Ej: 1234567890"
               class="glass-input @error('identificacion') border-red-500 @enderror">
        @error('identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Teléfono Móvil ──────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Teléfono Móvil <span class="text-red-500 font-bold">*</span></label>
        <input type="tel" name="movil"
               value="{{ old('movil', $c?->movil) }}"
               required maxlength="30"
               oninput="this.value=this.value.replace(/[^0-9]/g,'')"
               placeholder="Ej: 3001234567"
               class="glass-input @error('movil') border-red-500 @enderror">
        @error('movil') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Email ───────────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Correo Electrónico</label>
        <input type="email" name="email"
               value="{{ old('email', $c?->email) }}"
               maxlength="100"
               placeholder="correo@ejemplo.com"
               class="glass-input @error('email') border-red-500 @enderror">
        @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Departamento ────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Departamento</label>
        <select name="departamento" id="select_departamento" class="glass-input" data-placeholder="Seleccionar departamento..."
                onchange="cargarMunicipios(this.value)">
            <option value=""></option>
            @foreach($departamentos as $dep)
                <option value="{{ $dep }}" {{ $selDep === $dep ? 'selected' : '' }}>{{ $dep }}</option>
            @endforeach
        </select>
        @error('departamento') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Municipio ───────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Municipio / Ciudad</label>
        <select name="municipio" id="select_municipio" class="glass-input" data-placeholder="Seleccionar municipio...">
            <option value=""></option>
            @if(!empty($municipios))
                @foreach($municipios as $mun)
                    <option value="{{ $mun }}" {{ $selMun === $mun ? 'selected' : '' }}>{{ $mun }}</option>
                @endforeach
            @endif
        </select>
        @error('municipio') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Dirección ───────────────────────────────────────────────── --}}
    <div class="md:col-span-2">
        <label class="field-label">Dirección</label>
        <textarea name="direccion" rows="2"
                  placeholder="Ej: Calle 45 #12-34, Barrio Centro"
                  class="glass-input resize-y">{{ old('direccion', $c?->direccion) }}</textarea>
        @error('direccion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2 flex flex-col md:flex-row items-center justify-end gap-3 mt-0 pt-0">
        <a href="{{ route('clientes.index') }}" class="btn-cancel">✕ Cancelar</a>
        <button type="submit" class="btn-save">{{ $c ? '🔄 Actualizar Cliente' : '💾 Guardar Cliente' }}</button>
    </div>

</div>

<script>
async function cargarMunicipios(departamento, seleccionado = '') {
    const select = document.getElementById('select_municipio');
    const ts = select.tomselect; // Obtener instancia de TomSelect si existe

    if (ts) {
        ts.disable(); // Solo deshabilitamos mientras carga para evitar parpadeos visuales
    } else {
        select.disabled = true;
    }

    if (!departamento) {
        if (ts) {
            ts.clearOptions();
            ts.setValue('');
            ts.enable();
        } else {
            select.innerHTML = '<option value=""></option>';
            select.disabled = false;
        }
        return;
    }

    try {
        const res = await fetch(`{{ route('api.municipios') }}?departamento=` + encodeURIComponent(departamento));
        const municipios = await res.json();

        if (ts) {
            ts.clearOptions();
            municipios.forEach(m => {
                ts.addOption({value: m, text: m});
            });
            if (seleccionado && municipios.includes(seleccionado)) {
                ts.setValue(seleccionado);
            } else if (municipios.length > 0) {
                ts.setValue(municipios[0]); // Seleccionar primera ciudad automáticamente
            } else {
                ts.setValue('');
            }
        } else {
            select.innerHTML = '';
            municipios.forEach((m, index) => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                if (m === seleccionado || (index === 0 && !seleccionado)) opt.selected = true;
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
    const munSeleccionado = '{{ $selMun }}';
    if (dep) {
        cargarMunicipios(dep, munSeleccionado);
    }

    // Estilo dinámico para botones de radio
    const radios = document.querySelectorAll('input[name="tipo_cliente"]');
    const labelCliente = document.getElementById('label_cliente');
    const labelTecnico = document.getElementById('label_tecnico');
    if (radios.length > 0 && labelCliente && labelTecnico) {
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'cliente') {
                    labelCliente.classList.add('active-blue');
                    labelTecnico.classList.remove('active-orange');
                } else {
                    labelTecnico.classList.add('active-orange');
                    labelCliente.classList.remove('active-blue');
                }
            });
        });
    }
});
</script>
