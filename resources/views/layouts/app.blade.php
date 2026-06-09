<!DOCTYPE html>
<html lang="es">
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
    <!-- Anti-FOUC: aplicar tema y bloquear transiciones antes del primer render -->
    <script>
        document.documentElement.classList.add('preload');
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        /* Bloquear transiciones en carga inicial para evitar flash */
        .preload * { transition: none !important; }

        .search-input:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Transición suave al cambiar de modo claro/oscuro */
        body, nav, main, header, footer,
        table, thead, tbody, tfoot, tr, th, td,
        div, span, a, button, input, select, textarea, label, p, h1, h2, h3, h4, h5 {
            transition: background-color 0.3s ease, color 0.2s ease, border-color 0.25s ease;
        }

        /* Animación de entrada de página */
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page-enter {
            animation: pageFadeIn 0.28s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }

        /* Marcas de agua para impresión */
        .watermark-container { position: relative; }
        .watermark-container.anulado::after {
            content: "ANULADO";
            position: fixed; /* For print it stays centered on the page */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.15); /* text-red-500 muy transparente */
            z-index: 1000;
            pointer-events: none;
            white-space: nowrap;
        }
        @media print {
            .watermark-container.anulado::after {
                color: rgba(200, 0, 0, 0.2) !important;
                /* En impresión a veces es mejor absolute relative al body o pagina */
                position: absolute;
            }
        }

        /* Tablas Responsive: Tarjetas en móvil */
        @media (max-width: 767px) {
            .responsive-table, .responsive-table tbody, .responsive-table tr, .responsive-table td {
                display: block; width: 100%;
            }
            .responsive-table thead { display: none; }
            .responsive-table tr {
                margin-bottom: 1rem;
                border: 1px solid rgba(156, 163, 175, 0.3);
                border-radius: 0.75rem;
                overflow: hidden;
            }
            .responsive-table td {
                display: grid;
                grid-template-columns: minmax(100px, 35%) 1fr;
                gap: 1rem;
                align-items: center;
                text-align: right;
                padding: 0.75rem 1rem !important;
                border-bottom: 1px solid rgba(156, 163, 175, 0.2);
            }
            .responsive-table td:last-child { border-bottom: none; }
            .responsive-table td::before {
                content: attr(data-label);
                font-weight: 700;
                text-align: left;
                color: #6b7280;
            }
            html.dark .responsive-table td::before { color: #9ca3af; }
        }

        /* Barra de progreso de navegación */
        #nav-progress {
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
            z-index: 9999;
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 10px rgba(99,102,241,0.7);
            transition: width 0.35s ease;
            pointer-events: none;
        }
    </style>
