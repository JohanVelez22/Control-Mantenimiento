@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('mantenimientos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">➕ Registrar Mantenimiento</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Ingresa los datos de la nueva orden de servicio</p>
 </div>
 </div>

 <form method="POST" action="{{ route('mantenimientos.store') }}" id="mantenimientoForm" class="space-y-6">
 @csrf
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

 {{-- N° Orden (Consecutivo automático) --}}
 <div>
 <label class="field-label flex items-center gap-2"><span>🔖</span> N° Orden</label>
 <input type="text" name="id_orden" value="{{ old('id_orden', $nextOrden) }}" readonly
 class="glass-input mt-1 bg-gray-200/50 dark:bg-gray-800/50 cursor-not-allowed text-gray-500 dark:text-gray-400 font-bold font-mono"
 title="El consecutivo se genera automáticamente">
 </div>

 {{-- Técnico --}}
 <div class="min-w-0">
 <label class="field-label flex items-center gap-2"><span>👨‍🔧</span> Técnico Asignado *</label>
 <select name="tecnico_id" required class="glass-input mt-1">
 <option value=\"\">Seleccione un técnico...</option>
 @foreach($tecnicos as $tecnico)
 <option value="{{ $tecnico->id }}">{{ $tecnico->nombre }} ({{ $tecnico->identificacion }})</option>
 @endforeach
 </select>
 </div>

 {{-- Equipo --}}
 <div class="md:col-span-2 min-w-0 p-4 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl">
 <label class="field-label flex items-center gap-2"><span>💻</span> Seleccionar Equipo *</label>
 <select name="equipo_id" required class="glass-input text-sm font-bold mt-1">
 <option value=\"\">Seleccione un equipo...</option>
 @foreach($equipos as $equipo)
 <option value="{{ $equipo->id }}">{{ $equipo->nombre }} ({{ $equipo->marca }} {{ $equipo->modelo }}) • S/N: {{ $equipo->serie }} • Cliente: {{ $equipo->cliente->nombre ?? 'N/A' }}</option>
 @endforeach
 </select>
 </div>

 {{-- Estado --}}
 <div>
 <label class="field-label flex items-center gap-2"><span>🏷️</span> Estado Inicial *</label>
 <select name="estado" required class="glass-input no-search mt-1 font-bold text-yellow-600 dark:text-yellow-400">
 <option value="pendiente" selected>⏳ Pendiente</option>
 <option value="terminado" class="text-emerald-600">✅ Terminado</option>
 </select>
 </div>

 {{-- Fechas --}}
 <div class="grid grid-cols-2 gap-3 md:col-span-2">
 <div>
 <label class="field-label">Fecha de Entrada *</label>
 <input type="date" name="fecha_entrada" value="{{ date('Y-m-d') }}" required class="glass-input mt-1">
 </div>
 <div>
 <label class="field-label flex justify-between">
 <span>Fecha de Salida</span>
 <span class="text-[10px] font-normal text-gray-400 normal-case">(Opcional)</span>
 </label>
 <input type="date" name="fecha_salida" class="glass-input mt-1">
 </div>
 </div>

 {{-- Tipo Mantenimiento --}}
 <div>
 <label class="field-label">Tipo de Mantenimiento *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20" id="lbl_prev">
 <input type="radio" name="tipo" value="preventivo" checked class="accent-emerald-500 w-4 h-4">
 <span class="font-bold text-emerald-700 dark:text-emerald-400">Preventivo</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md" id="lbl_corr">
 <input type="radio" name="tipo" value="correctivo" class="accent-orange-500 w-4 h-4">
 <span class="font-bold text-slate-600 dark:text-slate-400">Correctivo</span>
 </label>
 </div>
 </div>

 {{-- Tipo Reparación --}}
 <div>
 <label class="field-label">Tipo de Reparación *</label>
 <div class="flex gap-3 mt-1">
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20" id="lbl_soft">
 <input type="radio" name="reparacion" value="software" checked class="accent-indigo-500 w-4 h-4">
 <span class="font-bold text-indigo-700 dark:text-indigo-400">Software</span>
 </label>
 <label class="flex-1 flex justify-center items-center gap-2 p-3 rounded-xl border-2 cursor-pointer transition-all border-gray-200/50 dark:border-white/10 bg-white/30 dark:bg-slate-800/30 backdrop-blur-md" id="lbl_hard">
 <input type="radio" name="reparacion" value="hardware" class="accent-rose-500 w-4 h-4">
 <span class="font-bold text-slate-600 dark:text-slate-400">Hardware</span>
 </label>
 </div>
 </div>

 {{-- Costo --}}
 <div class="md:col-span-2 p-4 bg-gray-50/50 dark:bg-gray-800/50 rounded-2xl border border-gray-200/50 dark:border-gray-700">
 <label class="field-label text-center mb-2 block text-sm">Costo Estimado / Final ($) *</label>
 <input type="text" name="costo_visual" id="costo_visual" placeholder="0" required class="glass-input text-3xl font-black text-center py-4 text-blue-600 dark:text-blue-400 bg-white dark:bg-gray-900">
 <input type="hidden" name="costo" id="costo_real">
 </div>

 {{-- Descripción --}}
 <div class="md:col-span-2">
 <label class="field-label">Descripción del problema / trabajo *</label>
 <textarea name="descripcion" required rows="3" placeholder="Detalla los síntomas o las tareas a realizar..." class="glass-input mt-1 resize-y @error('descripcion') border-red-500 @enderror">{{ old('descripcion') }}</textarea>
 @error('descripcion') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
 </div>
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('mantenimientos.index') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">
 💾 Guardar Mantenimiento
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
@endsection
