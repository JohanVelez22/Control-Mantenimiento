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
 <div class="flex justify-center mb-4">
     <div class="text-[24px] font-black tracking-widest font-logo flex items-center gap-2">
         <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
         <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
     </div>
 </div>

 {{-- Header --}}
 <div class="text-center mb-2">
 <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-3xl shadow-xl mb-4">🔧</div>
 <h1 class="text-xl font-black text-gray-900 dark:text-white">Iniciar Sesión</h1>
 <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tus credenciales para acceder al sistema</p>
 </div>

@php
    $initialLockout = isset($lockoutSeconds) && $lockoutSeconds > 0 ? (int)$lockoutSeconds : (session('lockout_seconds') ? (int)session('lockout_seconds') : 0);
@endphp

 {{-- Card --}}
 <div class="glass-card px-6 pb-8 pt-2 md:px-8 md:pb-8 md:pt-2">

 <form method="POST" action="{{ route('login') }}" class="space-y-6">
 @csrf

 {{-- Usuario / Correo --}}
 <div>
 <label for="email" class="mb-1.5 flex items-center gap-1.5 text-sm font-bold text-slate-700 dark:text-slate-200" style="margin-bottom: 5px;">
 <span class="flex-shrink-0">📧</span>
 <span>Usuario o Correo Electrónico</span>
 </label>
 <input type="text" id="email" name="email" value="{{ old('email') }}"
 required autofocus placeholder="usuario o correo@..."
 class="glass-input w-full text-base py-3 px-4 focus:placeholder-transparent {{ $initialLockout > 0 ? 'opacity-60 cursor-not-allowed' : '' }}" autocomplete="username" {{ $initialLockout > 0 ? 'disabled' : '' }}>
 @error('email') <p id="email-error-msg" class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 {{-- Contraseña --}}
 <div>
 <label for="password" class="mb-1.5 flex items-center gap-1.5 text-sm font-bold text-slate-700 dark:text-slate-200" style="margin-bottom: 5px;">
 <span class="flex-shrink-0">🔑</span>
 <span>Contraseña</span>
 </label>
 <input type="password" id="password" name="password"
 required placeholder="••••••••"
 class="glass-input w-full text-base py-3 px-4 focus:placeholder-transparent {{ $initialLockout > 0 ? 'opacity-60 cursor-not-allowed' : '' }}" {{ $initialLockout > 0 ? 'disabled' : '' }}>
 @error('password') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
 </div>

 {{-- Remember Me --}}
 <div class="flex items-center gap-2 cursor-pointer select-none">
 <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-0 focus:ring-offset-0 focus:outline-none dark:bg-gray-700 dark:border-gray-600 cursor-pointer" style="outline: none !important; box-shadow: none !important;" {{ $initialLockout > 0 ? 'disabled' : '' }}>
 <label for="remember" class="text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer select-none">Mantener sesión iniciada</label>
 </div>

 {{-- Submit --}}
 <div>
     <button type="submit" id="login-submit-btn" class="w-full btn-primary py-3 justify-center text-sm transition-all duration-200 {{ $initialLockout > 0 ? 'opacity-60 cursor-not-allowed' : '' }}" {{ $initialLockout > 0 ? 'disabled' : '' }}>
         <span id="login-btn-text">{{ $initialLockout > 0 ? '🔒 Bloqueado' : 'Entrar al Sistema →' }}</span>
     </button>
 </div>
 </form>

 </div>
</div>

<style>
.glass-input:focus::placeholder,
.glass-input:focus::-webkit-input-placeholder,
.glass-input:focus::-moz-placeholder,
.glass-input:focus:-ms-input-placeholder {
  color: transparent !important;
  opacity: 0 !important;
}
#remember:focus {
  outline: none !important;
  box-shadow: none !important;
}
</style>

<script>
(function(){
  // Ocultar placeholder inmediatamente al hacer clic / focus
  document.querySelectorAll('.glass-input').forEach(function(input) {
    var originalPlaceholder = input.getAttribute('placeholder') || '';
    input.addEventListener('focus', function() {
      this.setAttribute('placeholder', '');
    });
    input.addEventListener('blur', function() {
      this.setAttribute('placeholder', originalPlaceholder);
    });
  });

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

  // Temporizador interactivo de cuenta regresiva de bloqueo en botón
  var initialSeconds = {{ $initialLockout }};
  if (initialSeconds > 0) {
    var emailInput = document.getElementById('email');
    var passwordInput = document.getElementById('password');
    var submitBtn = document.getElementById('login-submit-btn');
    var btnText = document.getElementById('login-btn-text');
    var errorMsg = document.getElementById('email-error-msg');

    function formatTime(s) {
      var m = Math.floor(s / 60);
      var rem = s % 60;
      return (m < 10 ? '0' : '') + m + ':' + (rem < 10 ? '0' : '') + rem;
    }

    function lockUI(seconds) {
      if (emailInput) {
        emailInput.disabled = true;
        emailInput.classList.add('opacity-60', 'cursor-not-allowed');
      }
      if (passwordInput) {
        passwordInput.disabled = true;
        passwordInput.classList.add('opacity-60', 'cursor-not-allowed');
      }
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
      }
      if (btnText) btnText.textContent = '🔒 Bloqueado (' + formatTime(seconds) + ')';
    }

    function unlockUI() {
      if (emailInput) {
        emailInput.disabled = false;
        emailInput.classList.remove('opacity-60', 'cursor-not-allowed');
        emailInput.focus();
      }
      if (passwordInput) {
        passwordInput.disabled = false;
        passwordInput.classList.remove('opacity-60', 'cursor-not-allowed');
      }
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
      }
      if (btnText) btnText.textContent = 'Entrar al Sistema →';
      if (!errorMsg && emailInput) {
        errorMsg = document.createElement('div');
        errorMsg.id = 'email-error-msg';
        emailInput.parentNode.appendChild(errorMsg);
      }
      if (errorMsg) {
        errorMsg.className = 'text-emerald-500 text-xs font-bold mt-1.5 flex items-start gap-1.5 leading-5';
        errorMsg.innerHTML = '<span class="flex-shrink-0 inline-flex items-center justify-center h-5 select-none">✅</span><span class="leading-5">Bloqueo finalizado. Ya puedes ingresar tus credenciales.</span>';
      }
    }

    var remaining = initialSeconds;
    lockUI(remaining);

    var interval = setInterval(function() {
      remaining--;
      if (remaining <= 0) {
        clearInterval(interval);
        unlockUI();
      } else {
        lockUI(remaining);
      }
    }, 1000);
  }
})();
</script>

@endsection
