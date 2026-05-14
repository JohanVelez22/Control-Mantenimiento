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
    <style>
        .search-input:focus {
            outline: none !important;
            box-shadow: none !important;
        }
    </style>
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

        <!-- Derecha: Usuario, Modo Oscuro, Logout y Hamburguesa -->
        <div class="flex items-center space-x-3">
            @if(auth()->user()->photo)
                <img src="{{ asset('storage/' . auth()->user()->photo) }}" width="32" height="32" class="rounded-full object-cover border border-gray-300 dark:border-gray-600">
            @endif
            <span class="text-sm hidden xl:inline-block">Bienvenido, {{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</span>
            
            <!-- Botón Modo Oscuro -->
            <button type="button" id="theme-toggle" class="p-2 bg-gray-200 dark:bg-gray-700 rounded-lg focus:outline-none" aria-label="Cambiar tema claro u oscuro">
                🌓
            </button>

            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-red-500/20">Salir</button>
            </form>

            <!-- Botón Hamburguesa (solo móvil/tablet) -->
            <button type="button" id="mobile-menu-btn" class="lg:hidden p-2 bg-gray-200 dark:bg-gray-700 rounded-lg focus:outline-none" aria-label="Abrir menú">
                <svg id="icon-menu" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg id="icon-close" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </nav>

    <!-- Menú móvil desplegable -->
    <div id="mobile-menu" class="lg:hidden hidden bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border-b border-gray-200/50 dark:border-gray-700/50 shadow-md sticky top-[73px] z-40">
        <div class="flex flex-col p-4 space-y-1">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">⚙️ Dashboard</a>
            <a href="{{ route('clientes.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">👤 Clientes</a>
            <a href="{{ route('equipos.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">🖥️ Equipos</a>
            <a href="{{ route('tecnicos.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">🛠️ Técnicos</a>
            <a href="{{ route('mantenimientos.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">📋 Mantenimientos</a>
            <a href="{{ route('mantenimientos.reportes') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">📈 Reportes</a>
            <a href="{{ route('usuarios.index') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition-all">👨🏻‍💻 Usuarios</a>
        </div>
    </div>
    @endauth

    <!-- Contenido Principal -->
    <main class="container mx-auto p-4 mt-4">
        @yield('content')
    </main>

    <!-- Contenedor de Toasts -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-3 max-w-sm w-full pointer-events-none"></div>

    <!-- Modal de Confirmación de Eliminación -->
    <div id="delete-modal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
        <!-- Overlay -->
        <div id="delete-modal-overlay" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <!-- Tarjeta -->
        <div class="relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-2xl p-6 max-w-sm w-full transform transition-all duration-300 scale-95 opacity-0" id="delete-modal-card">
            <div class="text-center">
                <div class="text-5xl mb-4">🗑️</div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">¿Confirmar eliminación?</h3>
                <p id="delete-modal-message" class="text-gray-500 dark:text-gray-400 text-sm mb-6">Esta acción no se puede deshacer.</p>
            </div>
            <div class="flex gap-3">
                <button type="button" id="delete-modal-cancel" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                    Cancelar
                </button>
                <button type="button" id="delete-modal-confirm" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-all shadow-lg shadow-red-500/30">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // --- BÚSQUEDA RÁPIDA EN TABLAS (cliente-side) ---
        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            if (!input) return;
            input.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
                rows.forEach(row => {
                    if (row.cells.length <= 1) return; // fila empty-state
                    row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
                });
            });
        }

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

        // --- MENÚ MÓVIL (HAMBURGUESA) ---
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const iconMenu = document.getElementById('icon-menu');
            const iconClose = document.getElementById('icon-close');

            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const isOpen = !mobileMenu.classList.contains('hidden');
                    mobileMenu.classList.toggle('hidden');
                    iconMenu.classList.toggle('hidden', !isOpen);
                    iconClose.classList.toggle('hidden', isOpen);
                });

                // Cerrar menú al hacer click en un enlace
                mobileMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', () => {
                        mobileMenu.classList.add('hidden');
                        iconMenu.classList.remove('hidden');
                        iconClose.classList.add('hidden');
                    });
                });
            }
        });

        // --- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN ---
        let _deleteFormPending = null;

        function confirmDelete(form, message) {
            _deleteFormPending = form;
            const modal = document.getElementById('delete-modal');
            const card = document.getElementById('delete-modal-card');
            document.getElementById('delete-modal-message').textContent = message || 'Esta acción no se puede deshacer.';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            requestAnimationFrame(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            });
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            const card = document.getElementById('delete-modal-card');
            card.classList.add('scale-95', 'opacity-0');
            card.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                _deleteFormPending = null;
            }, 200);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('delete-modal-cancel').addEventListener('click', closeDeleteModal);
            document.getElementById('delete-modal-overlay').addEventListener('click', closeDeleteModal);
            document.getElementById('delete-modal-confirm').addEventListener('click', function() {
                if (_deleteFormPending) {
                    _deleteFormPending.setAttribute('data-confirmed', 'true');
                    _deleteFormPending.submit();
                }
            });
        });

        // --- VALIDACIÓN DE FORMULARIOS ---
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(form => {
                // Ignorar formulario de logout
                if(form.action && form.action.includes('logout')) return;

                // Interceptar formularios con data-confirm-delete
                if (form.dataset.confirmDelete) {
                    form.addEventListener('submit', function(e) {
                        if (!form.getAttribute('data-confirmed')) {
                            e.preventDefault();
                            confirmDelete(form, form.dataset.confirmDelete);
                        }
                    });
                    return;
                }

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
