@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('equipos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">💻 Registrar Nuevo Equipo</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Añade un nuevo dispositivo al inventario del cliente</p>
 </div>
 </div>
 
 <form method="POST" action="{{ route('equipos.store') }}" class="space-y-6">
 @csrf
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div class="md:col-span-2">
 <label class="field-label flex items-center gap-2"><span>👤</span> Cliente Propietario *</label>
 <select name="cliente_id" required class="glass-input mt-1 text-sm font-bold">
 <option value=\"\">Seleccione un cliente...</option>
 @foreach($clientes as $cliente)
 <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
 {{ $cliente->nombre }} ({{ $cliente->identificacion }})
 </option>
 @endforeach
 </select>
 </div>

 <div>
 <label class="field-label">Nombre del Equipo *</label>
 <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej. PC Escritorio" class="glass-input mt-1 @error('nombre') border-red-500 @enderror">
 @error('nombre') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Marca *</label>
 <input type="text" name="marca" value="{{ old('marca') }}" required placeholder="Ej. HP, Dell" class="glass-input mt-1 @error('marca') border-red-500 @enderror">
 @error('marca') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Modelo *</label>
 <input type="text" name="modelo" value="{{ old('modelo') }}" required placeholder="Ej. ProDesk 400" class="glass-input mt-1 @error('modelo') border-red-500 @enderror">
 @error('modelo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Número de Serie *</label>
 <input type="text" name="serie" value="{{ old('serie') }}" required placeholder="S/N..." oninput="this.value = this.value.toUpperCase()" class="glass-input mt-1 uppercase @error('serie') border-red-500 @enderror">
 @error('serie') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>
 </div>

 <div>
 <label class="field-label flex items-center gap-2"><span>📝</span> Observaciones / Detalles</label>
 <textarea name="observacion" rows="3" class="glass-input mt-1 resize-y" placeholder="Cargador original, rayón en la tapa, etc...">{{ old('observacion') }}</textarea>
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('equipos.index') }}" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="btn-save">
 💾 Guardar Equipo
 </button>
 </div>
 </form>
 </div>
</div>
@endsection

