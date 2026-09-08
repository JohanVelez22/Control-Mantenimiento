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
            <label class="field-label mb-2 block">Foto del Producto (Opcional)</label>
            <input type="hidden" name="remove_photo" id="remove_photo" value="0">

            {{-- Foto actual asignada en BD --}}
            @if($st?->photo)
                <div id="existing-photo-container" class="mb-3 flex items-center gap-4 p-3 rounded-2xl bg-white/40 dark:bg-slate-900/40 border border-white/50 dark:border-white/10 w-fit backdrop-blur-sm shadow-sm">
                    <img src="{{ asset('storage/' . $st->photo) }}" alt="{{ $st->producto }}"
                         onclick="openImageLightbox('{{ asset('storage/' . $st->photo) }}', '{{ addslashes($st->producto) }}', this)"
                         class="w-20 h-20 rounded-xl object-cover cursor-pointer border border-white/40 shadow-sm hover:opacity-80 transition">
                    <div class="space-y-1.5">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Foto actual del producto</span>
                        <button type="button" id="btn-remove-existing" class="btn-danger">
                            🗑️ Quitar imagen
                        </button>
                    </div>
                </div>

                {{-- Aviso cuando se marca para quitar la foto actual --}}
                <div id="photo-removed-notice" class="hidden mb-3 p-3 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-between gap-4 max-w-md">
                    <div class="flex items-center gap-2 text-xs font-bold text-red-500 dark:text-red-400">
                        <span>⚠️ La foto actual se eliminará al guardar los cambios.</span>
                    </div>
                    <button type="button" id="btn-restore-existing" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline shrink-0">
                        ↩️ Deshacer
                    </button>
                </div>
            @endif

            {{-- Previsualización interactiva de nueva foto seleccionada --}}
            <div id="new-photo-preview-container" class="hidden mb-3 flex items-center gap-4 p-3 rounded-2xl bg-blue-500/10 border border-blue-500/20 w-fit backdrop-blur-sm shadow-sm">
                <img id="new-photo-preview-img" src="" alt="Nueva foto" class="w-20 h-20 rounded-xl object-cover border-2 border-blue-500/40 shadow-sm">
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400 block">Nueva imagen lista para subir</span>
                    <button type="button" id="btn-clear-new-file" class="btn-danger">
                        ❌ Quitar selección
                    </button>
                </div>
            </div>

            {{-- Selector de Archivo Estilizado y Anidado --}}
            <input type="file" name="photo" id="stock_photo_input" accept="image/*" class="hidden">
            
            <div class="flex items-center gap-3 p-1 rounded-2xl border border-white/30 dark:border-white/10 bg-white/20 dark:bg-slate-900/40 backdrop-blur-md shadow-inner">
                <label for="stock_photo_input" class="btn-blue inline-flex items-center gap-2 px-3.5 py-2 text-[13px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-600/10 hover:bg-blue-600/20 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 border border-blue-500/30 dark:border-blue-400/30 rounded-[14px] cursor-pointer select-none transition-all duration-200 shrink-0 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Seleccionar imagen</span>
                </label>
                
                <span id="stock_photo_name" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate flex-1 pr-3 select-none">
                    Ningún archivo seleccionado
                </span>
            </div>

            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
                Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB).
                @if($st?->photo)
                    <span class="text-indigo-500 dark:text-indigo-400 font-medium">Deja vacío para conservar la foto actual.</span>
                @endif
            </p>
            @error('photo') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
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

    // Manejo de foto del producto
    var photoInput = document.getElementById('stock_photo_input');
    var removePhotoInput = document.getElementById('remove_photo');
    var existingContainer = document.getElementById('existing-photo-container');
    var removedNotice = document.getElementById('photo-removed-notice');
    var btnRemoveExisting = document.getElementById('btn-remove-existing');
    var btnRestoreExisting = document.getElementById('btn-restore-existing');
    var previewContainer = document.getElementById('new-photo-preview-container');
    var previewImg = document.getElementById('new-photo-preview-img');
    var btnClearNewFile = document.getElementById('btn-clear-new-file');
    var photoNameSpan = document.getElementById('stock_photo_name');

    function resetPhotoName() {
        if (photoNameSpan) {
            photoNameSpan.textContent = 'Ningún archivo seleccionado';
            photoNameSpan.classList.remove('text-blue-600', 'dark:text-blue-400', 'text-indigo-600', 'dark:text-indigo-400', 'font-bold');
            photoNameSpan.classList.add('text-gray-500', 'dark:text-gray-400');
        }
    }

    if (btnRemoveExisting) {
        btnRemoveExisting.addEventListener('click', function () {
            if (removePhotoInput) removePhotoInput.value = '1';
            if (existingContainer) existingContainer.classList.add('hidden');
            if (removedNotice) removedNotice.classList.remove('hidden');
            if (photoInput) photoInput.value = '';
            resetPhotoName();
            if (previewContainer) previewContainer.classList.add('hidden');
        });
    }

    if (btnRestoreExisting) {
        btnRestoreExisting.addEventListener('click', function () {
            if (removePhotoInput) removePhotoInput.value = '0';
            if (existingContainer) existingContainer.classList.remove('hidden');
            if (removedNotice) removedNotice.classList.add('hidden');
        });
    }

    if (photoInput) {
        photoInput.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            if (file) {
                if (photoNameSpan) {
                    var sizeKb = Math.round(file.size / 1024);
                    var sizeStr = sizeKb >= 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB';
                    photoNameSpan.textContent = '📄 ' + file.name + ' (' + sizeStr + ')';
                    photoNameSpan.classList.remove('text-gray-500', 'dark:text-gray-400');
                    photoNameSpan.classList.add('text-blue-600', 'dark:text-blue-400', 'font-bold');
                }
                var reader = new FileReader();
                reader.onload = function (ev) {
                    if (previewImg) previewImg.src = ev.target.result;
                    if (previewContainer) previewContainer.classList.remove('hidden');
                    if (removePhotoInput) removePhotoInput.value = '0';
                    if (removedNotice) removedNotice.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                resetPhotoName();
                if (previewContainer) previewContainer.classList.add('hidden');
            }
        });
    }

    if (btnClearNewFile) {
        btnClearNewFile.addEventListener('click', function () {
            if (photoInput) photoInput.value = '';
            resetPhotoName();
            if (previewContainer) previewContainer.classList.add('hidden');
            if (previewImg) previewImg.src = '';
        });
    }
})();
</script>
