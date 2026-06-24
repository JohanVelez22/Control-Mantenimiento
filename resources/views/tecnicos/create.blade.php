@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('tecnicos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">👨‍🔧 Registrar Nuevo Técnico</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Añade un nuevo técnico al sistema</p>
 </div>
 </div>
 
 <form method="POST" action="{{ route('tecnicos.store') }}" enctype="multipart/form-data" class="space-y-6">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div>
 <label class="field-label">Nombre Completo *</label>
 <input type="text" name="nombre" value="{{ old('nombre') }}" required class="glass-input mt-1 @error('nombre') border-red-500 @enderror">
 @error('nombre') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Identificación (DNI/NIT) *</label>
 <input type="text" name="identificacion" value="{{ old('identificacion') }}" required class="glass-input mt-1 @error('identificacion') border-red-500 @enderror">
 @error('identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Especialidad *</label>
 <select name="especialidad" required class="glass-input no-search mt-1 text-sm font-bold">
 <option value=\"\"></option>
 <option value="Hardware" {{ old('especialidad') == 'Hardware' ? 'selected' : '' }}>Hardware</option>
 <option value="Software" {{ old('especialidad') == 'Software' ? 'selected' : '' }}>Software</option>
 <option value="Electrónica" {{ old('especialidad') == 'Electrónica' ? 'selected' : '' }}>Electrónica</option>
 <option value="Redes" {{ old('especialidad') == 'Redes' ? 'selected' : '' }}>Redes</option>
 <option value="General" {{ old('especialidad') == 'General' ? 'selected' : '' }}>General</option>
 </select>
 @error('especialidad') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Teléfono Móvil *</label>
 <input type="tel" pattern="[\d\+\-\s\(\)]+" name="movil" value="{{ old('movil') }}" required class="glass-input mt-1 @error('movil') border-red-500 @enderror">
 @error('movil') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div class="md:col-span-2">
 <label class="field-label">Email</label>
 <input type="email" name="email" value="{{ old('email') }}" class="glass-input mt-1 @error('email') border-red-500 @enderror">
 @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>
 </div>

 <div>
 <label class="field-label">Dirección</label>
 <textarea name="direccion" rows="3" class="glass-input mt-1 resize-y">{{ old('direccion') }}</textarea>
 </div>

 <div>
 <label class="field-label">Foto del Técnico (Opcional)</label>
 <input type="file" name="photo" accept="image/*" class="glass-input mt-1 @error('photo') border-red-500 @enderror">
 @error('photo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('tecnicos.index') }}" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="btn-save">
 💾 Guardar Técnico
 </button>
 </div>
 </form>
 </div>
</div>
@endsection
