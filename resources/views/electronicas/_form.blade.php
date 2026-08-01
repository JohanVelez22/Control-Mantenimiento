@php
    $electronica = $electronica ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- N° Orden (Consecutivo automático / ID readonly) --}}
    <div class="md:col-span-2">
        <label class="field-label flex items-center gap-2"><span>🔖</span> N° Orden</label>
        <input type="text" name="id_orden" value="{{ old('id_orden', $electronica->id_orden ?? ($nextOrden ?? '')) }}" readonly
            class="glass-input mt-1 bg-gray-200/50 dark:bg-gray-800/50 cursor-not-allowed text-gray-500 dark:text-gray-400 font-bold font-mono"
            title="El consecutivo se genera automáticamente">
    </div>

    {{-- Equipo --}}
    <div class="md:col-span-2 min-w-0 p-4 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
        <label class="field-label flex items-center gap-2"><span>💻</span> Seleccionar Dispositivo / Equipo *</label>
        <select name="equipo_id" required class="glass-input text-sm font-bold mt-1">
            <option value="">Seleccione un equipo...</option>
            @foreach($equipos as $equipo)
                <option value="{{ $equipo->id }}" {{ (old('equipo_id', $electronica->equipo_id ?? '') == $equipo->id) ? 'selected' : '' }}>
                    {{ $equipo->nombre }} ({{ $equipo->marca }} {{ $equipo->modelo }}) • S/N: {{ $equipo->serie }} • Cliente: {{ $equipo->cliente->nombre ?? 'N/A' }}
                </option>
            @endforeach
        </select>
        @error('equipo_id') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Técnico --}}
    <div class="min-w-0">
        <label class="field-label flex items-center gap-2"><span>👨🏻‍🔧</span> Técnico Asignado *</label>
        <select name="tecnico_id" required class="glass-input mt-1">
            <option value="">Seleccione un técnico...</option>
            @foreach($tecnicos as $t)
                <option value="{{ $t->id }}" {{ (old('tecnico_id', $electronica->tecnico_id ?? '') == $t->id) ? 'selected' : '' }}>
                    {{ $t->nombre }} ({{ $t->identificacion }})
                </option>
            @endforeach
        </select>
        @error('tecnico_id') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Estado --}}
    <div>
        <label class="field-label flex items-center gap-2"><span>🏷️</span> Estado Inicial *</label>
        <select name="estado" required class="glass-input no-search mt-1 font-bold {{ old('estado', $electronica->estado ?? 'pendiente') === 'terminado' ? 'text-emerald-600 dark:text-emerald-400' : 'text-yellow-600 dark:text-yellow-400' }}">
            <option value="pendiente" {{ old('estado', $electronica->estado ?? 'pendiente') === 'pendiente' ? 'selected' : '' }} class="text-yellow-600">⏳ Pendiente</option>
            <option value="terminado" {{ old('estado', $electronica->estado ?? '') === 'terminado' ? 'selected' : '' }} class="text-emerald-600">✅ Terminado</option>
        </select>
    </div>

    {{-- Fechas --}}
    <div class="grid grid-cols-2 gap-3 md:col-span-2">
        <div>
            <label class="field-label">Fecha de Entrada *</label>
            <input type="date" name="fecha_entrada" required value="{{ old('fecha_entrada', isset($electronica->fecha_entrada) ? \Carbon\Carbon::parse($electronica->fecha_entrada)->format('Y-m-d') : date('Y-m-d')) }}" class="glass-input mt-1">
            @error('fecha_entrada') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label flex justify-between">
                <span>Fecha de Salida</span>
                <span class="text-[10px] font-normal text-gray-400 normal-case">(Opcional)</span>
            </label>
            <input type="date" name="fecha_salida" value="{{ old('fecha_salida', isset($electronica->fecha_salida) ? \Carbon\Carbon::parse($electronica->fecha_salida)->format('Y-m-d') : '') }}" class="glass-input mt-1">
            @error('fecha_salida') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Tipo Trabajo --}}
    <div>
        <label class="field-label">Tipo de Trabajo *</label>
        <div class="flex gap-3 mt-1">
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('tipo', $electronica->tipo ?? 'preventivo') === 'preventivo' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}" id="lbl_prev_elec">
                <input type="radio" name="tipo" value="preventivo" {{ old('tipo', $electronica->tipo ?? 'preventivo') === 'preventivo' ? 'checked' : '' }} class="accent-emerald-500 w-4 h-4">
                <span class="font-bold {{ old('tipo', $electronica->tipo ?? 'preventivo') === 'preventivo' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400' }}">Preventivo</span>
            </label>
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('tipo', $electronica->tipo ?? '') === 'correctivo' ? 'border-orange-500 bg-orange-50/50 dark:bg-orange-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}" id="lbl_corr_elec">
                <input type="radio" name="tipo" value="correctivo" {{ old('tipo', $electronica->tipo ?? '') === 'correctivo' ? 'checked' : '' }} class="accent-orange-500 w-4 h-4">
                <span class="font-bold {{ old('tipo', $electronica->tipo ?? '') === 'correctivo' ? 'text-orange-700 dark:text-orange-400' : 'text-slate-600 dark:text-slate-400' }}">Correctivo</span>
            </label>
        </div>
    </div>

    {{-- Tipo Reparación --}}
    <div>
        <label class="field-label">Tipo de Reparación *</label>
        <div class="flex gap-3 mt-1">
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('reparacion', $electronica->reparacion ?? 'software') === 'software' ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}" id="lbl_soft_elec">
                <input type="radio" name="reparacion" value="software" {{ old('reparacion', $electronica->reparacion ?? 'software') === 'software' ? 'checked' : '' }} class="accent-indigo-500 w-4 h-4">
                <span class="font-bold {{ old('reparacion', $electronica->reparacion ?? 'software') === 'software' ? 'text-indigo-700 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-400' }}">Software</span>
            </label>
            <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all {{ old('reparacion', $electronica->reparacion ?? '') === 'hardware' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-900/20' : 'border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md' }}" id="lbl_hard_elec">
                <input type="radio" name="reparacion" value="hardware" {{ old('reparacion', $electronica->reparacion ?? '') === 'hardware' ? 'checked' : '' }} class="accent-rose-500 w-4 h-4">
                <span class="font-bold {{ old('reparacion', $electronica->reparacion ?? '') === 'hardware' ? 'text-rose-700 dark:text-rose-400' : 'text-slate-600 dark:text-slate-400' }}">Hardware</span>
            </label>
        </div>
    </div>

    {{-- Descripción --}}
    <div class="md:col-span-2">
        <label class="field-label">Descripción del problema / trabajo *</label>
        <textarea name="descripcion_problema" required rows="3" placeholder="Detalla los síntomas o las tareas a realizar..." class="glass-input mt-1 resize-y @error('descripcion_problema') border-red-500 @enderror">{{ old('descripcion_problema', $electronica->descripcion_problema ?? '') }}</textarea>
        @error('descripcion_problema') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Costo --}}
    <div class="md:col-span-2 p-4 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm mt-2">
        <label class="field-label text-center mb-2 block text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Costo Estimado / Final ($) *</label>
        <input type="text" name="costo_visual" id="costo_visual" value="{{ isset($electronica->costo) ? number_format(old('costo', $electronica->costo), 0, ',', '.') : '' }}" placeholder="0" required class="glass-input bg-white/50 dark:bg-slate-900/60 border-gray-200/50 dark:border-white/5 text-3xl font-black text-center py-3 text-emerald-600 dark:text-emerald-400 shadow-sm transition-all focus:ring-4 focus:ring-emerald-500/20">
        <input type="hidden" name="costo" id="costo_real" value="{{ old('costo', $electronica->costo ?? '') }}">
        @error('costo') <p class="text-red-500 text-xs font-bold mt-2 text-center">{{ $message }}</p> @enderror
    </div>

    @if(isset($electronica) && auth()->user()->isTecnico())
    <div class="md:col-span-2 mt-4">
        <label class="field-label flex items-center gap-2"><span>🔐</span> Contraseña de Administrador *</label>
        <input type="password" name="admin_password" class="glass-input mt-1" placeholder="Requerida para guardar cambios" autocomplete="off">
        <p class="text-xs text-gray-400 mt-1">Como técnico, debes confirmar con la contraseña de un administrador para editar.</p>
    </div>
    @endif

    <div class="md:col-span-2 flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
        <a href="{{ route('electronicas.index') }}" class="btn-cancel">↩️ Cancelar</a>
        <button type="submit" class="btn-save">
            {{ isset($electronica) ? '🔄 Actualizar Registro' : '💾 Guardar Registro' }}
        </button>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Formateador de monto
        const inputVisual = document.getElementById('costo_visual');
        const inputReal = document.getElementById('costo_real');

        if(inputVisual && inputReal) {
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
            
            const form = inputVisual.closest('form');
            if (form) {
                form.addEventListener('submit', function() {
                    if(inputReal.value === "") inputReal.value = 0;
                });
            }
        }

        // Manejo visual de radio buttons (Tipo Electrónica)
        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const lblCorr = document.getElementById('lbl_corr_elec');
                const lblPrev = document.getElementById('lbl_prev_elec');
                if(!lblCorr || !lblPrev) return;
                
                [lblCorr, lblPrev].forEach(lbl => {
                    lbl.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md';
                    lbl.querySelector('span').className = 'font-bold text-slate-600 dark:text-slate-400';
                });
                
                if (this.value === 'correctivo') {
                    lblCorr.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-orange-500 bg-orange-50/50 dark:bg-orange-900/20';
                    lblCorr.querySelector('span').className = 'font-bold text-orange-700 dark:text-orange-400';
                } else {
                    lblPrev.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20';
                    lblPrev.querySelector('span').className = 'font-bold text-emerald-700 dark:text-emerald-400';
                }
            });
        });

        // Manejo visual de radio buttons (Tipo Reparación)
        document.querySelectorAll('input[name="reparacion"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const lblHard = document.getElementById('lbl_hard_elec');
                const lblSoft = document.getElementById('lbl_soft_elec');
                if (!lblHard || !lblSoft) return;

                [lblHard, lblSoft].forEach(lbl => {
                    lbl.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md';
                    lbl.querySelector('span').className = 'font-bold text-slate-600 dark:text-slate-400';
                });
                
                if (this.value === 'hardware') {
                    lblHard.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-rose-500 bg-rose-50/50 dark:bg-rose-900/20';
                    lblHard.querySelector('span').className = 'font-bold text-rose-700 dark:text-rose-400';
                } else {
                    lblSoft.className = 'flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20';
                    lblSoft.querySelector('span').className = 'font-bold text-indigo-700 dark:text-indigo-400';
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
    });
</script>
