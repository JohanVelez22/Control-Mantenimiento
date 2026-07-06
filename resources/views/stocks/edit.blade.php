@extends('layouts.app')

@section('content')
<style>
/* Altura uniforme de 42px para todos los inputs y selectores (incluyendo TomSelect) */
select.glass-input,
.ts-wrapper.glass-input .ts-control,
select.glass-input + .ts-wrapper .ts-control,
input[type="number"].glass-input,
input[type="text"].glass-input {
  height: 42px !important;
  font-size: 14px !important;
}
/* Panel de precios: siempre visible, nunca animado, nunca transparente */
.pricing-panel,
.pricing-panel * {
  opacity: 1 !important;
  visibility: visible !important;
  animation: none !important;
}
</style>

<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('stocks.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Producto: {{ $stock->producto }}</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos del producto o repuesto</p>
 </div>
 </div>

 <form action="{{ route('stocks.update', $stock->id) }}" method="POST" class="space-y-6">
 @csrf
 @method('PUT')

 <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <div>
 <label for="codigo" class="field-label">Código (Opcional)</label>
 <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $stock->codigo ?? '') }}"
  oninput="this.value = this.value.toUpperCase()" class="glass-input" placeholder="Ej: REF-001">
 @error('codigo') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <div>
 <label for="producto" class="field-label">Nombre del Producto *</label>
 <input type="text" name="producto" id="producto" value="{{ old('producto', $stock->producto ?? '') }}"
  required class="glass-input" placeholder="Ej: Disco Duro SSD 1TB">
 @error('producto') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <div>
 <label for="categoria" class="field-label">Categoría *</label>
 @php
     $categorias = \App\Models\CategoriaStock::where('tipo', 'categoria')->pluck('nombre');
     $subcategorias = \App\Models\CategoriaStock::where('tipo', 'subcategoria')->pluck('nombre');
 @endphp
 <select name="categoria" id="categoria" required class="glass-input no-search">
    <option value="">Seleccione una categoría...</option>
    @foreach($categorias as $cat)
        <option value="{{ $cat }}" {{ old('categoria', $stock->categoria ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
    @endforeach
 </select>
 @error('categoria') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label for="subcategoria" class="field-label">Subcategoría *</label>
 <select name="subcategoria" id="subcategoria" required class="glass-input no-search">
    <option value="">Seleccione una subcategoría...</option>
    @foreach($subcategorias as $subcat)
        <option value="{{ $subcat }}" {{ old('subcategoria', $stock->subcategoria ?? '') == $subcat ? 'selected' : '' }}>{{ $subcat }}</option>
    @endforeach
 </select>
 @error('subcategoria') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <div>
 <label for="cantidad" class="field-label">Cantidad Actual *</label>
 <input type="number" name="cantidad" id="cantidad"
  value="{{ old('cantidad', $stock->cantidad ?? 0) }}"
  required min="0" class="glass-input font-bold dark:[color-scheme:dark]">
 @error('cantidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>

 <div>
 <label for="proveedor_id" class="field-label">Proveedor *</label>
 <select name="proveedor_id" id="proveedor_id" required class="glass-input no-search">
 <option value="">Seleccione un proveedor...</option>
 @foreach($proveedores as $proveedor)
 <option value="{{ $proveedor->id }}" {{ old('proveedor_id', $stock->proveedor_id ?? '') == $proveedor->id ? 'selected' : '' }}>
 {{ $proveedor->nombre_razon_social }} ({{ $proveedor->identificacion }})
 </option>
 @endforeach
 </select>
 @error('proveedor_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 </div>

 {{-- Panel de Precios: siempre visible, fuera del grid --}}
 <div class="pricing-panel p-5 bg-white/45 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl shadow-sm">
 <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
 <div>
 <label for="precio_compra_visual" class="field-label">P. Compra ($) *</label>
 <input type="text" id="precio_compra_visual"
  value="{{ old('precio_compra', isset($stock) && $stock->precio_compra ? number_format($stock->precio_compra, 0, '', '') : '') }}"
  required class="glass-input text-right font-bold text-slate-800 dark:text-white" placeholder="0">
 <input type="hidden" name="precio_compra" id="precio_compra_real"
  value="{{ old('precio_compra', isset($stock) ? intval($stock->precio_compra) : '') }}">
 @error('precio_compra') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 <div>
 <label for="utilidad" class="field-label">Utilidad (%) *</label>
 <div class="relative">
 <input type="number" step="0.01" name="utilidad" id="utilidad"
  value="{{ old('utilidad', $stock->utilidad ?? 30) }}" required min="0"
  class="glass-input pr-8 text-right font-bold text-emerald-600 dark:text-emerald-400 dark:[color-scheme:dark]">
 <span class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-600 dark:text-emerald-400 font-bold text-sm pointer-events-none">%</span>
 </div>
 @error('utilidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 <div>
 <label for="precio_venta_visual" class="field-label">P. Venta (Manual)</label>
 <input type="text" id="precio_venta_visual"
  value="{{ old('precio_venta', isset($stock) && $stock->precio_venta ? number_format($stock->precio_venta, 0, '', '') : '') }}"
  placeholder="Auto si vacío" class="glass-input text-right font-bold text-blue-600 dark:text-cyan-400">
 <input type="hidden" name="precio_venta" id="precio_venta_real"
  value="{{ old('precio_venta', isset($stock) && $stock->precio_venta ? intval($stock->precio_venta) : '') }}">
 @error('precio_venta') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 <div>
 <label for="precio_tecnico_visual" class="field-label">P. Técnico (Manual)</label>
 <input type="text" id="precio_tecnico_visual"
  value="{{ old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? number_format($stock->precio_tecnico, 0, '', '') : '') }}"
  placeholder="Auto si vacío" class="glass-input text-right font-bold text-purple-600 dark:text-purple-400">
 <input type="hidden" name="precio_tecnico" id="precio_tecnico_real"
  value="{{ old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? intval($stock->precio_tecnico) : '') }}">
 @error('precio_tecnico') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 </div>
 <p class="text-[10px] text-gray-500 mt-3 font-medium">Si dejas P. Venta y P. Técnico vacíos, se recalcularán automáticamente al guardar basados en el Precio de Compra y la Utilidad actual.</p>
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('stocks.index') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">🔄 Actualizar Producto</button>
 </div>
 </form>
 </div>
</div>

<script>
(function () {
 function formatInput(visualId, realId) {
  var vis = document.getElementById(visualId);
  var real = document.getElementById(realId);
  if (!vis || !real) return;
  if (real.value && real.value !== '0' && real.value !== '') {
   vis.value = new Intl.NumberFormat('es-CO').format(parseInt(real.value, 10));
  }
  vis.addEventListener('input', function (e) {
   var raw = e.target.value.replace(/\D/g, '');
   real.value = raw;
   e.target.value = raw ? new Intl.NumberFormat('es-CO').format(parseInt(raw, 10)) : '';
  });
 }
 formatInput('precio_compra_visual', 'precio_compra_real');
 formatInput('precio_venta_visual', 'precio_venta_real');
 formatInput('precio_tecnico_visual', 'precio_tecnico_real');
})();
</script>
@endsection
