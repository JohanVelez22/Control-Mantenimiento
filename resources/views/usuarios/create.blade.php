@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 dark:border-gray-700/50 p-8">
 <h2 class="text-2xl font-bold mb-6">Nuevo Usuario</h2>

 <form method="POST" action="{{ route('usuarios.store') }}" enctype="multipart/form-data">
 @csrf
 
 {{-- Nombre --}}
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2">Nombre</label>
 <input type="text" name="name" value="{{ old('name') }}" required class="glass-input @error('name') border-red-500 @enderror">
 @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Email --}}
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2">Email</label>
 <input type="email" name="email" value="{{ old('email') }}" required class="glass-input @error('email') border-red-500 @enderror">
 @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Rol --}}
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2">Rol</label>
 <select name="role" required class="glass-input no-search">
 <option value="tecnico" {{ old('role') == 'tecnico' ? 'selected' : '' }}>Técnico</option>
 <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador</option>
 <option value="invitado" {{ old('role') == 'invitado' ? 'selected' : '' }}>Invitado</option>
 </select>
 </div>

 {{-- Foto de Perfil --}}
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2">Foto de Perfil (Opcional)</label>
 <input type="file" name="photo" accept="image/*" class="glass-input @error('photo') border-red-500 @enderror">
 @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 </div>

 {{-- Contraseña (para el usuario a crear) --}}
 <div class="mb-4">
 <label class="block text-sm font-medium mb-2">Contraseña del Nuevo Usuario</label>
 <input type="password" id="password" name="password" required class="glass-input @error('password') border-red-500 @enderror">
 @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
 <ul id="password-requirements" class="mt-2 text-sm text-gray-500 dark:text-gray-400 space-y-1 hidden">
 <li id="req-length" class="flex items-center"><span class="mr-2">✗</span> Mínimo 8 caracteres</li>
 <li id="req-case" class="flex items-center"><span class="mr-2">✗</span> Mayúsculas y minúsculas</li>
 <li id="req-number" class="flex items-center"><span class="mr-2">✗</span> Al menos un número</li>
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

 <div class="flex gap-4 mt-6">
 <a href="{{ route('usuarios.index') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-save w-1/2">
 Crear Usuario
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

 function updateRequirement(el, isValid) {
 if (isValid) {
 el.classList.remove('text-gray-500', 'dark:text-gray-400', 'text-red-500');
 el.classList.add('text-green-500');
 el.querySelector('span').textContent = '✓';
 } else {
 el.classList.remove('text-green-500');
 el.classList.add('text-gray-500', 'dark:text-gray-400');
 el.querySelector('span').textContent = '✗';
 }
 }

 if (passwordInput) {
 passwordInput.addEventListener('focus', function() {
 requirementsList.classList.remove('hidden');
 });

 passwordInput.addEventListener('input', function() {
 var val = passwordInput.value;
 updateRequirement(reqLength, val.length >= 8);
 updateRequirement(reqCase, /[a-z]/.test(val) && /[A-Z]/.test(val));
 updateRequirement(reqNumber, /\d/.test(val));
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

