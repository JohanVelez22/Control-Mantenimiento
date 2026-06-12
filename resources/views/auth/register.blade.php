@extends('layouts.app')

@section('content')

{{-- Botón modo oscuro --}}
<div class="absolute top-5 right-5 z-10">
    <button id="theme-toggle-login"
        class="p-2 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-gray-200 dark:border-gray-700 shadow-sm hover:scale-105 transition-transform"
        title="Cambiar tema" aria-label="Cambiar tema">🌓</button>
</div>

<div class="w-full max-w-lg px-6 py-8">

    {{-- Header --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-700 text-2xl shadow-xl shadow-green-500/30 mb-4">👤</div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white">Registro de Usuario</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Crea tu cuenta para acceder al sistema</p>
    </div>

    {{-- Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8">

        {{-- Errores generales --}}
        @if ($errors->any())
            <div class="mb-5 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2"><span>⚠️</span> {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            {{-- Nombre --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nombre Completo</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Tu nombre completo"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                           transition-all text-sm">
            </div>

            {{-- Email --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Correo Electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="usuario@empresa.com"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                           transition-all text-sm">
            </div>

            {{-- Rol --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Rol en el Sistema</label>
                <select name="role" id="role" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                           transition-all text-sm">
                    <option value="invitado" selected>Invitado</option>
                    <option value="tecnico">Técnico</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            {{-- Contraseña --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                           transition-all text-sm">
                <ul id="password-requirements" class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-1 hidden">
                    <li id="req-length" class="flex items-center gap-1.5"><span>✗</span> Mínimo 8 caracteres</li>
                    <li id="req-case"   class="flex items-center gap-1.5"><span>✗</span> Mayúsculas y minúsculas</li>
                    <li id="req-number" class="flex items-center gap-1.5"><span>✗</span> Al menos un número</li>
                </ul>
            </div>

            {{-- Confirmar contraseña --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Confirmar Contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                           transition-all text-sm">
                <p id="req-match" class="mt-1 text-xs text-red-500 hidden">Las contraseñas no coinciden</p>
            </div>

            {{-- Clave autorización --}}
            <div id="auth-key-wrap">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Clave de Autorización
                    <span class="font-normal text-gray-400">(solo Administrador o Técnico)</span>
                </label>
                <input type="password" name="admin_password" id="admin_password" autocomplete="off"
                    placeholder="Invitado: dejar vacío"
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600
                           bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500
                           transition-all text-sm @error('admin_password') border-red-500 @enderror">
                @error('admin_password')
                    <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                @else
                    <p class="text-xs text-gray-400 mt-1">Los roles Invitado no requieren esta clave.</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button type="submit"
                class="w-full py-3 px-4 mt-2
                       bg-gradient-to-r from-green-500 to-emerald-600
                       hover:from-green-600 hover:to-emerald-700
                       text-white font-bold rounded-xl
                       shadow-lg shadow-green-500/30
                       transition-all duration-200 hover:scale-[1.02] active:scale-100
                       text-sm tracking-wide">
                Crear Cuenta →
            </button>
        </form>

        {{-- Enlace login --}}
        <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="text-blue-500 hover:text-blue-600 dark:text-blue-400 font-semibold hover:underline ml-1">
                    Inicia sesión
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

    // Validación de contraseña
    var passwordInput = document.getElementById('password');
    var confirmInput  = document.getElementById('password_confirmation');
    var reqList   = document.getElementById('password-requirements');
    var reqLength = document.getElementById('req-length');
    var reqCase   = document.getElementById('req-case');
    var reqNumber = document.getElementById('req-number');
    var reqMatch  = document.getElementById('req-match');

    function setReq(el, valid) {
        var span = el.querySelector('span');
        el.classList.toggle('text-green-500', valid);
        el.classList.toggle('text-gray-500', !valid);
        if (span) span.textContent = valid ? '✓' : '✗';
    }

    if (passwordInput) {
        passwordInput.addEventListener('focus', function() { reqList.classList.remove('hidden'); });
        passwordInput.addEventListener('input', function() {
            var v = passwordInput.value;
            setReq(reqLength, v.length >= 8);
            setReq(reqCase,   /[a-z]/.test(v) && /[A-Z]/.test(v));
            setReq(reqNumber, /\d/.test(v));
            checkMatch();
        });
    }

    function checkMatch() {
        if (confirmInput.value.length > 0) {
            reqMatch.classList.toggle('hidden', passwordInput.value === confirmInput.value);
        } else {
            reqMatch.classList.add('hidden');
        }
    }

    if (confirmInput) confirmInput.addEventListener('input', checkMatch);

    var form = document.querySelector('form');
    if (form && passwordInput) {
        form.addEventListener('submit', function(e) {
            var v = passwordInput.value;
            var ok = v.length >= 8 && /[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && v === confirmInput.value;
            if (!ok) {
                e.preventDefault();
                reqList.classList.remove('hidden');
                if (typeof showToast === 'function') {
                    showToast('Asegúrate de cumplir con todos los requisitos de la contraseña.', 'error');
                }
            }
        });
    }
})();
</script>

@endsection
