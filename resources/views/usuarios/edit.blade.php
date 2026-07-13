@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('usuarios.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Usuario: {{ $user->name }}</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Actualiza los datos y credenciales del usuario</p>
 </div>
 </div>

 <form method="POST" action="{{ route('usuarios.update', $user->id) }}" enctype="multipart/form-data">
 @csrf
 @method('PUT')

 {{-- Nombre --}}
 <div class="mb-5">
 <label class="field-label">Nombre Completo *</label>
 <input type="text" name="name" value="{{ old('name', $user->name) }}" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')" class="glass-input mt-1 @error('name') border-red-500 @enderror">
 @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Email --}}
 <div class="mb-5">
 <label class="field-label">Correo Electrónico *</label>
 <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="glass-input mt-1 @error('email') border-red-500 @enderror">
 @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Rol --}}
 @if(auth()->user()->isAdmin())
 <div class="mb-5">
 <label class="field-label">Rol del Sistema</label>
 <select name="role" required class="glass-input no-search mt-1">
 <option value="tecnico" {{ old('role', $user->role) == 'tecnico' ? 'selected' : '' }}>Técnico</option>
 <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
 <option value="invitado" {{ old('role', $user->role) == 'invitado' ? 'selected' : '' }}>Invitado</option>
 </select>
 </div>
 @else
 <input type="hidden" name="role" value="{{ $user->role }}">
 @endif

 {{-- Foto de Perfil --}}
 <div class="mb-5">
 <label class="field-label">Foto de Perfil</label>
@if($user->photo)
  <div class="mb-3">
  <img src="{{ asset('storage/' . $user->photo) }}" width="100" height="100" class="rounded-full object-cover border-2 border-gray-300 dark:border-gray-600 shadow-md cursor-pointer hover:opacity-80 transition" onclick="openImageLightbox('{{ asset('storage/' . $user->photo) }}', '{{ addslashes($user->name) }}', this)">
  </div>
@endif
 <input type="file" name="photo" accept="image/*" class="glass-input @error('photo') border-red-500 @enderror">
 <p class="text-xs text-gray-500 mt-1">Selecciona una imagen para actualizar tu foto actual.</p>
 @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>



  <hr class="my-6 border-gray-200 dark:border-gray-700">

  {{-- Contraseña actual (solo auto-edición, por seguridad) --}}
  @if(auth()->id() === $user->id)
  <div class="mb-4">
  <label class="block text-sm font-medium mb-2 text-gray-500">Contraseña Actual *</label>
  <input type="password" id="current_password" name="current_password" placeholder="Requerida para cambiar tu contraseña" class="glass-input @error('current_password') border-red-500 @enderror">
  @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
  </div>
  @endif

  {{-- Password --}}
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2 text-gray-500">Cambiar Contraseña del Usuario (opcional)</label>
 <input type="password" id="password" name="password" placeholder="Dejar en blanco para no cambiar" class="glass-input @error('password') border-red-500 @enderror">
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
 @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 <div class="mb-6">
 <label class="block text-sm font-medium mb-2 text-gray-500">Confirmar Nueva Contraseña</label>
 <input type="password" id="password_confirmation" name="password_confirmation" class="glass-input">
 <p id="req-match" class="mt-1 text-sm text-red-500 hidden">Las contraseñas no coinciden</p>
 </div>

 <hr class="my-6 border-gray-200 dark:border-gray-700">



 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('usuarios.index') }}" class="btn-cancel">
 ↩️ Cancelar
 </a>
 <button type="submit" class="btn-save">
 🔄 Actualizar Usuario
 </button>
 </div>
 </form>
 </div>
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

 function setReq(el, valid) {
 var span = el.querySelector('span');
 if (valid) {
 el.classList.add('text-emerald-600', 'dark:text-emerald-400');
 el.classList.remove('text-gray-500', 'dark:text-gray-400');
 if (span) {
 span.textContent = '✓';
 span.classList.add('bg-emerald-100', 'text-emerald-600', 'dark:bg-emerald-900/50', 'dark:text-emerald-400');
 span.classList.remove('bg-gray-200', 'text-gray-500', 'dark:bg-gray-700', 'dark:text-gray-400');
 }
 } else {
 el.classList.remove('text-emerald-600', 'dark:text-emerald-400');
 el.classList.add('text-gray-500', 'dark:text-gray-400');
 if (span) {
 span.textContent = '✖';
 span.classList.remove('bg-emerald-100', 'text-emerald-600', 'dark:bg-emerald-900/50', 'dark:text-emerald-400');
 span.classList.add('bg-gray-200', 'text-gray-500', 'dark:bg-gray-700', 'dark:text-gray-400');
 }
 }
 }

 if (passwordInput) {
 passwordInput.addEventListener('focus', function() {
 requirementsList.classList.remove('hidden');
 });

 passwordInput.addEventListener('input', function() {
 var val = passwordInput.value;
 requirementsList.classList.remove('hidden');
 setReq(reqLength, val.length >= 8);
 setReq(reqCase, /[a-z]/.test(val) && /[A-Z]/.test(val));
 setReq(reqNumber, /\d/.test(val));
 checkMatch();
 });
 }

 function checkMatch() {
 if (confirmInput.value.length > 0 && passwordInput.value.length > 0) {
 if (passwordInput.value !== confirmInput.value) {
 reqMatch.classList.remove('hidden');
 } else {
 reqMatch.classList.add('hidden');
 }
 } else {
 reqMatch.classList.add('hidden');
 }
 }

 if (confirmInput) {
 confirmInput.addEventListener('focus', function() {
 requirementsList.classList.remove('hidden');
 });
 confirmInput.addEventListener('input', function() {
 requirementsList.classList.remove('hidden');
 checkMatch();
 });
 }

 // Evita el envío del formulario si no se cumplen los requisitos
 var form = document.querySelector('form');
 if (form && passwordInput) {
 form.addEventListener('submit', function(e) {
 var val = passwordInput.value;
 var confVal = confirmInput.value;

 // Si el usuario intentó escribir algo en cualquiera de los dos campos
 if (val.length > 0 || confVal.length > 0) {
 var isLengthValid = val.length >= 8;
 var isCaseValid = /[a-z]/.test(val) && /[A-Z]/.test(val);
 var isNumberValid = /\d/.test(val);
 var isMatchValid = (val === confVal) && val.length > 0;

 // Si NO cumple los requisitos o NO coinciden/están vacíos
 if (!isLengthValid || !isCaseValid || !isNumberValid || !isMatchValid) {
 e.preventDefault(); // DETENER ENVÍO
 requirementsList.classList.remove('hidden');
 
 if (val.length === 0 && confVal.length > 0) {
 if (typeof showToast === 'function') showToast('Debes ingresar una nueva contraseña si deseas usar el campo de confirmación.', 'error'); else alert('Debes ingresar una nueva contraseña si deseas usar el campo de confirmación.');
 passwordInput.focus();
 } else if (!isMatchValid) {
 if (typeof showToast === 'function') showToast('Las contraseñas no coinciden.', 'error'); else alert('Las contraseñas no coinciden.');
 confirmInput.focus();
 } else {
 if (typeof showToast === 'function') showToast('La nueva contraseña no cumple con los requisitos.', 'error'); else alert('La nueva contraseña no cumple con los requisitos.');
 passwordInput.focus();
 }
 }
 }
 });
 }
});
</script>
@endsection

