<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Código -->
    <div>
        <label for="codigo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Código (Opcional)</label>
        <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $stock->codigo ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('codigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Producto -->
    <div>
        <label for="producto" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre del Producto *</label>
        <input type="text" name="producto" id="producto" value="{{ old('producto', $stock->producto ?? '') }}" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('producto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Cantidad -->
    <div>
        <label for="cantidad" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cantidad Inicial *</label>
        <input type="number" name="cantidad" id="cantidad" value="{{ old('cantidad', $stock->cantidad ?? 0) }}" required min="0" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Proveedor -->
    <div>
        <label for="proveedor" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Proveedor (Opcional)</label>
        <input type="text" name="proveedor" id="proveedor" value="{{ old('proveedor', $stock->proveedor ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('proveedor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Precio Compra -->
    <div>
        <label for="precio_compra" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Precio Compra ($) *</label>
        <input type="number" step="0.01" name="precio_compra" id="precio_compra" value="{{ old('precio_compra', $stock->precio_compra ?? 0) }}" required min="0" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('precio_compra') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Utilidad -->
    <div>
        <label for="utilidad" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Utilidad (%) *</label>
        <input type="number" step="0.01" name="utilidad" id="utilidad" value="{{ old('utilidad', $stock->utilidad ?? 30) }}" required min="0" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('utilidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Precio Venta -->
    <div>
        <label for="precio_venta" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Precio Venta (Manual) ($)</label>
        <input type="number" step="0.01" name="precio_venta" id="precio_venta" value="{{ old('precio_venta', $stock->precio_venta ?? '') }}" min="0" placeholder="Se calcula auto si se deja vacío" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('precio_venta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Precio Técnico -->
    <div>
        <label for="precio_tecnico" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Precio Técnico (Manual) ($)</label>
        <input type="number" step="0.01" name="precio_tecnico" id="precio_tecnico" value="{{ old('precio_tecnico', $stock->precio_tecnico ?? '') }}" min="0" placeholder="Se calcula auto si se deja vacío" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        @error('precio_tecnico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
