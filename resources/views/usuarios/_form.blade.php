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
    <label class="field-label">Foto de Perfil {{ $isEdit ? '' : '(Opcional)' }}</label>
    @if($isEdit && $user->photo)
        <div class="mb-3">
            <img src="{{ asset('storage/' . $user->photo) }}" width="100" height="100" class="rounded-full object-cover border-2 border-gray-300 dark:border-gray-600 shadow-md cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('{{ asset('storage/' . $user->photo) }}', '{{ addslashes($user->name) }}', this)">
        </div>
    @endif
    <input type="file" name="photo" accept="image/*" class="glass-input mt-1 @error('photo') border-red-500 @enderror">
    @if($isEdit)
        <p class="text-xs text-gray-500 mt-1">Selecciona una imagen para actualizar tu foto actual.</p>
    @endif
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
});
</script>
