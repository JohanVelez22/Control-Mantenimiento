{{-- resources/views/proveedores/_form.blade.php --}}
@php
    $p      = $proveedor ?? null;
    $selDep = old('departamento', $p?->departamento ?? '');
    $selMun = old('municipio',    $p?->municipio    ?? '');
    $selTipoId = old('tipo_identificacion', $p?->tipo_identificacion ?? 'nit');
    $selEnt    = old('tipo_entidad', $p?->tipo_entidad ?? 'persona');
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- ── Tipo de Entidad ──────────────────────────────────────── --}}
    <div>
        <label class="field-label">Tipo de Entidad *</label>
        <select name="tipo_entidad" required class="glass-input no-search w-full">
            <option value="persona"  {{ $selEnt === 'persona'  ? 'selected' : '' }}>👤 Persona Natural</option>
            <option value="empresa"  {{ $selEnt === 'empresa'  ? 'selected' : '' }}>🏢 Empresa / Sociedad</option>
        </select>
        @error('tipo_entidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Tipo de Identificación ────────────────────────────────── --}}
    <div>
        <label class="field-label">Tipo de Identificación *</label>
        <select name="tipo_identificacion" required class="glass-input no-search w-full">
            @foreach($tiposId as $val => $label)
                <option value="{{ $val }}" {{ $selTipoId === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('tipo_identificacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Identificación ────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Número de Identificación (NIT / Cédula) *</label>
        <input type="text" name="identificacion" required
            value="{{ old('identificacion', $p?->identificacion ?? '') }}"
            oninput="this.value = this.value.replace(/[^0-9\-]/g, '')"
            placeholder="Ej: 900123456-7"
            class="glass-input w-full @error('identificacion') border-red-500 @enderror">
        @error('identificacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Nombre / Razón Social ─────────────────────────────────── --}}
    <div>
        <label class="field-label">Nombre / Razón Social *</label>
        <input type="text" name="nombre_razon_social" required
            value="{{ old('nombre_razon_social', $p?->nombre_razon_social ?? '') }}"
            placeholder="Ej: Distribuciones ABC S.A.S."
            class="glass-input w-full @error('nombre_razon_social') border-red-500 @enderror">
        @error('nombre_razon_social') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Teléfono Principal ────────────────────────────────────── --}}
    <div>
        <label class="field-label">Teléfono Principal</label>
        <input type="text" name="telefono"
            value="{{ old('telefono', $p?->telefono ?? '') }}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            placeholder="Ej: 3001234567"
            class="glass-input w-full @error('telefono') border-red-500 @enderror">
        @error('telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Teléfono Alternativo ──────────────────────────────────── --}}
    <div>
        <label class="field-label">Teléfono Alternativo / Fijo</label>
        <input type="text" name="telefono2"
            value="{{ old('telefono2', $p?->telefono2 ?? '') }}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            placeholder="Ej: 6012345678"
            class="glass-input w-full">
    </div>

    {{-- ── Nombre Contacto ───────────────────────────────────────── --}}
    <div>
        <label class="field-label">Nombre del Contacto Principal</label>
        <input type="text" name="contacto_nombre"
            value="{{ old('contacto_nombre', $p?->contacto_nombre ?? '') }}"
            placeholder="Ej: María González"
            class="glass-input w-full">
    </div>

    {{-- ── Email ─────────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Correo Electrónico</label>
        <input type="email" name="email"
            value="{{ old('email', $p?->email ?? '') }}"
            placeholder="proveedor@empresa.com"
            class="glass-input w-full @error('email') border-red-500 @enderror">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Departamento ──────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Departamento</label>
        <select name="departamento" id="prov_departamento" class="glass-input no-search w-full"
                onchange="cargarMunicipiosProv(this.value)">
            <option value="">— Seleccionar departamento —</option>
            @foreach($departamentos as $dep)
                <option value="{{ $dep }}" {{ $selDep === $dep ? 'selected' : '' }}>{{ $dep }}</option>
            @endforeach
        </select>
        @error('departamento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Municipio ─────────────────────────────────────────────── --}}
    <div>
        <label class="field-label">Municipio / Ciudad</label>
        <select name="municipio" id="prov_municipio" class="glass-input no-search w-full">
            <option value="">— Primero selecciona un departamento —</option>
            @if(!empty($municipios))
                @foreach($municipios as $mun)
                    <option value="{{ $mun }}" {{ $selMun === $mun ? 'selected' : '' }}>{{ $mun }}</option>
                @endforeach
            @endif
        </select>
        @error('municipio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Dirección ─────────────────────────────────────────────── --}}
    <div class="md:col-span-2">
        <label class="field-label">Dirección</label>
        <textarea name="direccion" rows="2" class="glass-input w-full"
                  placeholder="Ej: Calle 45 #12-34, Bogotá">{{ old('direccion', $p?->direccion ?? '') }}</textarea>
        @error('direccion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- ── Notas ─────────────────────────────────────────────────── --}}
    <div class="md:col-span-2">
        <label class="field-label">Notas / Condiciones Comerciales</label>
        <textarea name="notas" rows="2" class="glass-input w-full"
                  placeholder="Ej: Crédito a 30 días, descuento del 5%...">{{ old('notas', $p?->notas ?? '') }}</textarea>
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
        const res  = await fetch(`{{ route('api.municipios') }}?departamento=` + encodeURIComponent(departamento));
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
    const munSeleccionado = '{{ $selMun }}';
    if (dep) cargarMunicipiosProv(dep, munSeleccionado);
});
</script>
