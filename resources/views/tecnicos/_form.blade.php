{{-- resources/views/tecnicos/_form.blade.php --}}
@php
    $t = $tecnico ?? null;
    $selEsp = old('especialidad', $t?->especialidad ?? '');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="field-label">Nombre Completo *</label>
            <input type="text" name="nombre" value="{{ old('nombre', $t?->nombre) }}" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')" class="glass-input mt-1 @error('nombre') border-red-500 @enderror">
            @error('nombre') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Identificación (DNI/NIT) *</label>
            <input type="text" name="identificacion" value="{{ old('identificacion', $t?->identificacion) }}" required oninput="this.value = this.value.replace(/[^0-9-]/g, '')" class="glass-input mt-1 @error('identificacion') border-red-500 @enderror">
            @error('identificacion') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Especialidad *</label>
            <select name="especialidad" required class="glass-input no-search mt-1 text-sm font-bold">
                <option value="">— Seleccionar especialidad —</option>
                <option value="Hardware" {{ $selEsp == 'Hardware' ? 'selected' : '' }}>Hardware</option>
                <option value="Software" {{ $selEsp == 'Software' ? 'selected' : '' }}>Software</option>
                <option value="Electrónica" {{ $selEsp == 'Electrónica' ? 'selected' : '' }}>Electrónica</option>
                <option value="Redes" {{ $selEsp == 'Redes' ? 'selected' : '' }}>Redes</option>
                <option value="General" {{ $selEsp == 'General' ? 'selected' : '' }}>General</option>
            </select>
            @error('especialidad') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="field-label">Teléfono Móvil *</label>
            <input type="tel" name="movil" value="{{ old('movil', $t?->movil) }}" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="glass-input mt-1 @error('movil') border-red-500 @enderror">
            @error('movil') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="field-label">Email</label>
            <input type="email" name="email" value="{{ old('email', $t?->email) }}" class="glass-input mt-1 @error('email') border-red-500 @enderror">
            @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="md:col-span-2">
        <label class="field-label">Dirección</label>
        <textarea name="direccion" rows="3" class="glass-input mt-1 resize-y">{{ old('direccion', $t?->direccion) }}</textarea>
    </div>

    <div>
        <label class="field-label mb-2 block">Foto del Técnico (Opcional)</label>
        <input type="hidden" name="remove_photo" id="remove_photo" value="0">

        {{-- Foto actual asignada en BD --}}
        @if($t?->photo)
            <div id="existing-photo-container" class="mb-3 flex items-center gap-4">
                <div class="p-1 rounded-2xl bg-white/40 dark:bg-slate-900/40 border border-white/50 dark:border-white/10 backdrop-blur-sm shadow-sm shrink-0">
                    <img src="{{ asset('storage/' . $t->photo) }}" width="100" height="100" class="w-20 h-20 rounded-xl object-cover shadow-sm cursor-pointer hover:opacity-80 transition block" onclick="openImageLightbox('{{ asset('storage/' . $t->photo) }}', '{{ addslashes($t->nombre) }}', this)">
                </div>
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Foto actual del técnico</span>
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
        <div id="new-photo-preview-container" class="hidden mb-3 flex items-center gap-4">
            <div class="p-1 rounded-2xl bg-blue-500/10 border border-blue-500/20 backdrop-blur-sm shadow-sm shrink-0">
                <img id="new-photo-preview-img" src="" alt="Nueva foto" class="w-20 h-20 rounded-xl object-cover shadow-sm block">
            </div>
            <div class="space-y-1.5">
                <span class="text-xs font-bold text-blue-600 dark:text-blue-400 block">Nueva imagen lista para subir</span>
                <button type="button" id="btn-clear-new-file" class="btn-danger">
                    ❌ Quitar selección
                </button>
            </div>
        </div>

        {{-- Selector de Archivo Estilizado y Anidado --}}
        <input type="file" name="photo" id="tecnico_photo_input" accept="image/*" class="hidden">
        
        <div class="flex items-center gap-3 p-1 rounded-2xl border border-white/30 dark:border-white/10 bg-white/20 dark:bg-slate-900/40 backdrop-blur-md shadow-inner">
            <label for="tecnico_photo_input" class="btn-blue inline-flex items-center gap-2 px-3.5 py-2 text-[13px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-600/10 hover:bg-blue-600/20 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 border border-blue-500/30 dark:border-blue-400/30 rounded-[14px] cursor-pointer select-none transition-all duration-200 shrink-0 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Seleccionar imagen</span>
            </label>
            
            <span id="tecnico_photo_name" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate flex-1 pr-3 select-none">
                Ningún archivo seleccionado
            </span>
        </div>

        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
            Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB).
            @if($t?->photo)
                <span class="text-indigo-500 dark:text-indigo-400 font-medium">Deja vacío para conservar la foto actual.</span>
            @endif
        </p>
        @error('photo') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
        <a href="{{ route('tecnicos.index') }}" class="btn-cancel">↩️ Cancelar</a>
        <button type="submit" class="btn-save">
            {{ $t ? '🔄 Actualizar Técnico' : '💾 Guardar Técnico' }}
        </button>
    </div>
</div>

<script>
(function () {
    var photoInput = document.getElementById('tecnico_photo_input');
    var removePhotoInput = document.getElementById('remove_photo');
    var existingContainer = document.getElementById('existing-photo-container');
    var removedNotice = document.getElementById('photo-removed-notice');
    var btnRemoveExisting = document.getElementById('btn-remove-existing');
    var btnRestoreExisting = document.getElementById('btn-restore-existing');
    var previewContainer = document.getElementById('new-photo-preview-container');
    var previewImg = document.getElementById('new-photo-preview-img');
    var btnClearNewFile = document.getElementById('btn-clear-new-file');
    var photoNameSpan = document.getElementById('tecnico_photo_name');

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
