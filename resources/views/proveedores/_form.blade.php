{{-- Formulario reutilizable para crear/editar proveedor --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Entidad *</label>
 <select name="tipo_entidad" required
 class="glass-input w-full">
 <option value="persona" {{ old('tipo_entidad', $proveedor->tipo_entidad ?? 'persona') === 'persona' ? 'selected' : '' }}>👤 Persona Natural</option>
 <option value="empresa" {{ old('tipo_entidad', $proveedor->tipo_entidad ?? '') === 'empresa' ? 'selected' : '' }}>🏢 Empresa</option>
 </select>
 @error('tipo_entidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Identificación (NIT / Cédula) *</label>
 <input type="text" name="identificacion" required
 value="{{ old('identificacion', $proveedor->identificacion ?? '') }}"
 placeholder="Ej: 900123456-7"
 class="glass-input w-full">
 @error('identificacion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
 </div>

 <div class="md:col-span-2">
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre / Razón Social *</label>
 <input type="text" name="nombre_razon_social" required
 value="{{ old('nombre_razon_social', $proveedor->nombre_razon_social ?? '') }}"
 placeholder="Ej: Distribuciones ABC S.A.S."
 class="glass-input w-full">
 @error('nombre_razon_social') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
 <input type="text" name="telefono"
 value="{{ old('telefono', $proveedor->telefono ?? '') }}"
 placeholder="Ej: 3001234567"
 class="glass-input w-full">
 @error('telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Email</label>
 <input type="email" name="email"
 value="{{ old('email', $proveedor->email ?? '') }}"
 placeholder="proveedor@empresa.com"
 class="glass-input w-full">
 @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
 </div>

 <div class="md:col-span-2">
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Dirección</label>
 <textarea name="direccion" rows="2"
 class="glass-input w-full"
 placeholder="Ej: Calle 45 #12-34, Bogotá">{{ old('direccion', $proveedor->direccion ?? '') }}</textarea>
 @error('direccion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
 </div>

 <div class="md:col-span-2">
 <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notas / Condiciones comerciales</label>
 <textarea name="notas" rows="2"
 class="glass-input w-full"
 placeholder="Ej: Crédito a 30 días, descuento del 5%...">{{ old('notas', $proveedor->notas ?? '') }}</textarea>
 </div>
</div>
