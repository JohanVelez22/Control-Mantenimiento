@php
    $user = $user ?? null;
    $isEdit = !is_null($user);
@endphp

{{-- Nombre --}}
<div class="mb-5">
    <label class="field-label">Nombre {{ $isEdit ? 'Completo' : '' }} *</label>
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')" class="glass-input mt-1 @error('name') border-red-500 @enderror">
    @error('name') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
</div>

{{-- Email --}}
<div class="mb-5">
    <label class="field-label">Correo Electrónico *</label>
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="glass-input mt-1 @error('email') border-red-500 @enderror">
    @error('email') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
</div>

{{-- Rol --}}
@if(!$isEdit || auth()->user()->isAdmin())
<div class="mb-5">
    <label class="field-label">Rol {{ $isEdit ? 'del Sistema' : '*' }}</label>
    <select name="role" required class="glass-input no-search mt-1">
        <option value="tecnico" {{ old('role', $user->role ?? '') == 'tecnico' ? 'selected' : '' }}>Técnico</option>
        <option value="admin" {{ old('role', $user->role ?? '') == 'admin' ? 'selected' : '' }}>Administrador</option>
        <option value="invitado" {{ old('role', $user->role ?? '') == 'invitado' ? 'selected' : '' }}>Invitado</option>
    </select>
</div>
@else
<input type="hidden" name="role" value="{{ $user->role }}">
@endif

{{-- Foto de Perfil --}}
<div class="mb-5">
    <label class="field-label mb-2 block">Foto de Perfil {{ $isEdit ? '' : '(Opcional)' }}</label>
    <input type="hidden" name="remove_photo" id="remove_user_photo" value="0">

    {{-- Foto actual asignada en BD --}}
    @if($isEdit && $user->photo)
        <div id="existing-user-photo-container" class="mb-3 flex items-center gap-4 p-3 rounded-2xl bg-white/40 dark:bg-slate-900/40 border border-white/50 dark:border-white/10 w-fit backdrop-blur-sm shadow-sm">
            <img src="{{ asset('storage/' . $user->photo) }}" width="100" height="100" class="w-20 h-20 rounded-full object-cover border-2 border-white/60 shadow-sm cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('{{ asset('storage/' . $user->photo) }}', '{{ addslashes($user->name) }}', this)">
            <div class="space-y-1.5">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block">Foto actual del usuario</span>
                <button type="button" id="btn-remove-existing-user-photo" class="btn-danger">
                    🗑️ Quitar imagen
                </button>
            </div>
        </div>

        {{-- Aviso cuando se marca para quitar la foto actual --}}
        <div id="user-photo-removed-notice" class="hidden mb-3 p-3 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-between gap-4 max-w-md">
            <div class="flex items-center gap-2 text-xs font-bold text-red-500 dark:text-red-400">
                <span>⚠️ La foto actual se eliminará al guardar los cambios.</span>
            </div>
            <button type="button" id="btn-restore-existing-user-photo" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline shrink-0">
                ↩️ Deshacer
            </button>
        </div>
    @endif

    {{-- Previsualización interactiva de nueva foto seleccionada --}}
    <div id="new-user-photo-preview-container" class="hidden mb-3 flex items-center gap-4 p-3 rounded-2xl bg-blue-500/10 border border-blue-500/20 w-fit backdrop-blur-sm shadow-sm">
        <img id="new-user-photo-preview-img" src="" alt="Nueva foto" class="w-20 h-20 rounded-full object-cover border-2 border-blue-500/40 shadow-sm">
        <div class="space-y-1.5">
            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 block">Nueva imagen lista para subir</span>
            <button type="button" id="btn-clear-new-user-file" class="btn-danger">
                ❌ Quitar selección
            </button>
        </div>
    </div>

    {{-- Selector de Archivo Estilizado y Anidado --}}
    <input type="file" name="photo" id="user_photo_input" accept="image/*" class="hidden">
    
        <div class="flex items-center gap-3 p-1 rounded-2xl border border-white/30 dark:border-white/10 bg-white/20 dark:bg-slate-900/40 backdrop-blur-md shadow-inner">
            <label for="user_photo_input" class="btn-blue inline-flex items-center gap-2 px-3.5 py-2 text-[13px] font-semibold text-blue-600 dark:text-blue-400 bg-blue-600/10 hover:bg-blue-600/20 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 border border-blue-500/30 dark:border-blue-400/30 rounded-[14px] cursor-pointer select-none transition-all duration-200 shrink-0 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Seleccionar imagen</span>
            </label>
        
        <span id="user_photo_name" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate flex-1 pr-3 select-none">
            Ningún archivo seleccionado
        </span>
    </div>

    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
        Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB).
        @if($isEdit && $user->photo)
            <span class="text-indigo-500 dark:text-indigo-400 font-medium">Deja vacío para conservar la foto actual.</span>
        @endif
    </p>
    @error('photo') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
