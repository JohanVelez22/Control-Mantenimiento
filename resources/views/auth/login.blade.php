@extends('layouts.app')

@section('content')

{{-- Botón modo oscuro --}}
<div class="absolute top-5 right-5 z-10">
 <button id="theme-toggle-login" type="button"
 class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg"
 title="Cambiar tema" aria-label="Cambiar tema">
    <span class="dark:hidden">☀️</span>
    <span class="hidden dark:inline">🌙</span>
 </button>
</div>

<div class="w-full max-w-md px-6 pb-16">

 {{-- Logo TECNI SYSTEMAS --}}
 <div class="flex justify-center mb-8">
     <div class="text-[24px] font-black tracking-widest font-logo flex items-center gap-2">
         <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
         <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
     </div>
 </div>

 {{-- Header --}}
 <div class="text-center mb-6">
 <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-3xl shadow-xl mb-4">🔧</div>
 <h1 class="text-xl font-black text-gray-900 dark:text-white">Iniciar Sesión</h1>
 <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tus credenciales para acceder al sistema</p>
 </div>

 {{-- Card --}}
 <div class="glass-card px-6 pb-8 pt-5 md:px-8 md:pb-8 md:pt-6">

 {{-- Errores --}}
 @if ($errors->any())
 <div class="mb-5 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm space-y-1">
 @foreach ($errors->all() as $error)
 <p class="flex items-center gap-2"><span>⚠️</span> {{ $error }}</p>
 @endforeach
 </div>
 @endif

 <form method="POST" action="{{ route('login') }}" class="space-y-6">
 @csrf

 {{-- Email --}}
 <div>
 <label for="email" class="mb-3 flex items-center gap-3 text-sm font-bold text-slate-700 dark:text-slate-200">
 <span class="flex-shrink-0">📧</span>
 <span>Correo Electrónico</span>
 </label>
 <input type="email" id="email" name="email" value="{{ old('email') }}"
 required autofocus placeholder="usuario@empresa.com"
 class="glass-input mt-1 w-full text-base py-3 px-4">
 @error('email') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 {{-- Contraseña --}}
 <div>
 <label for="password" class="mb-3 flex items-center gap-3 text-sm font-bold text-slate-700 dark:text-slate-200">
 <span class="flex-shrink-0">🔑</span>
 <span>Contraseña</span>
 </label>
 <input type="password" id="password" name="password"
 required placeholder="••••••••"
 class="glass-input mt-1 w-full text-base py-3 px-4">
 @error('password') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 {{-- Remember Me --}}
 <div class="flex items-center gap-2">
 <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
 <label for="remember" class="text-sm font-medium text-gray-900 dark:text-gray-300">Mantener sesión iniciada</label>
 </div>

 {{-- Submit --}}
 <button type="submit" class="w-full btn-primary py-3 justify-center text-sm">
 Entrar al Sistema →
  </button>
  </form>

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
 var isToggling = false;
 btn.addEventListener('click', function(e) {
 e.preventDefault();
 if (isToggling) return;
 isToggling = true;
 setTimeout(function() { isToggling = false; }, 300);
 
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
