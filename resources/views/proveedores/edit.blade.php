@extends('layouts.app')
@section('content')
<div class="max-w-2xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('proveedores.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Proveedor</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza la información de {{ $proveedor->nombre_razon_social }}</p>
 </div>
 </div>
 <form action="{{ route('proveedores.update', $proveedor->id) }}" method="POST" class="space-y-6">
 @csrf @method('PUT')
 @include('proveedores._form')

 @if(auth()->user()->isAdmin())
 <div>
 <label class="field-label flex items-center gap-2">⚙️ Estado del Proveedor</label>
 <select name="active" class="glass-input no-search mt-1">
 <option value="1" {{ old('active', $proveedor->active) ? 'selected' : '' }}>✅ Activo</option>
 <option value="0" {{ !old('active', $proveedor->active) ? 'selected' : '' }}>🚫 Inactivo (deshabilitado)</option>
 </select>
 
 </div>
 @else
 <input type="hidden" name="active" value="{{ $proveedor->active ? 1 : 0 }}">
 @endif
 
 <div class="flex gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('proveedores.index') }}" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="flex-1 btn-primary justify-center py-3">
 🔄 Actualizar Proveedor
 </button>
 </div>
 </form>
 </div>
</div>
@endsection
