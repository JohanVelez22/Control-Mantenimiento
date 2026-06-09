<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">N° Orden (auto si vacío)</label>
        <input type="text" name="id_orden" value="{{ old('id_orden', $electronica->id_orden ?? $nextOrden ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('id_orden') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cliente *</label>
        <input type="text" name="cliente" required value="{{ old('cliente', $electronica->cliente ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('cliente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Dispositivo *</label>
        <input type="text" name="dispositivo" required value="{{ old('dispositivo', $electronica->dispositivo ?? '') }}" placeholder="Ej: Televisor, Celular, Tablet..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('dispositivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Marca</label>
        <input type="text" name="marca" value="{{ old('marca', $electronica->marca ?? '') }}" placeholder="Ej: Samsung, LG, Apple..." class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción del Problema *</label>
        <textarea name="descripcion_problema" required rows="3" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">{{ old('descripcion_problema', $electronica->descripcion_problema ?? '') }}</textarea>
        @error('descripcion_problema') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo *</label>
        <select name="tipo" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
            <option value="correctivo" {{ old('tipo', $electronica->tipo ?? 'correctivo') === 'correctivo' ? 'selected' : '' }}>Correctivo</option>
            <option value="preventivo" {{ old('tipo', $electronica->tipo ?? '') === 'preventivo' ? 'selected' : '' }}>Preventivo</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Estado *</label>
        <select name="estado" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
            <option value="pendiente" {{ old('estado', $electronica->estado ?? 'pendiente') === 'pendiente' ? 'selected' : '' }}>⏳ Pendiente</option>
            <option value="terminado" {{ old('estado', $electronica->estado ?? '') === 'terminado' ? 'selected' : '' }}>✅ Terminado</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Costo ($) *</label>
        <input type="number" step="0.01" name="costo" required value="{{ old('costo', $electronica->costo ?? 0) }}" min="0" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('costo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Técnico *</label>
        <select name="tecnico_id" required class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
            <option value="">-- Seleccionar técnico --</option>
            @foreach($tecnicos as $t)
                <option value="{{ $t->id }}" {{ old('tecnico_id', $electronica->tecnico_id ?? '') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
            @endforeach
        </select>
        @error('tecnico_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha Entrada *</label>
        <input type="date" name="fecha_entrada" required value="{{ old('fecha_entrada', isset($electronica) ? $electronica->fecha_entrada->format('Y-m-d') : date('Y-m-d')) }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('fecha_entrada') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Fecha Salida</label>
        <input type="date" name="fecha_salida" value="{{ old('fecha_salida', isset($electronica) && $electronica->fecha_salida ? $electronica->fecha_salida->format('Y-m-d') : '') }}" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-purple-500 transition-all">
        @error('fecha_salida') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

