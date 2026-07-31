{{-- resources/views/stocks/_form.blade.php --}}
@php
    $st = $stock ?? null;
    $categorias = \App\Models\CategoriaStock::where('tipo', 'categoria')->pluck('nombre');
    $subcategorias = \App\Models\CategoriaStock::where('tipo', 'subcategoria')->pluck('nombre');
    $selCat = old('categoria', $st?->categoria ?? '');
    $selSubcat = old('subcategoria', $st?->subcategoria ?? '');
    $selProv = old('proveedor_id', $st?->proveedor_id ?? '');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label for="codigo" class="field-label">Código (Opcional)</label>
            <input type="text" name="codigo" id="codigo" value="{{ old('codigo', $st?->codigo) }}"
                   oninput="this.value = this.value.toUpperCase()" class="glass-input" placeholder="Ej: REF-001">
            @error('codigo') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="producto" class="field-label">Nombre del Producto *</label>
            <input type="text" name="producto" id="producto" value="{{ old('producto', $st?->producto) }}"
                   required class="glass-input" placeholder="Ej: Disco Duro SSD 1TB">
            @error('producto') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="categoria" class="field-label">Categoría *</label>
            <select name="categoria" id="categoria" required class="glass-input no-search">
                <option value="">Seleccione una categoría...</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat }}" {{ $selCat == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
            @error('categoria') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="subcategoria" class="field-label">Subcategoría *</label>
            <select name="subcategoria" id="subcategoria" required class="glass-input no-search">
                <option value="">Seleccione una subcategoría...</option>
                @foreach($subcategorias as $subcat)
                    <option value="{{ $subcat }}" {{ $selSubcat == $subcat ? 'selected' : '' }}>{{ $subcat }}</option>
                @endforeach
            </select>
            @error('subcategoria') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="cantidad" class="field-label">{{ $st ? 'Cantidad Actual *' : 'Cantidad Inicial *' }}</label>
            <input type="number" name="cantidad" id="cantidad"
                   value="{{ old('cantidad', $st?->cantidad ?? 0) }}"
                   required min="0" class="glass-input font-bold dark:[color-scheme:dark]">
            @error('cantidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="proveedor_id" class="field-label">Proveedor *</label>
            <select name="proveedor_id" id="proveedor_id" required class="glass-input no-search">
                <option value="">Seleccione un proveedor...</option>
                @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" {{ $selProv == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->nombre_razon_social }} ({{ $proveedor->identificacion }})
                    </option>
                @endforeach
            </select>
            @error('proveedor_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
        </div>

        {{-- Foto del producto --}}
        <div class="md:col-span-2">
            <label class="field-label">Foto del Producto (Opcional)</label>
            @if($st?->photo)
                <div class="mb-2">
                    <img src="{{ asset('storage/' . $st->photo) }}" alt="{{ $st->producto }}"
                         onclick="openImageLightbox('{{ asset('storage/' . $st->photo) }}', '{{ addslashes($st->producto) }}', this)"
                         class="w-20 h-20 rounded-lg object-cover cursor-pointer border border-white/40 shadow-sm hover:opacity-80 transition">
                </div>
            @endif
            <input type="file" name="photo" accept="image/*" class="glass-input">
            @if($st?->photo)
                <p class="text-[10px] text-gray-400 mt-1">Deja vacío para mantener la foto actual</p>
            @endif
        </div>
    </div>

    {{-- Panel de Precios --}}
    <div class="pricing-panel p-5 bg-white/45 dark:bg-slate-900/60 border border-white/40 dark:border-white/10 rounded-2xl shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="precio_compra_visual" class="field-label">P. Compra ($) *</label>
                <input type="text" id="precio_compra_visual"
                       value="{{ old('precio_compra', isset($st) && $st->precio_compra ? number_format($st->precio_compra, 0, '', '') : '') }}"
                       required class="glass-input text-right font-bold text-slate-800 dark:text-white" placeholder="0">
                <input type="hidden" name="precio_compra" id="precio_compra_real"
                       value="{{ old('precio_compra', isset($st) ? intval($st->precio_compra) : '') }}">
                @error('precio_compra') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="utilidad" class="field-label">Utilidad (%) *</label>
                <div class="glass-input flex items-center justify-end pr-3">
                    <input type="number" step="1" name="utilidad" id="utilidad"
                           value="{{ old('utilidad', isset($st) && $st->utilidad !== null ? (int)$st->utilidad : 30) }}" required min="0"
                           class="w-12 bg-transparent border-none outline-none focus:ring-0 text-left pl-1 font-bold text-slate-800 dark:text-white dark:[color-scheme:dark] p-0">
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-sm ml-1">%</span>
                </div>
                @error('utilidad') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="precio_venta_visual" class="field-label">P. Venta (Manual)</label>
                <input type="text" id="precio_venta_visual"
                       value="{{ old('precio_venta', isset($st) && $st->precio_venta ? number_format($st->precio_venta, 0, '', '') : '') }}"
                       placeholder="Automático" class="glass-input text-right font-bold text-blue-600 dark:text-cyan-400">
                <input type="hidden" name="precio_venta" id="precio_venta_real"
                       value="{{ old('precio_venta', isset($st) && $st->precio_venta ? intval($st->precio_venta) : '') }}">
                @error('precio_venta') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="precio_tecnico_visual" class="field-label">P. Técnico (Manual)</label>
                <input type="text" id="precio_tecnico_visual"
                       value="{{ old('precio_tecnico', isset($st) && $st->precio_tecnico ? number_format($st->precio_tecnico, 0, '', '') : '') }}"
                       placeholder="Automático" class="glass-input text-right font-bold text-purple-600 dark:text-purple-400">
                <input type="hidden" name="precio_tecnico" id="precio_tecnico_real"
                       value="{{ old('precio_tecnico', isset($st) && $st->precio_tecnico ? intval($st->precio_tecnico) : '') }}">
                @error('precio_tecnico') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-[10px] text-gray-500 mt-3 font-medium">Si dejas P. Venta y P. Técnico vacíos, se calculan automáticamente: Venta = Compra × (1 + Utilidad%), Técnico = Compra × (1 + Utilidad%/2).</p>
    </div>

    <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
        <a href="{{ route('stocks.index') }}" class="btn-cancel">↩️ Cancelar</a>
        <button type="submit" class="btn-save">
            {{ $st ? '🔄 Actualizar Producto' : '💾 Guardar Producto' }}
        </button>
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
