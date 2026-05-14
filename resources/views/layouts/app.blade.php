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
<body class="bg-gradient-to-br from-gray-50 to-gray-200 dark:from-gray-900 dark:to-gray-800 text-gray-900 dark:text-gray-100 min-h-screen">

    <!-- Navegación -->
    @auth
    <nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-sm border-b border-gray-200/50 dark:border-gray-700/50 p-4 flex flex-wrap justify-between items-center sticky top-0 z-50 gap-4">
        
        <!-- Izquierda: Logo y Menú Principal -->
        <div class="flex items-center space-x-6">
            <div class="text-xl font-bold whitespace-nowrap">
                <a href="{{ route('dashboard') }}">⚙️ Control Mantenimientos</a>
            </div>
            
            <!-- Enlaces del menú -->
            <div class="hidden lg:flex space-x-4">
                <a href="{{ route('clientes.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">👤 Clientes</a>
                <a href="{{ route('equipos.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">🖥️ Equipos</a>
                <a href="{{ route('tecnicos.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">🛠️ Técnicos</a>
                <a href="{{ route('mantenimientos.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">📋 Mantenimientos</a>
                <a href="{{ route('mantenimientos.reportes') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">📈 Reportes</a>
                <a href="{{ route('usuarios.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-blue-500 font-medium">👨🏻‍💻 Usuarios</a>
            </div>
        </div>

        <!-- Centro: Buscador Global -->
        <div class="flex-grow max-w-md hidden sm:block">
            <form action="{{ route('mantenimientos.reportes') }}" method="GET" class="relative group">
                <input type="text" name="search" placeholder="Buscar Orden o Cliente..." 
                    class="w-full bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl py-2 pl-10 pr-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm group-hover:border-gray-300 dark:group-hover:border-gray-500"
                    value="{{ request('search') }}">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    🔍
                </div>
            </form>
        </div>

        <!-- Derecha: Usuario, Modo Oscuro y Logout -->
        <div class="flex items-center space-x-4">
            @if(auth()->user()->photo)
                <img src="{{ asset('storage/' . auth()->user()->photo) }}" width="32" height="32" class="rounded-full object-cover border border-gray-300 dark:border-gray-600">
            @endif
            <span class="text-sm hidden xl:inline-block">Bienvenido, {{ auth()->user()->name }}</span>
            
            <!-- Botón Modo Oscuro -->
            <button type="button" id="theme-toggle" class="p-2 bg-gray-200 dark:bg-gray-700 rounded-lg focus:outline-none" aria-label="Cambiar tema claro u oscuro">
                🌓
            </button>

            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-red-500/20">Salir</button>
            </form>
        </div>
    </nav>
    @endauth

    <!-- Contenido Principal -->
    <main class="container mx-auto p-4 mt-4">
        @yield('content')
    </main>

    <!-- Contenedor de Toasts -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-3 max-w-sm w-full pointer-events-none"></div>

    <!-- Scripts -->
    <script>
        // --- SISTEMA DE TOASTS ---
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgClass = type === 'success' 
                ? 'bg-green-500/90 border-green-400 text-white' 
                : (type === 'error' ? 'bg-red-500/90 border-red-400 text-white' : 'bg-blue-500/90 border-blue-400 text-white');
            
            const icon = type === 'success' ? '✅' : (type === 'error' ? '⚠️' : 'ℹ️');

            toast.className = `${bgClass} backdrop-blur-md border rounded-2xl shadow-2xl p-4 flex items-center gap-3 transition-all duration-500 transform translate-y-10 opacity-0 pointer-events-auto`;
            toast.innerHTML = `
                <span class="text-xl">${icon}</span>
                <div class="flex-grow">
                    <p class="text-sm font-bold">${message}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white/60 hover:text-white transition-colors">✕</button>
            `;

            container.appendChild(toast);

            // Trigger animation
            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-10', 'opacity-0');
            });

            // Auto-remove
            setTimeout(() => {
                toast.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }

        // Detectar mensajes de Laravel
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', () => showToast("{{ session('success') }}", 'success'));
        @endif

        @if(session('error'))
            document.addEventListener('DOMContentLoaded', () => showToast("{{ session('error') }}", 'error'));
        @endif

        @if($errors->any())
            document.addEventListener('DOMContentLoaded', () => {
                @foreach($errors->all() as $error)
                    showToast("{{ $error }}", 'error');
                @endforeach
            });
        @endif

        // --- LÓGICA MODO OSCURO ---
        const themeToggleBtn = document.getElementById('theme-toggle');
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

        // --- VALIDACIÓN DE FORMULARIOS ---
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(form => {
                if(form.action && form.action.includes('logout')) return;
                form.setAttribute('novalidate', true);
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    form.querySelectorAll('input, select, textarea').forEach(el => {
                        const prevError = el.nextElementSibling;
                        if (prevError && prevError.classList.contains('custom-error-msg')) prevError.remove();
                        el.classList.remove('border-red-500');

                        if (!el.checkValidity()) {
                            isValid = false;
                            el.classList.add('border-red-500');
                            const errorMsg = document.createElement('p');
                            errorMsg.className = 'custom-error-msg text-red-500 text-xs mt-1 font-semibold';
                            errorMsg.textContent = el.validity.valueMissing ? 'Obligatorio' : el.validationMessage;
                            el.parentNode.insertBefore(errorMsg, el.nextSibling);
                        }
                    });
                    if (!isValid) {
                        e.preventDefault();
                        showToast('Por favor corrige los errores del formulario', 'error');
                    }
                });
            });
        });

        // --- TIMEOUT DE INACTIVIDAD ---
        @auth
        (function() {
            let time;
            function resetTimer() {
                clearTimeout(time);
                time = setTimeout(() => {
                    const logoutForm = document.querySelector('form[action="{{ route('logout') }}"]');
                    if (logoutForm) logoutForm.submit();
                }, 180000); // 3 minutos
            }
            window.onload = resetTimer;
            ['mousemove', 'keypress', 'scroll', 'click'].forEach(e => window.addEventListener(e, resetTimer));
        })();
        @endauth
    </script>
</body>
</html>
