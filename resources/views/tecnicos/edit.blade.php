@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('tecnicos.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📝 Editar Técnico</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos del técnico registrado</p>
 </div>
 </div>
 
 <form method="POST" action="{{ route('tecnicos.update', $tecnico->id) }}" enctype="multipart/form-data" class="space-y-6">
 @csrf
 @method('PUT')
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div>
 <label class="field-label">Nombre Completo *</label>
 <input type="text" name="nombre" value="{{ old('nombre', $tecnico->nombre) }}" required class="glass-input mt-1 @error('nombre') border-red-500 @enderror">
 @error('nombre') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Identificación (DNI/NIT) *</label>
 <input type="text" name="identificacion" value="{{ old('identificacion', $tecnico->identificacion) }}" required class="glass-input mt-1 @error('identificacion') border-red-500 @enderror">
 @error('identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Especialidad *</label>
 <select name="especialidad" required class="glass-input mt-1 text-sm font-bold">
 <option value="Hardware" {{ old('especialidad', $tecnico->especialidad) == 'Hardware' ? 'selected' : '' }}>Hardware</option>
 <option value="Software" {{ old('especialidad', $tecnico->especialidad) == 'Software' ? 'selected' : '' }}>Software</option>
 <option value="Electrónica" {{ old('especialidad', $tecnico->especialidad) == 'Electrónica' ? 'selected' : '' }}>Electrónica</option>
 <option value="Redes" {{ old('especialidad', $tecnico->especialidad) == 'Redes' ? 'selected' : '' }}>Redes</option>
 <option value="General" {{ old('especialidad', $tecnico->especialidad) == 'General' ? 'selected' : '' }}>General</option>
 </select>
 @error('especialidad') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="field-label">Teléfono Móvil *</label>
 <input type="tel" pattern="[\d\+\-\s\(\)]+" name="movil" value="{{ old('movil', $tecnico->movil) }}" required class="glass-input mt-1 @error('movil') border-red-500 @enderror">
 @error('movil') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div class="md:col-span-2">
 <label class="field-label">Email</label>
 <input type="email" name="email" value="{{ old('email', $tecnico->email) }}" class="glass-input mt-1 @error('email') border-red-500 @enderror">
 @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>
 </div>

 <div>
 <label class="field-label">Dirección</label>
 <textarea name="direccion" rows="3" class="glass-input mt-1 resize-y">{{ old('direccion', $tecnico->direccion) }}</textarea>
 </div>

 <div>
 <label class="field-label">Foto del Técnico</label>
 @if($tecnico->photo)
 <div class="mb-3">
 <img src="{{ asset('storage/' . $tecnico->photo) }}" width="100" height="100" class="rounded-xl object-cover border-2 border-gray-300 dark:border-gray-600 shadow-md">
 </div>
 @endif
 <input type="file" name="photo" accept="image/*" class="glass-input mt-1 @error('photo') border-red-500 @enderror">
 <p class="text-xs text-gray-500 mt-1">Sube una nueva imagen para actualizar la foto.</p>
 @error('photo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 @if(auth()->user()->isAdmin())
 <div>
 <label class="field-label flex items-center gap-2">⚙️ Estado del Técnico</label>
 <select name="active" class="glass-input mt-1">
 <option value="1" {{ old('active', $tecnico->active) ? 'selected' : '' }}>✅ Activo</option>
 <option value="0" {{ !old('active', $tecnico->active) ? 'selected' : '' }}>🚫 Inactivo (deshabilitado)</option>
 </select>
 
 </div>
 @else
 <input type="hidden" name="active" value="{{ $tecnico->active ? 1 : 0 }}">
 @endif

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('tecnicos.index') }}" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="btn-save">
 💾 Actualizar Técnico
 </button>
 </div>
 </form>
 </div>
</div>
@endsection
