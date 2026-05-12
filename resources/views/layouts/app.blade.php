<!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Mantenimiento</title>
    <!-- Usamos Tailwind via CDN para ejecución rápida -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <!-- Script para evitar el parpadeo (FOUC) al cargar el modo oscuro -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-900 dark:bg-gray-900 dark:text-gray-100">

    <!-- Navegación -->
    @auth
    <nav class="bg-white dark:bg-gray-800 shadow p-4 flex justify-between items-center">
        
        <!-- Izquierda: Logo y Menú Principal -->
        <div class="flex items-center space-x-6">
            <div class="text-xl font-bold">
                <a href="{{ route('dashboard') }}">⚙️ Control Mantenimientos</a>
            </div>
            
            <!-- Enlaces del menú -->
            <div class="hidden md:flex space-x-4">
                <a href="{{ route('clientes.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">👤 Clientes</a>
                <a href="{{ route('equipos.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">🖥️ Equipos</a>
                <a href="{{ route('tecnicos.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">🛠️ Técnicos</a>
                <a href="{{ route('mantenimientos.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">📋 Mantenimientos</a>
                <a href="{{ route('mantenimientos.reportes') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">📈 Reportes</a>
                <a href="{{ route('usuarios.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">👨🏻‍💻 Usuarios</a>
            </div>
        </div>

        <!-- Derecha: Usuario, Modo Oscuro y Logout -->
        <div class="flex items-center space-x-4">
            @if(auth()->user()->photo)
                <img src="{{ asset('storage/' . auth()->user()->photo) }}" width="32" height="32" class="rounded-full object-cover border border-gray-300 dark:border-gray-600">
            @endif
            <span class="text-sm hidden sm:inline-block">Bienvenido, {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</span>
            
            <!-- Botón Modo Oscuro -->
            <button id="theme-toggle" class="p-2 bg-gray-200 dark:bg-gray-700 rounded-lg focus:outline-none">
                🌓
            </button>

            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-bold">Salir</button>
            </form>
        </div>
    </nav>
    @endauth

    <!-- Contenido Principal -->
    <main class="container mx-auto p-4 mt-4">
        <!-- Mensajes de Error Globales -->
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any() && !request()->routeIs('*.create', '*.edit', 'login', 'register'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @yield('content')
    </main>

    <!-- Lógica del botón de Modo Oscuro -->
    <script>
        var themeToggleBtn = document.getElementById('theme-toggle');
        if(themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function() {
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
    </script>
    <!-- Global Form Validation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var forms = document.querySelectorAll('form');
            
            forms.forEach(function(form) {
                // Ignore logout form
                if(form.action && form.action.includes('logout')) return;

                // Disable default browser validation tooltips
                form.setAttribute('novalidate', true);

                form.addEventListener('submit', function(event) {
                    var isValid = true;
                    var elements = form.querySelectorAll('input, select, textarea');
                    
                    elements.forEach(function(el) {
                        // Limpiar errores previos
                        var prevError = el.nextElementSibling;
                        if (prevError && prevError.classList.contains('custom-error-msg')) {
                            prevError.remove();
                        }
                        el.classList.remove('border-red-500');

                        // Si el campo es inválido según el HTML5
                        if (!el.checkValidity()) {
                            isValid = false;
                            el.classList.add('border-red-500');
                            
                            var errorMsg = document.createElement('p');
                            errorMsg.classList.add('custom-error-msg', 'text-red-500', 'text-xs', 'mt-1');
                            
                            if (el.validity.valueMissing) {
                                errorMsg.textContent = 'Este campo es obligatorio.';
                            } else if (el.validity.typeMismatch) {
                                if (el.type === 'email') errorMsg.textContent = 'Ingresa un correo electrónico válido.';
                                else errorMsg.textContent = 'Formato no válido.';
                            } else if (el.validity.tooShort) {
                                errorMsg.textContent = 'El texto es muy corto.';
                            } else {
                                errorMsg.textContent = el.validationMessage;
                            }
                            
                            // Insertar debajo del campo
                            el.parentNode.insertBefore(errorMsg, el.nextSibling);
                        }
                    });

                    if (!isValid) {
                        event.preventDefault(); // Detener recarga de la página
                    }
                });

                // Remover error al escribir
                var elements = form.querySelectorAll('input, select, textarea');
                elements.forEach(function(el) {
                    el.addEventListener('input', function() {
                        if (el.checkValidity()) {
                            el.classList.remove('border-red-500');
                            var prevError = el.nextElementSibling;
                            if (prevError && prevError.classList.contains('custom-error-msg')) {
                                prevError.remove();
                            }
                        }
                    });
                });
            });
        });
    </script>
    <!-- Inactivity Timeout Script -->
    @auth
    <script>
        (function() {
            let time;
            const timeout = 180000; // 3 minutos

            function logout() {
                const logoutForm = document.querySelector('form[action="{{ route('logout') }}"]');
                if (logoutForm) logoutForm.submit();
            }

            function resetTimer() {
                clearTimeout(time);
                time = setTimeout(logout, timeout);
            }

            window.onload = resetTimer;
            document.onmousemove = resetTimer;
            document.onkeypress = resetTimer;
            document.onscroll = resetTimer;
            document.onclick = resetTimer;
        })();
    </script>
    @endauth
</body>
</html>
