@extends('layouts.app')

@section('content')

{{-- Botón modo oscuro --}}
<div class="absolute top-5 right-5 z-10">
 <button id="theme-toggle-login"
 class="p-2 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 shadow-sm hover:scale-105 transition-transform"
 title="Cambiar tema" aria-label="Cambiar tema">🌓</button>
</div>

<div class="w-full max-w-lg px-6">

 {{-- Header --}}
 <div class="text-center mb-8">
 <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-2xl shadow-xl mb-4">🔧</div>
 <h1 class="text-3xl font-black text-gray-900 dark:text-white">Iniciar Sesión</h1>
 <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tus credenciales para acceder al sistema</p>
 </div>

 {{-- Card --}}
 <div class="glass-card p-8 md:p-10">

 {{-- Errores --}}
 @if ($errors->any())
 <div class="mb-5 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm space-y-1">
 @foreach ($errors->all() as $error)
 <p class="flex items-center gap-2"><span>⚠️</span> {{ $error }}</p>
 @endforeach
 </div>
 @endif

 <form method="POST" action="{{ route('login') }}" class="space-y-5">
 @csrf

 {{-- Email --}}
 <div>
 <label for="email" class="field-label mb-1.5 flex items-center gap-2">
 <span>📧</span> Correo Electrónico
 </label>
 <input type="email" id="email" name="email" value="{{ old('email') }}"
 required autofocus placeholder="usuario@empresa.com"
 class="glass-input mt-1">
 @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 {{-- Contraseña --}}
 <div>
 <label for="password" class="field-label mb-1.5 flex items-center gap-2">
 <span>🔑</span> Contraseña
 </label>
 <input type="password" id="password" name="password"
 required placeholder="••••••••"
 class="glass-input mt-1">
 @error('password') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 {{-- Submit --}}
 <button type="submit" class="w-full btn-primary py-3 justify-center text-lg mt-4 ">
 Entrar al Sistema →
 </button>
 </form>

 {{-- Divider + enlace registro --}}
 <div class="mt-6 pt-5 border-t border-gray-200/50 dark:border-white/10 text-center">
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
 ¿No tienes cuenta?
 <a href="{{ route('register') }}" class="text-blue-600 dark:text-blue-400 font-bold hover:text-blue-700 dark:hover:text-blue-300 transition-colors ml-1">
 Regístrate aquí
 </a>
 </p>
 </div>
 </div>
</div>

<script>
(function(){
 if (localStorage.getItem('color-theme') === 'dark' ||
 (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
 document.documentElement.classList.add('dark');
 } else {
 document.documentElement.classList.remove('dark');
 }
 var btn = document.getElementById('theme-toggle-login');
 if (btn) {
 btn.addEventListener('click', function() {
 if (document.documentElement.classList.contains('dark')) {
 document.documentElement.classList.remove('dark');
 localStorage.setItem('color-theme', 'light');
 } else {
 document.documentElement.classList.add('dark');
 localStorage.setItem('color-theme', 'dark');
 }
 });
 }
})();
</script>

@endsection