</head>
<body class="flex bg-gradient-to-br from-gray-50 to-gray-200 dark:from-gray-900 dark:to-gray-800 text-gray-900 dark:text-gray-100 min-h-screen">

    @auth
    <!-- Estructura Principal con Sidebar -->
    <div class="flex flex-col lg:flex-row min-h-screen">
        
        <!-- Sidebar Izquierdo (Vertical) -->
        <aside class="w-64 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border-r border-gray-200/50 dark:border-gray-700/50 hidden lg:flex flex-col flex-shrink-0 sticky top-0 h-screen overflow-y-auto no-print">
            <div class="p-6 border-b border-gray-200/50 dark:border-gray-700/50">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    ⚙️ <span class="tracking-tight">Control Mant.</span>
                </a>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">📊</span> Dashboard
                </a>
                <a href="{{ route('clientes.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">👤</span> Clientes
                </a>
                <a href="{{ route('equipos.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">🖥️</span> Equipos
                </a>
                <a href="{{ route('tecnicos.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">🛠️</span> Técnicos
                </a>
                <a href="{{ route('stocks.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">📦</span> Inventario
                </a>
                <a href="{{ route('caja.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400 font-bold transition-all">
                    <span class="text-xl">💰</span> Caja
                </a>
                <a href="{{ route('cierre.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-teal-50 dark:hover:bg-teal-900/20 hover:text-teal-600 dark:hover:text-teal-400 font-bold transition-all">
                    <span class="text-xl">📈</span> Cierre
                </a>
                <a href="{{ route('electronicas.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:text-purple-600 dark:hover:text-purple-400 font-bold transition-all">
                    <span class="text-xl">⚡</span> Electrónica
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">📋</span> Órdenes
                </a>
                <a href="{{ route('reportes.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">📊</span> Reportes
                </a>
                <a href="{{ route('usuarios.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 font-bold transition-all">
                    <span class="text-xl">👨🏻‍💻</span> Usuarios
                </a>
            </nav>
        </aside>

        <!-- Contenedor Derecho (Topbar + Contenido) -->
        <div class="flex-1 flex flex-col min-w-0">
            
            <!-- Topbar (Accesos Rápidos Horizontales y Perfil) -->
            <header class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-sm border-b border-gray-200/50 dark:border-gray-700/50 p-4 flex justify-between items-center sticky top-0 z-40 no-print">
                <div class="flex items-center gap-4">
                    <!-- Logo móvil -->
                    <div class="lg:hidden text-lg font-bold">
                        <a href="{{ route('dashboard') }}">⚙️ Control Mant.</a>
                    </div>
                    
                    <!-- Cajones con símbolos (Acceso rápido horizontal) -->
                    <div class="hidden md:flex items-center gap-2">
                        <a href="{{ route('mantenimientos.create') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg font-bold hover:bg-blue-200 dark:hover:bg-blue-800/50 transition-colors text-sm shadow-sm">
                            <span>➕</span> Orden
                        </a>
                        <a href="{{ route('caja.create') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-lg font-bold hover:bg-green-200 dark:hover:bg-green-800/50 transition-colors text-sm shadow-sm">
                            <span>💵</span> Ingreso/Gasto
                        </a>
                        <a href="{{ route('cierre.index') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 rounded-lg font-bold hover:bg-purple-200 dark:hover:bg-purple-800/50 transition-colors text-sm shadow-sm">
                            <span>📊</span> Arqueo
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    @if(auth()->user()->photo)
                        <img src="{{ asset('storage/' . auth()->user()->photo) }}" width="32" height="32" class="rounded-full object-cover border border-gray-300 dark:border-gray-600">
                    @endif
                    <span class="text-sm hidden sm:inline-block font-semibold">Hola, {{ auth()->user()->name }}</span>
                    
                    <!-- Botón Modo Oscuro -->
                    <button type="button" id="theme-toggle" class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl focus:outline-none transition-colors" aria-label="Cambiar tema">
                        🌓
                    </button>

                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-xl px-4 py-2 font-bold transition-all text-sm">Salir</button>
                    </form>

                    <!-- Botón Hamburguesa móvil -->
                    <button type="button" id="mobile-menu-btn" class="lg:hidden p-2 bg-gray-100 dark:bg-gray-700 rounded-xl focus:outline-none ml-2">
                        <svg id="icon-menu" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <svg id="icon-close" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </header>

            <!-- Menú móvil desplegable -->
            <div id="mobile-menu" class="lg:hidden hidden bg-white dark:bg-gray-800 border-b border-gray-200/50 dark:border-gray-700/50 shadow-md sticky top-[73px] z-40 no-print">
                <div class="flex flex-col p-4 space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">📊 Dashboard</a>
                    <a href="{{ route('clientes.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">👤 Clientes</a>
                    <a href="{{ route('equipos.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">🖥️ Equipos</a>
                    <a href="{{ route('tecnicos.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">🛠️ Técnicos</a>
                    <a href="{{ route('stocks.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">📦 Inventario</a>
                    <a href="{{ route('caja.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">💰 Caja</a>
                    <a href="{{ route('cierre.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">📈 Cierre</a>
                    <a href="{{ route('electronicas.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">⚡ Electrónica</a>
                    <a href="{{ route('mantenimientos.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">📋 Órdenes</a>
                    <a href="{{ route('reportes.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">📊 Reportes</a>
                    <a href="{{ route('usuarios.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 font-bold transition-all">👨🏻‍💻 Usuarios</a>
                </div>
            </div>

            <!-- Barra de progreso de navegación -->
            <div id="nav-progress" class="no-print"></div>

            <!-- Contenido Principal -->
            <main id="page-main" class="p-4 sm:p-6 lg:p-8 flex-1">
                @yield('content')
            </main>
        </div>
    </div>
    @endauth

    <!-- Contenedor de Toasts -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-3 max-w-sm w-full pointer-events-none"></div>

    <!-- Modal de Alertas de Electrónica (al iniciar sesión) -->
    @if(session('alertas_electronica'))
    <div id="elec-alert-modal" class="fixed inset-0 z-[300] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-purple-300/40 dark:border-purple-700/50 rounded-2xl shadow-2xl p-6 max-w-lg w-full max-h-[80vh] flex flex-col">
            <div class="flex items-center gap-3 mb-4">
                <span class="text-3xl">⚡</span>
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Resumen de Electrónica</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ count(session('alertas_electronica')) }} registro(s) activos al iniciar sesión</p>
                </div>
            </div>
            <div class="overflow-y-auto flex-1 space-y-2 pr-1">
                @foreach(session('alertas_electronica') as $alerta)
                <div class="flex items-center justify-between gap-3 p-3 rounded-xl {{ $alerta['estado'] === 'pendiente' ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700/40' : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700/40' }}">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-sm text-gray-800 dark:text-white truncate">{{ $alerta['id_orden'] }} — {{ $alerta['cliente'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $alerta['dispositivo'] }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="block text-xs font-semibold px-2 py-0.5 rounded-lg {{ $alerta['estado'] === 'pendiente' ? 'bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' : 'bg-green-200 text-green-800 dark:bg-green-800 dark:text-green-200' }}">
                            {{ $alerta['estado'] === 'pendiente' ? '⏳ Pendiente' : '✅ Terminado' }}
                        </span>
                        <span class="block text-xs mt-1 font-bold {{ $alerta['dias'] > 14 ? 'text-red-600' : ($alerta['dias'] > 7 ? 'text-yellow-600' : 'text-gray-500') }}">
                            {{ $alerta['dias'] }} días
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-between items-center">
                <a href="{{ route('electronicas.index') }}" class="text-purple-600 dark:text-purple-400 hover:underline text-sm font-semibold">Ver módulo completo →</a>
                <button onclick="document.getElementById('elec-alert-modal').remove(); fetch('{{ route('electronicas.dismiss-alert') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});" class="px-5 py-2 bg-purple-500 text-white rounded-xl font-bold hover:bg-purple-600 shadow-lg shadow-purple-500/30 transition-all text-sm">
                    Entendido
                </button>
            </div>
        </div>
    </div>
    {{ session()->forget('alertas_electronica') }}
    @endif

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

        // --- SCROLL SUAVE A FILA POR ANCLA (centralizado) ---
        function centerAnchor() {
            const hash = window.location.hash;
            if (!hash) return;
            function scrollToRow() {
                const target = document.querySelector(hash);
                if (!target) return false;
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return true;
            }
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    if (!scrollToRow()) {
                        setTimeout(scrollToRow, 50);
                        setTimeout(scrollToRow, 200);
                    }
                });
            });
        }
        document.addEventListener('DOMContentLoaded', centerAnchor);
        window.addEventListener('load', function() { centerAnchor(); setTimeout(centerAnchor, 100); });
        window.addEventListener('hashchange', centerAnchor);
        window.addEventListener('pageshow', function(e) { if (e.persisted) centerAnchor(); });

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
                showToast("Se encontraron errores en el formulario, por favor revíselos.", 'error');
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
                            let customMessage = el.validationMessage;
                            if (el.validity.valueMissing) {
                                customMessage = 'Obligatorio';
                            } else if (el.type === 'email' && el.validity.typeMismatch) {
                                customMessage = 'Introduzca una dirección de correo válida';
                            }
                            errorMsg.textContent = customMessage;
                            el.parentNode.insertBefore(errorMsg, el.nextSibling);
                        }
                    });
                    if (!isValid) {
                        e.preventDefault();
                        showToast('Faltan campos por llenar o tienen formato incorrecto. Revisa los mensajes en rojo.', 'error');
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
                }, 10800000); // 3 horas (en ms)
            }
            window.onload = resetTimer;
            ['mousemove', 'keypress', 'scroll', 'click'].forEach(e => window.addEventListener(e, resetTimer));
        })();
        @endauth
        // --- RESPONSIVE TABLE: Inyectar data-label a cada celda ---
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.responsive-table').forEach(table => {
                const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.innerText.trim());
                table.querySelectorAll('tbody tr').forEach(row => {
                    Array.from(row.querySelectorAll('td')).forEach((td, idx) => {
                        if (headers[idx]) td.setAttribute('data-label', headers[idx]);
                    });
                });
            });
        });
    </script>
</body>
</html>


