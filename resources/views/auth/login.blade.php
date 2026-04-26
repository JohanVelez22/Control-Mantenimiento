@extends('layouts.app')

@section('content')
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
})();
</script>

<div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8 mt-10">
    <h2 class="text-2xl font-bold mb-6 text-center">Iniciar Sesión</h2>
    
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Contraseña</label>
            <input type="password" name="password" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Entrar
        </button>
    </form>
    
    <p class="mt-4 text-center text-sm">
        ¿No tienes cuenta? <a href="{{ route('register') }}" class="text-blue-500">Regístrate aquí</a>
    </p>
</div>


@endsection
