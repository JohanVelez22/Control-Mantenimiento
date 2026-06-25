<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
 <!-- Código -->
 <div>
 <label for="codigo" class="field-label">Código (Opcional)</label>
 <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $stock->codigo ?? '') }}" class="glass-input font-mono">
 @error('codigo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Producto -->
 <div>
 <label for="producto" class="field-label">Nombre del Producto *</label>
 <input type="text" name="producto" id="producto" value="{{ old('producto', $stock->producto ?? '') }}" required class="glass-input">
 @error('producto') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Cantidad -->
 <div>
 <label for="cantidad" class="field-label">Cantidad Inicial *</label>
 <input type="number" name="cantidad" id="cantidad" value="{{ old('cantidad', $stock->cantidad ?? 0) }}" required min="0" class="glass-input font-bold text-blue-600 dark:text-blue-400">
 @error('cantidad') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Proveedor -->
 <div class="min-w-0">
 <label for="proveedor_id" class="field-label">Proveedor *</label>
 <select name="proveedor_id" id="proveedor_id" required class="glass-input font-bold">
 <option value="">Seleccionar...</option>
 @foreach($proveedores as $p)
 <option value="{{ $p->id }}" {{ old('proveedor_id', $stock->proveedor_id ?? '') == $p->id ? 'selected' : '' }}>
 🏢 Proveedor: {{ $p->nombre_razon_social }} ({{ $p->identificacion }})
 </option>
 @endforeach
 </select>
 @error('proveedor_id') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Precio Compra -->
 <div>
 <label for="precio_compra_visual" class="field-label">Precio Compra ($) *</label>
 <input type="text" id="precio_compra_visual" value="{{ old('precio_compra', isset($stock) ? number_format($stock->precio_compra, 0, '', '') : 0) }}" required class="glass-input font-mono font-bold text-orange-600 dark:text-orange-400">
 <input type="hidden" name="precio_compra" id="precio_compra_real" value="{{ old('precio_compra', isset($stock) ? intval($stock->precio_compra) : 0) }}">
 @error('precio_compra') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Utilidad -->
 <div>
 <label for="utilidad" class="field-label">Utilidad (%) *</label>
 <input type="number" step="0.01" name="utilidad" id="utilidad" value="{{ old('utilidad', $stock->utilidad ?? 30) }}" required min="0" class="glass-input font-bold text-emerald-600 dark:text-emerald-400">
 @error('utilidad') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Precio Venta -->
 <div>
 <label for="precio_venta_visual" class="field-label">Precio Venta (Manual) ($)</label>
 <input type="text" id="precio_venta_visual" value="{{ old('precio_venta', isset($stock) && $stock->precio_venta ? number_format($stock->precio_venta, 0, '', '') : '') }}" placeholder="Automático si vacío" class="glass-input font-mono">
 <input type="hidden" name="precio_venta" id="precio_venta_real" value="{{ old('precio_venta', isset($stock) && $stock->precio_venta ? intval($stock->precio_venta) : '') }}">
 @error('precio_venta') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 <!-- Precio Técnico -->
 <div>
 <label for="precio_tecnico_visual" class="field-label">Precio Técnico (Manual) ($)</label>
 <input type="text" id="precio_tecnico_visual" value="{{ old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? number_format($stock->precio_tecnico, 0, '', '') : '') }}" placeholder="Automático si vacío" class="glass-input font-mono">
 <input type="hidden" name="precio_tecnico" id="precio_tecnico_real" value="{{ old('precio_tecnico', isset($stock) && $stock->precio_tecnico ? intval($stock->precio_tecnico) : '') }}">
 @error('precio_tecnico') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>
</div>

<script>
 // Formateador de monto
 function formatInput(visualId, realId) {
 const inputVisual = document.getElementById(visualId);
 const inputReal = document.getElementById(realId);

 if(!inputVisual || !inputReal) return;

 // Init visual
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
 }

 formatInput('precio_compra_visual', 'precio_compra_real');
 formatInput('precio_venta_visual', 'precio_venta_real');
 formatInput('precio_tecnico_visual', 'precio_tecnico_real');
</script>

