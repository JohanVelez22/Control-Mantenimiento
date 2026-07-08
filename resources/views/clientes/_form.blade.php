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

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- ── Tipo de Cliente ─────────────────────────────────────────── --}}
    <div class="md:col-span-2">
        <label class="field-label mb-2 block">Tipo de Persona *</label>
        <div class="flex gap-3">
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                {{ $selTipoCli === 'cliente' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30' }}">
                <input type="radio" name="tipo_cliente" value="cliente" {{ $selTipoCli === 'cliente' ? 'checked' : '' }} class="accent-blue-500 w-4 h-4" required>
                <span class="font-bold {{ $selTipoCli === 'cliente' ? 'text-blue-700 dark:text-blue-400' : 'text-slate-600 dark:text-slate-400' }}">👤 Cliente Normal</span>
            </label>
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all
                {{ $selTipoCli === 'tecnico' ? 'border-orange-500 bg-orange-50/50 dark:bg-orange-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30' }}">
                <input type="radio" name="tipo_cliente" value="tecnico" {{ $selTipoCli === 'tecnico' ? 'checked' : '' }} class="accent-orange-500 w-4 h-4">
                <span class="font-bold {{ $selTipoCli === 'tecnico' ? 'text-orange-700 dark:text-orange-400' : 'text-slate-600 dark:text-slate-400' }}">🔧 Técnico</span>
            </label>
        </div>
        <p class="text-[11px] text-gray-400 mt-1">Los técnicos acceden al <strong>precio técnico</strong> al facturar productos.</p>
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
        <select name="genero" required class="glass-input no-search">
            <option value="masculino"  {{ $selGenero === 'masculino'  ? 'selected' : '' }}>♂ Masculino</option>
            <option value="femenino"   {{ $selGenero === 'femenino'   ? 'selected' : '' }}>♀ Femenino</option>
            <option value="indefinido" {{ $selGenero === 'indefinido' ? 'selected' : '' }}>⊘ Indefinido / No especifica</option>
        </select>
        @error('genero') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Tipo de Identificación ──────────────────────────────────── --}}
    <div>
        <label class="field-label">Tipo de Identificación *</label>
        <select name="tipo_identificacion" required class="glass-input no-search">
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
        <select name="departamento" id="select_departamento" class="glass-input no-search"
                onchange="cargarMunicipios(this.value)">
            <option value="">— Seleccionar departamento —</option>
            @foreach($departamentos as $dep)
                <option value="{{ $dep }}" {{ $selDep === $dep ? 'selected' : '' }}>{{ $dep }}</option>
            @endforeach
        </select>
        @error('departamento') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Municipio ───────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Municipio / Ciudad</label>
        <select name="municipio" id="select_municipio" class="glass-input no-search">
            <option value="">— Primero selecciona un departamento —</option>
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

</div>

<script>
async function cargarMunicipios(departamento, seleccionado = '') {
    const select = document.getElementById('select_municipio');
    select.innerHTML = '<option value="">Cargando...</option>';
    select.disabled = true;

    if (!departamento) {
        select.innerHTML = '<option value="">— Primero selecciona un departamento —</option>';
        select.disabled = false;
        return;
    }

    try {
        const res = await fetch(`{{ route('api.municipios') }}?departamento=` + encodeURIComponent(departamento));
        const municipios = await res.json();

        select.innerHTML = '<option value="">— Seleccionar municipio —</option>';
        municipios.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            if (m === seleccionado) opt.selected = true;
            select.appendChild(opt);
        });
    } catch(e) {
        select.innerHTML = '<option value="">Error cargando municipios</option>';
    }
    select.disabled = false;
}

// Al cargar la página, si ya hay un departamento seleccionado, cargar sus municipios
document.addEventListener('DOMContentLoaded', function() {
    const dep = document.getElementById('select_departamento').value;
    const munSeleccionado = '{{ $selMun }}';
    if (dep) {
        cargarMunicipios(dep, munSeleccionado);
    }
});
</script>
