@php
    $mantenimiento = $mantenimiento ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- N° Orden (Consecutivo automático / ID readonly) --}}
    <div class="md:col-span-2">
        <label class="field-label flex items-center gap-2">N° Orden</label>
        <input type="text" name="id_orden" value="{{ old('id_orden', $mantenimiento->id_orden ?? ($nextOrden ?? '')) }}" readonly
            class="glass-input mt-1 bg-gray-200/50 dark:bg-gray-800/50 cursor-not-allowed text-gray-500 dark:text-gray-400 font-bold font-mono"
            title="El consecutivo se genera automáticamente">
    </div>

    {{-- Equipo --}}
    <div class="md:col-span-2 min-w-0">
        <label class="field-label flex items-center gap-1.5 font-bold">Seleccionar Equipo <span class="text-red-500 font-bold">*</span></label>
        <select name="equipo_id" required class="glass-input text-sm font-bold mt-1" data-placeholder="Buscar equipo o cliente...">
            <option value="">Buscar equipo o cliente...</option>
            @foreach($equipos as $equipo)
                <option value="{{ $equipo->id }}" {{ (old('equipo_id', $mantenimiento->equipo_id ?? '') == $equipo->id) ? 'selected' : '' }}>
                    {{ $equipo->nombre }} ({{ $equipo->marca }} {{ $equipo->modelo }}) • S/N: {{ $equipo->serie }} • {{ $equipo->propietario_label }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Técnico --}}
    <div class="min-w-0">
        <label class="field-label flex items-center gap-2">Técnico Asignado *</label>
        <select name="tecnico_id" required class="glass-input mt-1" data-placeholder="Seleccione un técnico...">
            <option value="">Seleccione un técnico...</option>
            @foreach($tecnicos as $tecnico)
                <option value="{{ $tecnico->id }}" {{ (old('tecnico_id', $mantenimiento->tecnico_id ?? '') == $tecnico->id) ? 'selected' : '' }}>
                    {{ $tecnico->nombre }} ({{ $tecnico->identificacion }})
                </option>
            @endforeach
        </select>
    </div>

    {{-- Estado --}}
    <div>
        <label class="field-label flex items-center gap-2">Estado Inicial *</label>
        <select name="estado" required class="glass-input mt-1 font-bold {{ old('estado', $mantenimiento->estado ?? 'pendiente') === 'terminado' ? 'text-emerald-600 dark:text-emerald-400' : 'text-yellow-600 dark:text-yellow-400' }}">
            <option value="pendiente" {{ old('estado', $mantenimiento->estado ?? 'pendiente') == 'pendiente' ? 'selected' : '' }} class="text-yellow-600">⏳ Pendiente</option>
            <option value="terminado" {{ old('estado', $mantenimiento->estado ?? '') == 'terminado' ? 'selected' : '' }} class="text-emerald-600">✅ Terminado</option>
        </select>
    </div>

    {{-- Fechas --}}
    <div class="grid grid-cols-2 gap-3 md:col-span-2">
        <div>
            <label class="field-label">Fecha de Entrada *</label>
            <input type="date" name="fecha_entrada" value="{{ old('fecha_entrada', isset($mantenimiento->fecha_entrada) ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('Y-m-d') : date('Y-m-d')) }}" required class="glass-input mt-1">
        </div>
        <div>
            <label class="field-label flex justify-between">
                <span>Fecha de Salida</span>
                <span class="text-[10px] font-normal text-gray-400 normal-case">(Opcional)</span>
            </label>
            <input type="date" name="fecha_salida" value="{{ old('fecha_salida', isset($mantenimiento->fecha_salida) ? \Carbon\Carbon::parse($mantenimiento->fecha_salida)->format('Y-m-d') : '') }}" class="glass-input mt-1">
        </div>
    </div>

    {{-- Tipo Mantenimiento --}}
    <div>
        <label class="field-label">Tipo de Mantenimiento *</label>
        <div class="flex gap-3 mt-1">
            <label class="flex-1 ts-select-card hover-emerald {{ old('tipo', $mantenimiento->tipo ?? 'preventivo') == 'preventivo' ? 'active-emerald' : '' }}" id="lbl_prev">
                <input type="radio" name="tipo" value="preventivo" {{ old('tipo', $mantenimiento->tipo ?? 'preventivo') == 'preventivo' ? 'checked' : '' }} class="ts-radio-dot">
                <span>🛡️ Preventivo</span>
            </label>
            <label class="flex-1 ts-select-card hover-orange {{ old('tipo', $mantenimiento->tipo ?? '') == 'correctivo' ? 'active-orange' : '' }}" id="lbl_corr">
                <input type="radio" name="tipo" value="correctivo" {{ old('tipo', $mantenimiento->tipo ?? '') == 'correctivo' ? 'checked' : '' }} class="ts-radio-dot">
                <span>🛠️ Correctivo</span>
            </label>
        </div>
    </div>

    {{-- Tipo Reparación --}}
    <div>
        <label class="field-label">Tipo de Reparación *</label>
        <div class="flex gap-3 mt-1">
            <label class="flex-1 ts-select-card hover-indigo {{ old('reparacion', $mantenimiento->reparacion ?? 'software') == 'software' ? 'active-indigo' : '' }}" id="lbl_soft">
                <input type="radio" name="reparacion" value="software" {{ old('reparacion', $mantenimiento->reparacion ?? 'software') == 'software' ? 'checked' : '' }} class="ts-radio-dot">
                <span>💻 Software</span>
            </label>
            <label class="flex-1 ts-select-card hover-rose {{ old('reparacion', $mantenimiento->reparacion ?? '') == 'hardware' ? 'active-rose' : '' }}" id="lbl_hard">
                <input type="radio" name="reparacion" value="hardware" {{ old('reparacion', $mantenimiento->reparacion ?? '') == 'hardware' ? 'checked' : '' }} class="ts-radio-dot">
                <span>🔩 Hardware</span>
            </label>
        </div>
    </div>

    {{-- Descripción --}}
    <div class="md:col-span-2">
        <label class="field-label">Descripción del problema / trabajo *</label>
        <textarea name="descripcion" required rows="3" placeholder="Detalla los síntomas o las tareas a realizar..." class="glass-input mt-1 resize-y @error('descripcion') border-red-500 @enderror">{{ old('descripcion', $mantenimiento->descripcion ?? '') }}</textarea>
        @error('descripcion') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
    </div>

    {{-- Costo --}}
    <div class="md:col-span-2 mt-2">
        <label class="field-label text-center mb-2 block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Costo Estimado / Final <span class="text-red-500 font-bold">*</span></label>
        <input type="text" name="costo_visual" id="costo_visual" value="{{ isset($mantenimiento->costo) ? number_format(old('costo', $mantenimiento->costo), 0, ',', '.') : '' }}" placeholder="0" required class="glass-input text-3xl font-black text-center py-3 text-emerald-600 dark:text-emerald-400 shadow-sm transition-all focus:ring-4 focus:ring-emerald-500/20">
        <input type="hidden" name="costo" id="costo_real" value="{{ old('costo', $mantenimiento->costo ?? '') }}">
    </div>

    @if(isset($mantenimiento) && auth()->user()->isTecnico())
    <div class="md:col-span-2 mt-4">
        <label class="field-label flex items-center gap-2"><span>🔐</span> Contraseña de Administrador *</label>
        <input type="password" name="admin_password" class="glass-input mt-1" placeholder="Requerida para guardar cambios" autocomplete="off">
        <p class="text-xs text-gray-400 mt-1">Como técnico, debes confirmar con la contraseña de un administrador para editar.</p>
    </div>
    @endif

    <div class="md:col-span-2 flex flex-col md:flex-row items-center justify-end gap-3 pt-3 mt-1">
        <a href="{{ route('mantenimientos.index') }}" class="btn-cancel">✕ Cancelar</a>
        <button type="submit" class="btn-save">{{ isset($mantenimiento) ? '🔄 Actualizar Mantenimiento' : '💾 Guardar Mantenimiento' }}</button>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Radio buttons Tipo
        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const lblPrev = document.getElementById('lbl_prev');
                const lblCorr = document.getElementById('lbl_corr');
                if(!lblPrev || !lblCorr) return;
                
                lblPrev.classList.remove('active-emerald');
                lblCorr.classList.remove('active-orange');

                if (this.value === 'preventivo') {
                    lblPrev.classList.add('active-emerald');
                } else {
                    lblCorr.classList.add('active-orange');
                }
            });
        });

        // Radio buttons Reparación
        document.querySelectorAll('input[name="reparacion"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const lblSoft = document.getElementById('lbl_soft');
                const lblHard = document.getElementById('lbl_hard');
                if(!lblSoft || !lblHard) return;

                lblSoft.classList.remove('active-indigo');
                lblHard.classList.remove('active-rose');

                if (this.value === 'software') {
                    lblSoft.classList.add('active-indigo');
                } else {
                    lblHard.classList.add('active-rose');
                }
            });
        });

        // Select Estado
        const estadoSelect = document.querySelector('select[name="estado"]');
        if(estadoSelect) {
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

        // Formato Moneda
        const inputVisual = document.getElementById('costo_visual');
        const inputReal = document.getElementById('costo_real');
        if(inputVisual && inputReal) {
            inputVisual.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value.length > 12) {
                    value = value.substring(0, 12);
                }
                if (value !== "") {
                    inputReal.value = value;
                    e.target.value = new Intl.NumberFormat('es-CO').format(value);
                } else {
                    inputReal.value = "";
                }
            });
            
            const form = document.getElementById('mantenimientoForm');
            if(form) {
                form.addEventListener('submit', function() {
                    if(inputReal.value === "") inputReal.value = 0;
                });
            }
        }
    });
</script>
