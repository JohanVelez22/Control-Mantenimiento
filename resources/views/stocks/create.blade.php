@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('stocks.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📦 Agregar Producto al Stock</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Registra nuevos repuestos y artículos en el inventario</p>
 </div>
 </div>

 <form action="{{ route('stocks.store') }}" method="POST" class="space-y-6">
 @csrf
 
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <!-- Código -->
 <div>
 <label for="codigo" class="field-label">Código (Opcional)</label>
 <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $stock->codigo ?? '') }}" oninput="this.value = this.value.toUpperCase()" class="glass-input placeholder-gray-400" placeholder="Ej: REF-001">
 @error('codigo') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Producto -->
 <div>
 <label for="producto" class="field-label">Nombre del Producto *</label>
 <input type="text" name="producto" id="producto" value="{{ old('producto', $stock->producto ?? '') }}" required class="glass-input placeholder-gray-400" placeholder="Ej: Disco Duro SSD 1TB">
 @error('producto') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Cantidad -->
 <div>
 <label for="cantidad" class="field-label">Cantidad Inicial *</label>
 <input type="number" name="cantidad" id="cantidad" value="{{ old('cantidad', $stock->cantidad ?? 0) }}" required min="0" class="glass-input font-bold text-lg dark:[color-scheme:dark]">
 @error('cantidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Proveedor -->
 <div>
 <label for="proveedor_id" class="field-label">Proveedor *</label>
 <select name="proveedor_id" id="proveedor_id" required class="glass-input text-sm font-bold">
 <option value=\"\">Seleccione un proveedor...</option>
 @foreach($proveedores as $proveedor)
 <option value="{{ $proveedor->id }}" {{ old('proveedor_id', $stock->proveedor_id ?? '') == $proveedor->id ? 'selected' : '' }}>
 {{ $proveedor->nombre_razon_social }} ({{ $proveedor->identificacion }})
 </option>
 @endforeach
 </select>
 @error('proveedor_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Precio Compra -->
 <div class="p-4 bg-gray-50/50 dark:bg-gray-800/30 rounded-xl border border-gray-200/50 dark:border-white/5 md:col-span-2">
 <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
 <div>
 <label for="precio_compra_visual" class="field-label">P. Compra ($) *</label>
 <input type="text" id="precio_compra_visual" value="{{ old('precio_compra', isset($stock) ? number_format($stock->precio_compra, 0, '', '') : 0) }}" required class="glass-input text-right font-bold text-slate-800 dark:text-white">
 <input type="hidden" name="precio_compra" id="precio_compra_real" value="{{ old('precio_compra', isset($stock) ? intval($stock->precio_compra) : 0) }}">
 @error('precio_compra') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Utilidad -->
 <div>
 <label for="utilidad" class="field-label">Utilidad (%) *</label>
 <div class="relative">
 <input type="number" step="0.01" name="utilidad" id="utilidad" value="{{ old('utilidad', $stock->utilidad ?? 30) }}" required min="0" class="glass-input pr-8 text-right font-bold text-emerald-600 dark:text-emerald-400 dark:[color-scheme:dark]">
 <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-emerald-600 dark:text-emerald-400 font-bold">%</span>
 </div>
 @error('utilidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Precio Venta -->
 <div>
 <label for="precio_venta_visual" class="field-label">P. Venta (Manual)</label>
 <input type="text" id="precio_venta_visual" value="{{ old('precio_venta', isset($stock) && $stock->precio_venta ? number_format($stock->precio_venta, 0, '', '') : '') }}" class="glass-input text-right font-bold text-blue-600 dark:text-cyan-400">
 <input type="hidden" name="precio_venta" id="precio_venta_real" value="{{ old('precio_venta', isset($stock) && $stock->precio_venta ? intval($stock->precio_venta) : '') }}">
 @error('precio_venta') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <!-- Precio Técnico -->
 <div>
 <label for="precio_tecnico_visual" class="field-label">P. Técnico (Manual)</label>
 <input type="text" id="precio_tecnico_visual" value="{{ old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? number_format($stock->precio_tecnico, 0, '', '') : '') }}" class="glass-input text-right font-bold text-purple-600 dark:text-purple-400">
 <input type="hidden" name="precio_tecnico" id="precio_tecnico_real" value="{{ old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? intval($stock->precio_tecnico) : '') }}">
 @error('precio_tecnico') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 </div>
 <p class="text-[10px] text-gray-500 mt-2 font-medium">Si dejas P. Venta y P. Técnico vacíos, se calcularán automáticamente basados en el Precio de Compra y la Utilidad (% P. Venta, %/2 P. Técnico).</p>
 </div>
 </div>
 
 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('stocks.index') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">
 💾 Guardar Producto
 </button>
 </div>
 </form>
 </div>
</div>

<script>
 // Formateador de monto
 function formatInput(visualId, realId) {
 const inputVisual = document.getElementById(visualId);
 const inputReal = document.getElementById(realId);

 if(!inputVisual || !inputReal) return;

 // Init visual
 if (inputReal.value && inputReal.value != 0) {
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
 }

 formatInput('precio_compra_visual', 'precio_compra_real');
 formatInput('precio_venta_visual', 'precio_venta_real');
 formatInput('precio_tecnico_visual', 'precio_tecnico_real');
</script>
@endsection
