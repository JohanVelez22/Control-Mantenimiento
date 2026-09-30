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

<style>
/* Segmented Radio Cards para Tipo de Persona (Liquid Glass Premium - Estático sin saltos) */
.radio-persona-card {
    display: flex;
    flex: 1;
    justify-content: center;
    align-items: center;
    gap: 0.65rem;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    border: 2px solid rgba(0, 0, 0, 0.08);
    background: rgba(255, 255, 255, 0.35);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.radio-persona-card:hover {
    background: rgba(255, 255, 255, 0.55);
    border-color: rgba(0, 0, 0, 0.15);
}

.dark .radio-persona-card {
    border-color: rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.03);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.dark .radio-persona-card:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.18);
}

/* Estado ACTIVO: Cliente Normal (Sobrio, Limpio, Estático) */
.radio-persona-card.active-cliente {
    border-color: #3b82f6 !important;
    background: rgba(59, 130, 246, 0.08) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.dark .radio-persona-card.active-cliente {
    border-color: #3b82f6 !important;
    background: rgba(59, 130, 246, 0.12) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.20) !important;
}

/* Estado ACTIVO: Técnico (Sobrio, Limpio, Estático) */
.radio-persona-card.active-tecnico {
    border-color: #f97316 !important;
    background: rgba(249, 115, 22, 0.08) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

.dark .radio-persona-card.active-tecnico {
    border-color: #f97316 !important;
    background: rgba(249, 115, 22, 0.12) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.20) !important;
}

/* Indicador Radio Glass (hollow sutil cuando inactivo, dot vibrante cuando activo) */
.ts-radio-persona {
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    width: 18px !important;
    height: 18px !important;
    border-radius: 9999px !important;
    border: 2px solid rgba(148, 163, 184, 0.6) !important;
    background: transparent !important;
    outline: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    flex-shrink: 0 !important;
    margin: 0 !important;
}

.dark .ts-radio-persona {
    border-color: rgba(255, 255, 255, 0.25) !important;
    background: transparent !important;
}

.ts-radio-persona[value="cliente"]:checked {
    border: 2px solid #3b82f6 !important;
    background-color: #3b82f6 !important;
    box-shadow: inset 0 0 0 3px #ffffff !important;
}

.dark .ts-radio-persona[value="cliente"]:checked {
    border-color: #3b82f6 !important;
    background-color: #3b82f6 !important;
    box-shadow: inset 0 0 0 3px #0f172a !important;
}

.ts-radio-persona[value="tecnico"]:checked {
    border: 2px solid #f97316 !important;
    background-color: #f97316 !important;
    box-shadow: inset 0 0 0 3px #ffffff !important;
}

.dark .ts-radio-persona[value="tecnico"]:checked {
    border-color: #f97316 !important;
    background-color: #f97316 !important;
    box-shadow: inset 0 0 0 3px #0f172a !important;
}
</style>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- ── Tipo de Cliente ─────────────────────────────────────────── --}}
    <div class="md:col-span-2">
        <label class="field-label mb-2 block">Tipo de Persona *</label>
        <div class="flex gap-3">
            <label class="radio-persona-card {{ $selTipoCli === 'cliente' ? 'active-cliente' : '' }}">
                <input type="radio" name="tipo_cliente" value="cliente" {{ $selTipoCli === 'cliente' ? 'checked' : '' }}
                       class="ts-radio-persona" required>
                <span class="radio-persona-text font-semibold {{ $selTipoCli === 'cliente' ? 'text-blue-700 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300' }}">👤 Cliente Normal</span>
            </label>
            <label class="radio-persona-card {{ $selTipoCli === 'tecnico' ? 'active-tecnico' : '' }}">
                <input type="radio" name="tipo_cliente" value="tecnico" {{ $selTipoCli === 'tecnico' ? 'checked' : '' }}
                       class="ts-radio-persona">
                <span class="radio-persona-text font-semibold {{ $selTipoCli === 'tecnico' ? 'text-orange-700 dark:text-orange-400' : 'text-slate-600 dark:text-slate-300' }}">🛠️ Técnico</span>
            </label>
        </div>
        @error('tipo_cliente') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Nombre y Apellidos ──────────────────────────────────────── --}}
    <div>
        <label class="field-label">Nombres *</label>
        <input type="text" name="nombres"
               value="{{ old('nombres', $c?->nombres) }}"
               required maxlength="60"
               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
               placeholder="Ej: Juan Carlos"
               class="glass-input @error('nombres') border-red-500 @enderror">
        @error('nombres') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="field-label">Apellidos *</label>
        <input type="text" name="apellidos"
               value="{{ old('apellidos', $c?->apellidos) }}"
               required maxlength="80"
               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')"
               placeholder="Ej: García López"
               class="glass-input @error('apellidos') border-red-500 @enderror">
        @error('apellidos') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Género ──────────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Género *</label>
        <select name="genero" required class="glass-input">
            <option value="masculino"  {{ $selGenero === 'masculino'  ? 'selected' : '' }}>♂ Masculino</option>
            <option value="femenino"   {{ $selGenero === 'femenino'   ? 'selected' : '' }}>♀ Femenino</option>    
        </select>
        @error('genero') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Tipo de Identificación ──────────────────────────────────── --}}
    <div>
        <label class="field-label">Tipo de Identificación *</label>
        <select name="tipo_identificacion" required class="glass-input">
            @foreach($tiposId as $val => $label)
                <option value="{{ $val }}" {{ $selTipoId === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('tipo_identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Número de Identificación ────────────────────────────────── --}}
    <div>
        <label class="field-label">Número de Identificación *</label>
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
        <label class="field-label">Teléfono Móvil *</label>
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

    <div class="md:col-span-2 flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
        <a href="{{ route('clientes.index') }}" class="btn-cancel">✕<space>Cancelar</a>
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
    if (radios.length > 0) {
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                const clienteCard = document.querySelector('input[value="cliente"]').closest('.radio-persona-card');
                const tecnicoCard = document.querySelector('input[value="tecnico"]').closest('.radio-persona-card');
                const clienteText = clienteCard.querySelector('.radio-persona-text');
                const tecnicoText = tecnicoCard.querySelector('.radio-persona-text');
                
                if (this.value === 'cliente') {
                    clienteCard.classList.add('active-cliente');
                    clienteCard.classList.remove('active-tecnico');
                    clienteText.className = "radio-persona-text font-semibold text-blue-700 dark:text-blue-400";
                    
                    tecnicoCard.classList.remove('active-tecnico', 'active-cliente');
                    tecnicoText.className = "radio-persona-text font-semibold text-slate-600 dark:text-slate-300";
                } else {
                    tecnicoCard.classList.add('active-tecnico');
                    tecnicoCard.classList.remove('active-cliente');
                    tecnicoText.className = "radio-persona-text font-semibold text-orange-700 dark:text-orange-400";
                    
                    clienteCard.classList.remove('active-cliente', 'active-tecnico');
                    clienteText.className = "radio-persona-text font-semibold text-slate-600 dark:text-slate-300";
                }
            });
        });
    }
});
</script>