</div>

@if($isEdit)
    <hr class="my-6 border-gray-200 dark:border-gray-700">
    @if(auth()->id() === $user->id)
    <div class="mb-4">
        <label class="block text-sm font-medium mb-2 text-gray-500">Contraseña Actual *</label>
        <input type="password" id="current_password" name="current_password" placeholder="Requerida para cambiar tu contraseña" class="glass-input @error('current_password') border-red-500 @enderror">
        @error('current_password') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
    </div>
    @endif
@endif

{{-- Password --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2 text-gray-500">{{ $isEdit ? 'Cambiar Contraseña del Usuario (opcional)' : 'Contraseña del Nuevo Usuario *' }}</label>
    <input type="password" id="password" name="password" {{ $isEdit ? '' : 'required' }} placeholder="{{ $isEdit ? 'Dejar en blanco para no cambiar' : '' }}" class="glass-input mt-1 @error('password') border-red-500 @enderror">
    @error('password') <span class="text-red-500 text-xs font-bold mt-1 block">{{ $message }}</span> @enderror
    <ul id="password-requirements" class="mt-2 text-xs space-y-1.5 hidden transition-all duration-300">
        <li id="req-length" class="flex items-center gap-2 text-gray-500 dark:text-gray-400 font-medium transition-colors">
            <span class="flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[9px] font-black transition-colors">✖</span> Mínimo 8 caracteres
        </li>
        <li id="req-case" class="flex items-center gap-2 text-gray-500 dark:text-gray-400 font-medium transition-colors">
            <span class="flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[9px] font-black transition-colors">✖</span> Mayúsculas y minúsculas
        </li>
        <li id="req-number" class="flex items-center gap-2 text-gray-500 dark:text-gray-400 font-medium transition-colors">
            <span class="flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[9px] font-black transition-colors">✖</span> Al menos un número
        </li>
    </ul>
</div>

{{-- Confirmar Contraseña --}}
<div class="mb-5">
    <label class="block text-sm font-medium mb-2 text-gray-500">{{ $isEdit ? 'Confirmar Nueva Contraseña' : 'Confirmar Contraseña *' }}</label>
    <input type="password" id="password_confirmation" name="password_confirmation" {{ $isEdit ? '' : 'required' }} class="glass-input">
    <p id="req-match" class="mt-1 text-sm text-red-500 hidden">Las contraseñas no coinciden</p>
</div>

@if(!$isEdit)
<div class="mb-6 flex items-center">
    <input type="checkbox" name="active" id="active" checked class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
    <label for="active" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activar usuario inmediatamente</label>
</div>
@endif

<div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
    <a href="{{ route('usuarios.index') }}" class="btn-cancel">↩️ Cancelar</a>
    <button type="submit" class="btn-save">
        {{ $isEdit ? '🔄 Actualizar Usuario' : '💾 Guardar Usuario' }}
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('password_confirmation');
    var requirementsList = document.getElementById('password-requirements');
    var reqLength = document.getElementById('req-length');
    var reqCase = document.getElementById('req-case');
    var reqNumber = document.getElementById('req-number');
    var reqMatch = document.getElementById('req-match');

    if(!passwordInput) return;

    passwordInput.addEventListener('focus', function() {
        if(requirementsList) requirementsList.classList.remove('hidden');
    });

    passwordInput.addEventListener('input', function() {
        var val = this.value;
        if(reqLength) updateReq(reqLength, val.length >= 8);
        if(reqCase) updateReq(reqCase, /[a-z]/.test(val) && /[A-Z]/.test(val));
        if(reqNumber) updateReq(reqNumber, /\d/.test(val));
        if(confirmInput && confirmInput.value) checkMatch();
    });

    if(confirmInput) {
        confirmInput.addEventListener('input', checkMatch);
    }

    function checkMatch() {
        if(!reqMatch) return;
        if(passwordInput.value && confirmInput.value && passwordInput.value !== confirmInput.value) {
            reqMatch.classList.remove('hidden');
        } else {
            reqMatch.classList.add('hidden');
        }
    }

    function updateReq(el, isValid) {
        var icon = el.querySelector('span');
        if (isValid) {
            el.classList.remove('text-gray-500', 'dark:text-gray-400');
            el.classList.add('text-emerald-600', 'dark:text-emerald-400');
            if(icon) {
                icon.className = 'flex items-center justify-center w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 text-[9px] font-black transition-colors';
                icon.textContent = '✓';
            }
        } else {
            el.classList.remove('text-emerald-600', 'dark:text-emerald-400');
            el.classList.add('text-gray-500', 'dark:text-gray-400');
            if(icon) {
                icon.className = 'flex items-center justify-center w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[9px] font-black transition-colors';
                icon.textContent = '✖';
            }
        }
    }

    // Manejo de Foto de Perfil
    var userPhotoInput = document.getElementById('user_photo_input');
    var removeUserPhotoInput = document.getElementById('remove_user_photo');
    var existingUserContainer = document.getElementById('existing-user-photo-container');
    var userRemovedNotice = document.getElementById('user-photo-removed-notice');
    var btnRemoveExistingUser = document.getElementById('btn-remove-existing-user-photo');
    var btnRestoreExistingUser = document.getElementById('btn-restore-existing-user-photo');
    var userPreviewContainer = document.getElementById('new-user-photo-preview-container');
    var userPreviewImg = document.getElementById('new-user-photo-preview-img');
    var btnClearNewUserFile = document.getElementById('btn-clear-new-user-file');
    var userPhotoNameSpan = document.getElementById('user_photo_name');

    function resetUserPhotoName() {
        if (userPhotoNameSpan) {
            userPhotoNameSpan.textContent = 'Ningún archivo seleccionado';
            userPhotoNameSpan.classList.remove('text-blue-600', 'dark:text-blue-400', 'text-indigo-600', 'dark:text-indigo-400', 'font-bold');
            userPhotoNameSpan.classList.add('text-gray-500', 'dark:text-gray-400');
        }
    }

    if (btnRemoveExistingUser) {
        btnRemoveExistingUser.addEventListener('click', function () {
            if (removeUserPhotoInput) removeUserPhotoInput.value = '1';
            if (existingUserContainer) existingUserContainer.classList.add('hidden');
            if (userRemovedNotice) userRemovedNotice.classList.remove('hidden');
            if (userPhotoInput) userPhotoInput.value = '';
            resetUserPhotoName();
            if (userPreviewContainer) userPreviewContainer.classList.add('hidden');
        });
    }

    if (btnRestoreExistingUser) {
        btnRestoreExistingUser.addEventListener('click', function () {
            if (removeUserPhotoInput) removeUserPhotoInput.value = '0';
            if (existingUserContainer) existingUserContainer.classList.remove('hidden');
            if (userRemovedNotice) userRemovedNotice.classList.add('hidden');
        });
    }

    if (userPhotoInput) {
        userPhotoInput.addEventListener('change', function (e) {
            var file = e.target.files && e.target.files[0];
            if (file) {
                if (userPhotoNameSpan) {
                    var sizeKb = Math.round(file.size / 1024);
                    var sizeStr = sizeKb >= 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB';
                    userPhotoNameSpan.textContent = '📄 ' + file.name + ' (' + sizeStr + ')';
                    userPhotoNameSpan.classList.remove('text-gray-500', 'dark:text-gray-400');
                    userPhotoNameSpan.classList.add('text-blue-600', 'dark:text-blue-400', 'font-bold');
                }
                var reader = new FileReader();
                reader.onload = function (ev) {
                    if (userPreviewImg) userPreviewImg.src = ev.target.result;
                    if (userPreviewContainer) userPreviewContainer.classList.remove('hidden');
                    if (removeUserPhotoInput) removeUserPhotoInput.value = '0';
                    if (userRemovedNotice) userRemovedNotice.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                resetUserPhotoName();
                if (userPreviewContainer) userPreviewContainer.classList.add('hidden');
            }
        });
    }

    if (btnClearNewUserFile) {
        btnClearNewUserFile.addEventListener('click', function () {
            if (userPhotoInput) userPhotoInput.value = '';
            resetUserPhotoName();
            if (userPreviewContainer) userPreviewContainer.classList.add('hidden');
            if (userPreviewImg) userPreviewImg.src = '';
        });
    }
});
</script>
