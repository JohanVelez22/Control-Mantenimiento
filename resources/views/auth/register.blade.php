@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 mt-10">
    <h2 class="text-2xl font-bold mb-6 text-center">Registro de Usuario</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Nombre Completo</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Rol en el Sistema</label>
            <select name="role" id="role" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                <option value="invitado" selected>Invitado</option>
                <option value="tecnico">Técnico</option>
                <option value="admin">Administrador</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Contraseña</label>
            <input type="password" id="password" name="password" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            <ul id="password-requirements" class="mt-2 text-sm text-gray-500 dark:text-gray-400 space-y-1 hidden">
                <li id="req-length" class="flex items-center"><span class="mr-2">✗</span> Mínimo 8 caracteres</li>
                <li id="req-case" class="flex items-center"><span class="mr-2">✗</span> Mayúsculas y minúsculas</li>
                <li id="req-number" class="flex items-center"><span class="mr-2">✗</span> Al menos un número</li>
            </ul>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Confirmar Contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            <p id="req-match" class="mt-1 text-sm text-red-500 hidden">Las contraseñas no coinciden</p>
        </div>

        <div class="mb-4" id="auth-key-wrap">
            <label class="block text-sm font-medium mb-2">Clave de autorización <span class="text-gray-500 dark:text-gray-400 font-normal">(solo Administrador o Técnico)</span></label>
            <input type="password" name="admin_password" id="admin_password" autocomplete="off" placeholder="Invitado: dejar vacío" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Los roles invitado no requieren esta clave.</p>
        </div>

        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Registrarse</button>
    </form>

    <p class="mt-4 text-center text-sm">
        ¿Ya tienes una cuenta? <a href="{{ route('login') }}" class="text-blue-500">Inicia sesión</a>
    </p>
</div>

<!-- Botón de Modo Oscuro (pequeño y sencillo) -->
    <div class="absolute top-4 right-4">
        <button id="theme-toggle-login" class="p-2 bg-gray-200 dark:bg-gray-700 rounded" title="Cambiar tema" aria-label="Cambiar tema">
            🌓
        </button>
    </div>

    <!-- Formulario de login (tu diseño existente continúa aquí) -->
    <div class="w-full max-w-md">
        <!-- ... tu formulario de login ... -->
    </div>

<script>
(function(){
  // Inicializar tema
  if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }

  var btn = document.getElementById('theme-toggle-login');
  if (btn) {
    btn.addEventListener('click', function() {
      if (localStorage.getItem('color-theme')) {
        if (localStorage.getItem('color-theme') === 'light') {
          document.documentElement.classList.add('dark');
          localStorage.setItem('color-theme', 'dark');
        } else {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('color-theme', 'light');
        }
      } else {
        if (document.documentElement.classList.contains('dark')) {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('color-theme', 'light');
        } else {
          document.documentElement.classList.add('dark');
          localStorage.setItem('color-theme', 'dark');
        }
      }
    });
  }
  // Dynamic Password Validation
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
          // Length >= 8
          updateRequirement(reqLength, val.length >= 8);
          // Mixed case
          updateRequirement(reqCase, /[a-z]/.test(val) && /[A-Z]/.test(val));
          // Number
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
              alert('Por favor, asegúrate de cumplir con todos los requisitos de la contraseña antes de registrarte.');
          }
      });
  }

})();
</script>

@endsection
