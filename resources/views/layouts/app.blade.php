<!DOCTYPE html>
<html lang="es" class="preload">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tecni Systemas — ERP</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ts: {
                            blue: '#2563EB',
                            bluedark: '#1D4ED8',
                            cyan: '#06B6D4',
                            slate: '#0F172A',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- CSS Propio (Liquid Glass) -->
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif !important; background-color: #0A0F1C !important; }
        .nav-item.active { background: linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(37,99,235,0.05) 100%); color: #60A5FA; border-left: 3px solid #3B82F6; font-weight: 700; }
        .nav-label { font-family: 'Outfit', sans-serif !important; }
    </style>
    
    <!-- Lógica de Tema Temprana para evitar Flash de Contenido No Estilizado (FOUC) -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        window.addEventListener('load', () => {
            document.documentElement.classList.remove('preload');
        });
    </script>
</head>
<body class="ts-bg text-gray-800 dark:text-gray-100 overflow-x-hidden antialiased">

    @auth
    <!-- Envoltura Principal -->
    <div class="flex min-h-screen">
        
        <!-- SIDEBAR DE VIDRIO (Fijo) -->
        <aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col shrink-0 relative bg-[#0B1121] border-r border-white/5 transition-all duration-300 z-30 shadow-[4px_0_24px_rgba(0,0,0,0.2)] w-[70px] expanded:w-[280px]">
            <!-- Brand / Logo -->
            <div class="h-20 flex items-center justify-center expanded:justify-start border-b border-white/5 shrink-0 px-6 relative transition-all duration-300">
                <span class="text-[12px] font-black tracking-[0.2em] text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>
                <span class="text-[11px] font-black text-[#2563EB] uppercase group-[.expanded]:hidden">NAV</span>
            </div>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <div class="nav-icon">📊</div>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" title="Clientes">
                    <div class="nav-icon">👤</div>
                    <span class="nav-label">Clientes</span>
                </a>
                <a href="{{ route('equipos.index') }}" class="nav-item {{ request()->routeIs('equipos.*') ? 'active' : '' }}" title="Equipos">
                    <div class="nav-icon">🖥️</div>
                    <span class="nav-label">Equipos</span>
                </a>
                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" title="Proveedores">
                    <div class="nav-icon">🏭</div>
                    <span class="nav-label">Proveedores</span>
                </a>
                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" title="Técnicos">
                    <div class="nav-icon">🛠️</div>
                    <span class="nav-label">Técnicos</span>
                </a>
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}" title="Control Stock">
                    <div class="nav-icon">📦</div>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Operaciones (C/V)">
                    <div class="nav-icon">📄</div>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') ? 'active' : '' }}" title="Mantenimientos">
                    <div class="nav-icon">⚙️</div>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') ? 'active' : '' }}" title="Electrónica">
                    <div class="nav-icon">⚡</div>
                    <span class="nav-label">Electrónica</span>
                </a>
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" title="Caja (Ing/Egr)">
                    <div class="nav-icon">💵</div>
                    <span class="nav-label">Caja (Ing/Egr)</span>
                </a>
                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" title="Arqueo / Cierre">
                    <div class="nav-icon">🔒</div>
                    <span class="nav-label">Arqueo / Cierre</span>
                </a>
                <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Info Operativos">
                    <div class="nav-icon">📈</div>
                    <span class="nav-label">Info Operativos</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <div class="nav-icon">🏢</div>
                    <span class="nav-label">Empresa</span>
                </a>
            </nav>
            
            <!-- User Mini Profile (Fondo) -->
            <div class="p-3 border-t border-gray-200/40 dark:border-white/5">
                <form action="{{ route('logout') }}" method="POST" class="m-0 w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 p-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 rounded-xl transition-colors font-bold text-sm" title="Cerrar Sesión">
                        <span>🚪</span>
                        <span class="nav-label">Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL (Margen dinámico según sidebar) -->
        <div class="flex-1 flex flex-col min-w-0 transition-all duration-300" style="margin-left: var(--sidebar-w);">
            
            <!-- TOPBAR DE VIDRIO -->
            <header id="ts-topbar" class="h-16 px-4 md:px-6 flex items-center justify-between no-print">
                <!-- Izquierda: Toggle móvil y Título de vista actual -->
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl" onclick="toggleMobileSidebar()">
                        ☰
                    </button>
                    <!-- Search global (placeholder) -->
                    <div class="hidden md:flex items-center glass px-3 py-1.5 rounded-full text-sm w-64 border focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all">
                        <span class="text-gray-400 mr-2">🔍</span>
                        <input type="text" placeholder="Buscar orden, cliente..." class="bg-transparent border-none outline-none w-full text-gray-700 dark:text-gray-200 placeholder-gray-400">
                    </div>
                </div>

                <!-- Derecha: Perfil e Iconos -->
                <div class="flex items-center gap-3">
                    
                    <!-- Avatar de Usuario (Clickeable hacia Usuarios) -->
                    <a href="{{ route('usuarios.index') }}" class="flex items-center gap-2 pr-4 border-r border-white/10 hover:opacity-80 transition-opacity mr-2">
                        @if(auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Avatar" class="w-10 h-10 rounded-xl object-cover border-2 border-white/10">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="flex flex-col text-left hidden md:block ml-2">
                            <span class="text-sm font-bold text-white leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-blue-400 uppercase font-bold">{{ auth()->user()->role ?? 'Admin' }}</span>
                        </div>
                    </a>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-yellow-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>
                    </div>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition-colors group">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-yellow-500 group-hover:scale-110 transition-transform">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-red-500/20 hover:border-red-500/30 transition-all group" title="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Barra de progreso superior -->
            <div id="nav-progress" class="no-print"></div>

            <!-- CONTENIDO DINÁMICO -->
            <main id="ts-main" class="flex-1 p-4 sm:p-6 lg:p-8 page-enter relative z-10">
                @yield('content')
            </main>
        </div>
    </div>
    @endauth

    @guest
        <div class="min-h-screen flex items-center justify-center p-4 page-enter">
            @yield('content')
        </div>
    @endguest

    <!-- CONTENEDOR DE TOASTS (Notificaciones Glass) -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[999] flex flex-col gap-3 pointer-events-none w-full max-w-sm"></div>

    <!-- MODAL DE CONFIRMACIÓN DE ALERTA/ELIMINACIÓN (Liquid Glass) -->
    <div id="ts-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
        <div class="ts-modal-card scale-95 opacity-0" id="ts-modal-card">
            <div class="p-6">
                <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center text-3xl mx-auto mb-4">
                    ⚠️
                </div>
                <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2" id="ts-modal-title">¿Estás seguro?</h3>
                <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-8" id="ts-modal-msg">
                    Esta acción es irreversible y afectará los registros contables vinculados.
                </p>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeTsModal()" class="flex-1 btn-ghost justify-center">
                        Cancelar
                    </button>
                    <button type="button" id="ts-modal-confirm" class="flex-1 btn-danger justify-center font-bold">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS CORE -->
    <script>
        // ─── TEMA CLARO/OSCURO ──────────────────────────────────────────
        const themeToggleBtn = document.getElementById('theme-toggle');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark');
                if (document.documentElement.classList.contains('dark')) {
                    localStorage.setItem('color-theme', 'dark');
                } else {
                    localStorage.setItem('color-theme', 'light');
                }
            });
        }

        // ─── TOAST NOTIFICATIONS (Glass) ──────────────────────────────
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // Colores basados en el tipo
            const isError = type === 'error' || type === 'danger';
            const bgStr = isError 
                ? 'bg-red-500/90 dark:bg-red-900/80 border-red-500/50' 
                : 'bg-emerald-500/90 dark:bg-emerald-900/80 border-emerald-500/50';
            const icon = isError ? '⚠️' : '✅';

            toast.className = `flex items-center gap-3 p-4 rounded-2xl border backdrop-blur-xl shadow-2xl text-white transform transition-all duration-400 translate-y-12 opacity-0 pointer-events-auto ${bgStr}`;
            toast.innerHTML = `
                <span class="text-2xl drop-shadow-md">${icon}</span>
                <p class="text-sm font-bold leading-tight flex-1">${message}</p>
                <button onclick="this.parentElement.remove()" class="text-white/60 hover:text-white transition-colors text-lg focus:outline-none">&times;</button>
            `;

            container.appendChild(toast);

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-12', 'opacity-0');
                });
            });

            setTimeout(() => {
                toast.classList.add('translate-y-8', 'opacity-0');
                setTimeout(() => toast.remove(), 400);
            }, 4500);
        }

        // Leer sesiones de Laravel
        @if(session('success')) showToast("{{ session('success') }}", 'success'); @endif
        @if(session('error')) showToast("{{ session('error') }}", 'error'); @endif
        @if($errors->any()) showToast("Verifica los campos obligatorios del formulario.", 'error'); @endif

        // ─── MODAL GLOBAL DE CONFIRMACIÓN ─────────────────────────────
        let _pendingForm = null;

        function confirmDelete(form, message) {
            _pendingForm = form;
            const modal = document.getElementById('ts-modal');
            const card = document.getElementById('ts-modal-card');
            
            if(message) document.getElementById('ts-modal-msg').innerText = message;

            modal.classList.remove('hidden');
            // Timeout pequeño para permitir display:flex antes de animar
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeTsModal() {
            const modal = document.getElementById('ts-modal');
            const card = document.getElementById('ts-modal-card');
            
            modal.classList.add('opacity-0');
            card.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                _pendingForm = null;
            }, 300);
        }

        document.getElementById('ts-modal-confirm')?.addEventListener('click', () => {
            if (_pendingForm) {
                _pendingForm.setAttribute('data-confirmed', 'true');
                _pendingForm.submit();
            }
        });

        // Interceptar formularios con confirmación
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form[data-confirm-delete]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!this.getAttribute('data-confirmed')) {
                        e.preventDefault();
                        confirmDelete(this, this.dataset.confirmDelete);
                    }
                });
            });
        });

        // ─── SIDEBAR MÓVIL TOGGLE ─────────────────────────────────────
        function toggleMobileSidebar() {
            const sb = document.getElementById('ts-sidebar');
            // En móvil, el sidebar está en position: fixed, z-index alto.
            // Si no tiene clases para móvil, las añadimos.
            if(!sb.classList.contains('mobile-active')) {
                sb.style.transform = 'translateX(0)';
                sb.classList.add('mobile-active');
            } else {
                sb.style.transform = 'translateX(-100%)';
                sb.classList.remove('mobile-active');
            }
        }

        // Lógica CSS inline para móvil
        const mediaQuery = window.matchMedia('(max-width: 1024px)');
        function handleMobileChanges(e) {
            const sb = document.getElementById('ts-sidebar');
            if (e.matches) {
                sb.style.transform = 'translateX(-100%)';
                sb.classList.remove('hover:expanded');
                sb.style.width = '260px'; // Forzar ancho completo al mostrarse en móvil
            } else {
                sb.style.transform = 'translateX(0)';
                sb.classList.add('hover:expanded');
                sb.style.width = ''; // Limpiar inline
            }
        }
        mediaQuery.addListener(handleMobileChanges);
        handleMobileChanges(mediaQuery);

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const bell = document.getElementById('notification-bell');
            const dropdown = document.getElementById('notif-dropdown');
            if (bell && dropdown && !bell.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // ─── TABLA BUSQUEDA RÁPIDA ─────────────────────────────────────
        function filterTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            if (!input || !table) return;

            input.addEventListener('keyup', function() {
                const filter = input.value.toLowerCase();
                const tbody = table.getElementsByTagName('tbody')[0];
                if (!tbody) return;
                
                const trs = tbody.getElementsByTagName('tr');
                for (let i = 0; i < trs.length; i++) {
                    const tr = trs[i];
                    // Skip 'empty state' row (colspan)
                    if (tr.cells.length === 1 && tr.cells[0].hasAttribute('colspan')) continue;
                    
                    let text = tr.textContent || tr.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        tr.style.display = '';
                    } else {
                        tr.style.display = 'none';
                    }
                }
            });
        }
    </script>

    @if(session('alertas_pendientes') && count(session('alertas_pendientes')) > 0)
    <!-- Modal de Notificaciones Pendientes al Iniciar Sesión -->
    <div id="ts-notif-modal" class="ts-modal-overlay opacity-0 transition-opacity duration-300 z-[200]">
        <div id="ts-notif-card" class="ts-modal-card scale-95 opacity-0 p-6 md:p-8 flex flex-col items-center text-center transition-all duration-300">
            <div class="w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center mb-4">
                <span class="text-3xl">🔔</span>
            </div>
            <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2">¡Tienes tareas pendientes!</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Hay órdenes de electrónica y mantenimientos que requieren tu atención.</p>
            
            <div class="w-full max-h-[40vh] overflow-y-auto mb-6 text-left space-y-3 pr-2 scrollbar-hide">
                @foreach(session('alertas_pendientes') as $alerta)
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full {{ $alerta['modulo'] == 'mantenimiento' ? 'bg-blue-500' : 'bg-purple-500' }}"></div>
                        <div class="flex justify-between items-start gap-3 pl-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-black {{ $alerta['modulo'] == 'mantenimiento' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400' }}">{{ $alerta['id_orden'] }}</span>
                                    <span class="text-[9px] uppercase font-bold px-1.5 py-0.5 rounded-md {{ $alerta['modulo'] == 'mantenimiento' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">{{ $alerta['modulo'] }}</span>
                                </div>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate" title="{{ $alerta['dispositivo'] }}">{{ $alerta['dispositivo'] }}</p>
                                <p class="text-xs text-gray-500 truncate" title="{{ $alerta['cliente'] }}">{{ $alerta['cliente'] }}</p>
                            </div>
                            <span class="pill pill-pending text-[10px] py-0.5 px-2 whitespace-nowrap">
                                {{ $alerta['dias'] }} días
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <button onclick="closeNotifModal()" class="w-full btn-primary py-3 justify-center text-lg ">
                Entendido
            </button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('ts-notif-modal');
            const card = document.getElementById('ts-notif-card');
            
            // Bloquear scroll de fondo
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
            }, 100);
        });

        function closeNotifModal() {
            const modal = document.getElementById('ts-notif-modal');
            const card = document.getElementById('ts-notif-card');
            modal.classList.add('opacity-0');
            card.classList.add('scale-95', 'opacity-0');
            
            // Restaurar scroll
            document.body.style.overflow = 'auto';
            
            setTimeout(() => { modal.style.display = 'none'; }, 300);
        }
    </script>
    @endif

    <!-- Scripts de Librerías (Flatpickr y Tom Select) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Flatpickr para todos los inputs de tipo date
            flatpickr("input[type='date']", {
                locale: "es",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                altInputClass: "glass-input",
                disableMobile: true,
                monthSelectorType: "static" // Convierte el feo select nativo en texto elegante
            });

            // 2. Tom Select para selects con clase glass-input (con buscador habilitado)
            document.querySelectorAll("select.glass-input").forEach((el) => {
                if (!el.classList.contains('tomselected') && !el.classList.contains('no-search')) {
                    let defaultPlaceholder = el.getAttribute('data-placeholder');
                    
                    // Si no tiene placeholder explícito, usa la primera opción vacía o un texto genérico
                    if (!defaultPlaceholder && el.options.length > 0 && el.options[0].value === "") {
                        defaultPlaceholder = el.options[0].text;
                    } else if (!defaultPlaceholder) {
                        defaultPlaceholder = 'Selecciona o busca...';
                    }

                    new TomSelect(el, {
                        create: false,
                        maxOptions: 100, // Aumentado para clientes grandes
                        dropdownParent: 'body',
                        placeholder: defaultPlaceholder
                    });
                }
            });
        });
    </script>
</body>
</html>
