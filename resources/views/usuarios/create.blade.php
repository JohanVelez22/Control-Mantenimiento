@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 dark:border-gray-700/50 p-8">
 <h2 class="text-2xl font-bold mb-6">Nuevo Usuario</h2>

 <form method="POST" action="{{ route('usuarios.store') }}" enctype="multipart/form-data">
 @csrf
 
 {{-- Nombre --}}
 <div class="mb-5">
 <label class="field-label">Nombre *</label>
 <input type="text" name="name" value="{{ old('name') }}" required oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '')" class="glass-input mt-1 @error('name') border-red-500 @enderror">
 @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Email --}}
 <div class="mb-5">
 <label class="field-label">Email *</label>
 <input type="email" name="email" value="{{ old('email') }}" required class="glass-input mt-1 @error('email') border-red-500 @enderror">
 @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Rol --}}
 <div class="mb-5">
 <label class="field-label">Rol *</label>
 <select name="role" required class="glass-input no-search mt-1">
 <option value="tecnico" {{ old('role') == 'tecnico' ? 'selected' : '' }}>Técnico</option>
 <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador</option>
 <option value="invitado" {{ old('role') == 'invitado' ? 'selected' : '' }}>Invitado</option>
 </select>
 </div>

 {{-- Foto de Perfil --}}
 <div class="mb-5">
 <label class="field-label">Foto de Perfil (Opcional)</label>
 <input type="file" name="photo" accept="image/*" class="glass-input mt-1 @error('photo') border-red-500 @enderror">
 @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Contraseña (para el usuario a crear) --}}
 <div class="mb-5">
 <label class="field-label">Contraseña del Nuevo Usuario *</label>
 <input type="password" id="password" name="password" required class="glass-input mt-1 @error('password') border-red-500 @enderror">
 @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2">Confirmar Contraseña</label>
 <input type="password" id="password_confirmation" name="password_confirmation" required class="glass-input">
 <p id="req-match" class="mt-1 text-sm text-red-500 hidden">Las contraseñas no coinciden</p>
 </div>

 {{-- Campo de Activación (Nuevo) --}}
 <div class="mb-6 flex items-center">
 <input type="checkbox" name="active" id="active" checked class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
 <label for="active" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Activar usuario inmediatamente</label>
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('usuarios.index') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save">
 💾 Crear Usuario
 </button>
 </div>
 </form>
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
 setReq(reqLength, val.length >= 8);
 setReq(reqCase, /[a-z]/.test(val) && /[A-Z]/.test(val));
 setReq(reqNumber, /\d/.test(val));
 checkMatch();
 });
 }

 function checkMatch() {
 if (confirmInput.value.length > 0) {
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
 confirmInput.addEventListener('input', checkMatch);
 }

 // Prevent form submission if requirements are not met
 var form = document.querySelector('form');
 if (form && passwordInput) {
 form.addEventListener('submit', function(e) {
 var val = passwordInput.value;
 var isLengthValid = val.length >= 8;
 var isCaseValid = /[a-z]/.test(val) && /[A-Z]/.test(val);
 var isNumberValid = /\d/.test(val);
 var isMatchValid = val === confirmInput.value;

 if (!isLengthValid || !isCaseValid || !isNumberValid || !isMatchValid) {
 e.preventDefault(); // Stop submission
 requirementsList.classList.remove('hidden'); // Ensure requirements are visible
 if (typeof showToast === 'function') {
 showToast('Por favor, asegúrate de cumplir con todos los requisitos antes de guardar.', 'error');
 } else {
 alert('Por favor, asegúrate de cumplir con todos los requisitos antes de guardar.');
 }
 }
 });
 }
});
</script>
@endsection

