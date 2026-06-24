@extends('layouts.app')
@section('content')
<div class="max-w-3xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('proveedores.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">🏭 Nuevo Proveedor</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Registra una nueva persona o empresa</p>
 </div>
 </div>

 <form action="{{ route('proveedores.store') }}" method="POST" class="space-y-6">
 @csrf
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <!-- Tipo de Entidad (Toggle Custom) -->
 <div class="md:col-span-2">
 <label class="field-label mb-2">Tipo de Entidad *</label>
 <div class="entity-toggle" id="tipo_toggle_container">
 <label class="entity-tab {{ old('tipo_entidad', 'persona') === 'persona' ? 'active' : '' }}">
 <input type="radio" name="tipo_entidad" value="persona" class="hidden" {{ old('tipo_entidad', 'persona') === 'persona' ? 'checked' : '' }} onchange="updateToggle(this)">
 <span>👤 Persona Natural</span>
 </label>
 <label class="entity-tab {{ old('tipo_entidad') === 'empresa' ? 'active' : '' }}">
 <input type="radio" name="tipo_entidad" value="empresa" class="hidden" {{ old('tipo_entidad') === 'empresa' ? 'checked' : '' }} onchange="updateToggle(this)">
 <span>🏢 Empresa</span>
 </label>
 </div>
 @error('tipo_entidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Identificación -->
 <div>
 <label class="field-label">Identificación (NIT / Cédula) *</label>
 <input type="text" name="identificacion" required value="{{ old('identificacion') }}" oninput="this.value = this.value.replace(/[^0-9-]/g, '')" placeholder="Ej: 900123456-7" class="glass-input">
 @error('identificacion') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Nombre -->
 <div class="md:col-span-2">
 <label class="field-label">Nombre / Razón Social *</label>
 <input type="text" name="nombre_razon_social" required value="{{ old('nombre_razon_social') }}" placeholder="Ej: Distribuciones ABC S.A.S." class="glass-input">
 @error('nombre_razon_social') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Teléfono -->
 <div>
 <label class="field-label">Teléfono</label>
 <input type="text" name="telefono" value="{{ old('telefono') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="Ej: 3001234567" class="glass-input">
 @error('telefono') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Email -->
 <div>
 <label class="field-label">Email</label>
 <input type="email" name="email" value="{{ old('email') }}" placeholder="proveedor@empresa.com" class="glass-input">
 @error('email') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Dirección -->
 <div class="md:col-span-2">
 <label class="field-label">Dirección</label>
 <input type="text" name="direccion" value="{{ old('direccion') }}" placeholder="Ej: Calle 45 #12-34, Bogotá" class="glass-input">
 @error('direccion') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Notas -->
 <div class="md:col-span-2">
 <label class="field-label">Notas / Condiciones comerciales</label>
 <textarea name="notas" rows="2" placeholder="Ej: Crédito a 30 días, descuento del 5%..." class="glass-input resize-y">{{ old('notas') }}</textarea>
 </div>
 </div>

 <div class="flex gap-3 pt-4 border-t border-gray-200/50 dark:border-white/10">
 <a href="{{ route('proveedores.index') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">
 💾 Guardar Proveedor
 </button>
 </div>
 </form>
 </div>
</div>

<script>
 function updateToggle(radio) {
 document.querySelectorAll('.entity-tab').forEach(tab => tab.classList.remove('active'));
 if(radio.checked) {
 radio.closest('.entity-tab').classList.add('active');
 }
 }
</script>
@endsection
