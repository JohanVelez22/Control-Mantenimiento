@extends('layouts.guest')

@section('title', 'Cambiar contraseña · WolfCore')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md">

        <div class="flex flex-col items-center mb-5 text-center">
            <div class="w-16 h-16 mb-3 flex items-center justify-center rounded-2xl bg-amber-500/10 border border-amber-500/30 text-3xl" aria-hidden="true">🔐</div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">Cambie su contraseña</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                Por seguridad, <strong>{{ auth()->user()->name }}</strong> debe definir una contraseña propia antes de usar el sistema.
                La contraseña actual es predeterminada o fue asignada por un administrador.
            </p>
        </div>

        <div class="glass-card px-6 py-6 md:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm font-bold text-red-600 dark:text-red-400" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.actualizar') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password" required autofocus autocomplete="current-password"
                           class="glass-input w-full text-base py-3 px-4 @error('current_password') border-red-500 @enderror">
                    @error('current_password') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Nueva contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password" minlength="8"
                           aria-describedby="password-ayuda"
                           class="glass-input w-full text-base py-3 px-4 @error('password') border-red-500 @enderror">
                    <p id="password-ayuda" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Mínimo 8 caracteres, con mayúsculas, minúsculas y números. No use contraseñas de otros sistemas.
                    </p>
                    @error('password') <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-bold text-slate-700 dark:text-slate-200">Confirmar nueva contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                           class="glass-input w-full text-base py-3 px-4">
                </div>

                <button type="submit" class="btn-primary w-full justify-center py-3 font-bold">Guardar y continuar</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 underline">Cerrar sesión</button>
            </form>
        </div>
    </div>
</div>
@endsection
