@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl shadow-xl border border-white/20 dark:border-gray-700/50 p-8">
    <h2 class="text-2xl font-bold mb-6">Editar Usuario: {{ $user->name }}</h2>

    <form method="POST" action="{{ route('usuarios.update', $user->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Nombre --}}
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Nombre Completo</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('name') border-red-500 @enderror">
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('email') border-red-500 @enderror">
            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Rol --}}
        @if(auth()->user()->isAdmin())
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Rol del Sistema</label>
            <select name="role" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="tecnico" {{ old('role', $user->role) == 'tecnico' ? 'selected' : '' }}>Técnico</option>
                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
                <option value="invitado" {{ old('role', $user->role) == 'invitado' ? 'selected' : '' }}>Invitado</option>
            </select>
        </div>
        @else
        <input type="hidden" name="role" value="{{ $user->role }}">
        @endif

        {{-- Foto de Perfil --}}
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Foto de Perfil</label>
            @if($user->photo)
                <div class="mb-3">
                    <img src="{{ asset('storage/' . $user->photo) }}" width="100" height="100" class="rounded-full object-cover border-2 border-gray-300 dark:border-gray-600">
                </div>
            @endif
            <input type="file" name="photo" accept="image/*" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('photo') border-red-500 @enderror">
            <p class="text-xs text-gray-500 mt-1">Selecciona una imagen para actualizar tu foto actual.</p>
            @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Estado (IMPORTANTE: name="active") --}}
        @if(auth()->user()->isAdmin())
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Estado de la Cuenta</label>
            <select name="active" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="1" {{ old('active', $user->active) ? 'selected' : '' }}>Activo (Permitir acceso)</option>
                <option value="0" {{ !old('active', $user->active) ? 'selected' : '' }}>Inactivo (Bloquear acceso)</option>
            </select>
        </div>
        @else
        <input type="hidden" name="active" value="{{ $user->active }}">
        @endif

        <hr class="my-6 border-gray-200 dark:border-gray-700">

        {{-- Password --}}
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2 text-gray-500">Cambiar Contraseña del Usuario (opcional)</label>
            <input type="password" id="password" name="password" placeholder="Dejar en blanco para no cambiar" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('password') border-red-500 @enderror">
            <ul id="password-requirements" class="mt-2 text-sm text-gray-500 dark:text-gray-400 space-y-1 hidden">
                <li id="req-length" class="flex items-center"><span class="mr-2">✗</span> Mínimo 8 caracteres</li>
                <li id="req-case" class="flex items-center"><span class="mr-2">✗</span> Mayúsculas y minúsculas</li>
                <li id="req-number" class="flex items-center"><span class="mr-2">✗</span> Al menos un número</li>
            </ul>
            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2 text-gray-500">Confirmar Nueva Contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <p id="req-match" class="mt-1 text-sm text-red-500 hidden">Las contraseñas no coinciden</p>
        </div>

        <hr class="my-6 border-gray-200 dark:border-gray-700">



        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('usuarios.index') }}" class="w-1/2 text-center bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">Cancelar</a>
            <button type="submit" class="w-1/2 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20">
                Actualizar Usuario
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
            requirementsList.classList.remove('hidden');
            updateRequirement(reqLength, val.length >= 8);
            updateRequirement(reqCase, /[a-z]/.test(val) && /[A-Z]/.test(val));
            updateRequirement(reqNumber, /\d/.test(val));
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

    // Prevent form submission if requirements are not met
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
                        alert('Debes ingresar una nueva contraseña si deseas usar el campo de confirmación.');
                        passwordInput.focus();
                    } else if (!isMatchValid) {
                        alert('Las contraseñas no coinciden.');
                        confirmInput.focus();
                    } else {
                        alert('La nueva contraseña no cumple con los requisitos de seguridad (8 caracteres, mayúsculas y números).');
                        passwordInput.focus();
                    }
                }
            }
        });
    }
});
</script>
@endsection
