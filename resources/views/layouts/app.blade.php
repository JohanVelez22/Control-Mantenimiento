<!DOCTYPE html>
<html lang="es" class="preload">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tecni Systemas</title>
    
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
    
    <!-- CSS Librerías: Flatpickr + TomSelect (NECESARIOS para que funcionen) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    
    <!-- CSS Propio (Liquid Glass) - va DESPUÉS para sobreescribir estilos base -->
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .font-logo { font-family: 'Michroma', sans-serif; }
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
        <aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col">
            <!-- Brand / Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200/40 dark:border-white/5 shrink-0 px-6 relative transition-all duration-150">
                <span class="text-[11px] font-semibold tracking-[0.15em] text-[#06B6D4] uppercase font-logo text-center w-full">NAVEGACIÓN</span>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" title="Clientes">
                    <span class="nav-icon">👤</span>
                    <span class="nav-label">Clientes</span>
                </a>
                <a href="{{ route('equipos.index') }}" class="nav-item {{ request()->routeIs('equipos.*') ? 'active' : '' }}" title="Equipos">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-label">Equipos</span>
                </a>
                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" title="Proveedores">
                    <span class="nav-icon">🏭</span>
                    <span class="nav-label">Proveedores</span>
                </a>
                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" title="Técnicos">
                    <span class="nav-icon">🛠️</span>
                    <span class="nav-label">Técnicos</span>
                </a>
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}" title="Control Stock">
                    <span class="nav-icon">📦</span>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Operaciones (C/V)">
                    <span class="nav-icon">📄</span>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') ? 'active' : '' }}" title="Mantenimientos">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') ? 'active' : '' }}" title="Electrónica">
                    <span class="nav-icon">⚡</span>
                    <span class="nav-label">Electrónica</span>
                </a>
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" title="Caja (Ing/Egr)">
                    <span class="nav-icon">💵</span>
                    <span class="nav-label">Caja (Ing/Egr)</span>
                </a>
                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" title="Arqueo / Cierre">
                    <span class="nav-icon">🔒</span>
                    <span class="nav-label">Arqueo / Cierre</span>
                </a>
                <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Info Operativos">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Info Operativos</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-label">Empresa</span>
                </a>
            </nav>
            
            </aside>

        <!-- CONTENEDOR PRINCIPAL (Margen dinámico según sidebar) -->
        <div id="main-wrapper" class="flex-1 flex flex-col min-w-0 transition-all duration-150">
            
            <!-- TOPBAR DE VIDRIO -->
            <header id="ts-topbar" class="h-16 px-4 md:px-6 flex items-center justify-between no-print">
                <!-- Izquierda: Toggle móvil -->
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl transition-colors" onclick="toggleMobileSidebar()">
                        ☰
                    </button>
                </div>

                <!-- Centro: Logo Centrado -->
                <div class="absolute left-1/2 transform -translate-x-1/2 hidden md:flex justify-center items-center">
                    <a href="{{ route('dashboard') }}" class="text-[20px] font-black tracking-widest hover:scale-105 transition-transform duration-300 font-logo flex items-center gap-2">
                        <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
                        <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
                    </a>
                </div>

                <!-- Derecha: Perfil e Iconos -->
                <div class="flex items-center gap-3">
                    
                    <!-- Avatar de Usuario (Clickeable hacia Usuarios) -->
                    <a href="{{ route('usuarios.index') }}" class="flex items-center gap-2 pr-4 border-r border-gray-200 dark:border-white/10 hover:opacity-80 transition-opacity mr-2">
                        @if(auth()->check() && auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Avatar" class="w-10 h-10 rounded-xl object-cover border-2 border-gray-200 dark:border-white/10">
                        @elseif(auth()->check())
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="hidden md:flex flex-col text-left ml-2">
                            <span class="text-sm font-bold text-slate-800 dark:text-white leading-none">{{ auth()->check() ? auth()->user()->name : 'Invitado' }}</span>
                            <span class="text-[10px] text-[#06B6D4] uppercase font-bold mt-1">{{ auth()->check() ? auth()->user()->role ?? 'Admin' : 'Invitado' }}</span>
                        </div>
                    </a>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg relative" onclick="document.getElementById('notif-dropdown').classList.toggle('hidden')">
                            🔔
                            @if(isset($totalPendientes) && $totalPendientes > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border border-white dark:border-slate-800"></span>
                                </span>
                            @endif
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-72 bg-white/95 dark:bg-slate-900/95 backdrop-blur-2xl border border-gray-200/80 dark:border-gray-700/80 rounded-xl shadow-2xl z-50 overflow-hidden">
                            <div class="p-3 border-b border-gray-200/50 dark:border-gray-700/50 bg-gray-50/80 dark:bg-slate-800/80">
                                <h3 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Notificaciones Pendientes</h3>
                            </div>
                            <div class="p-2 space-y-1">
                                @if(isset($totalPendientes) && $totalPendientes > 0)
                                    @if($mantPendientes > 0)
                                    <button onclick="openNotifModal(); document.getElementById('notif-dropdown').classList.add('hidden');" class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Mantenimientos</span>
                                            <span class="bg-blue-100 text-blue-700 dark:bg-blue-900/80 dark:text-blue-300 py-0.5 px-2 rounded-full text-xs font-bold">{{ $mantPendientes }}</span>
                                        </div>
                                    </button>
                                    @endif
                                    @if($elecPendientes > 0)
                                    <button onclick="openNotifModal(); document.getElementById('notif-dropdown').classList.add('hidden');" class="w-full text-left px-3 py-2 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Electrónica</span>
                                            <span class="bg-purple-100 text-purple-700 dark:bg-purple-900/80 dark:text-purple-300 py-0.5 px-2 rounded-full text-xs font-bold">{{ $elecPendientes }}</span>
                                        </div>
                                    </button>
                                    @endif
                                    @if($cajaPendientes > 0)
                                    <button onclick="openNotifModal(); document.getElementById('notif-dropdown').classList.add('hidden');" class="w-full text-left px-3 py-2 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">Saldos Pendientes</span>
                                            <span class="bg-orange-100 text-orange-700 dark:bg-orange-900/80 dark:text-orange-300 py-0.5 px-2 rounded-full text-xs font-bold">{{ $cajaPendientes }}</span>
                                        </div>
                                    </button>
                                    @endif
                                @else
                                    <div class="px-3 py-4 text-center">
                                        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Todo al día, excelente trabajo.</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg">
                        <span class="dark:hidden">☀️</span>
                        <span class="hidden dark:inline">🌙</span>
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-all group text-lg" title="Cerrar Sesión">
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
        <div class="min-h-[100dvh] flex items-center justify-center p-4 page-enter pb-24 md:pb-32">
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

        // ─── SIDEBAR HOVER → EMPUJAR CONTENIDO ───────────────────────
        (function() {
            const sb = document.getElementById('ts-sidebar');
            const wrapper = document.getElementById('main-wrapper');
            if (!sb || !wrapper) return;

            const W_COLLAPSED = '72px';
            const W_EXPANDED  = '260px';

            // Inicializa el margen con transición suave
            wrapper.style.transition = 'margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
            wrapper.style.marginLeft = W_COLLAPSED;

            // Reposiciona el calendario Flatpickr siguiendo al input durante la transición
            function trackOpenCalendars(durationMs) {
                const start = performance.now();
                function tick(now) {
                    if (window._flatpickrInstances) {
                        window._flatpickrInstances.forEach(function(fp) {
                            if (fp.isOpen && fp.calendarContainer) {
                                var input = fp.altInput || fp.input;
                                var rect = input.getBoundingClientRect();
                                var scrollY = window.scrollY || window.pageYOffset;
                                var scrollX = window.scrollX || window.pageXOffset;
                                fp.calendarContainer.style.top  = (rect.bottom + scrollY + 4) + 'px';
                                fp.calendarContainer.style.left = (rect.left  + scrollX) + 'px';
                            }
                        });
                    }
                    if (now - start < durationMs) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            }

            sb.addEventListener('mouseenter', () => {
                wrapper.style.marginLeft = W_EXPANDED;
                trackOpenCalendars(300);
            });
            sb.addEventListener('mouseleave', () => {
                wrapper.style.marginLeft = W_COLLAPSED;
                trackOpenCalendars(300);
            });
        })();

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
            const wrapper = document.getElementById('main-wrapper');
            if (e.matches) {
                sb.style.transform = 'translateX(-100%)';
                sb.classList.remove('hover:expanded');
                sb.style.width = '260px'; // Forzar ancho completo al mostrarse en móvil
                if (wrapper) wrapper.style.marginLeft = '0';
            } else {
                sb.style.transform = 'translateX(0)';
                sb.classList.add('hover:expanded');
                sb.style.width = ''; // Limpiar inline
                if (wrapper) wrapper.style.marginLeft = '72px';
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

    <!-- Modal de Notificaciones Pendientes (siempre disponible, abierto desde campana o al iniciar sesión) -->
    @if(isset($totalPendientes) && $totalPendientes > 0)
    <div id="ts-notif-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
        <div id="ts-notif-card" class="ts-modal-card scale-95 opacity-0 p-6 md:p-8 flex flex-col transition-all duration-300 w-full max-w-lg mx-4">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/50 flex items-center justify-center shrink-0">
                    <span class="text-2xl">🔔</span>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">¡Tienes tareas pendientes!</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $totalPendientes }} elemento(s) requieren atención</p>
                </div>
            </div>

            {{-- Scrollable list of all pending items --}}
            <div class="w-full max-h-[55vh] overflow-y-auto space-y-2 pr-1 scrollbar-hide mb-5">

                {{-- Mantenimientos --}}
                @foreach($mantList as $m)
                <a href="{{ route('mantenimientos.show', $m->id) }}" onclick="closeNotifModal()"
                   class="flex items-center justify-between gap-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-500 rounded-l-xl"></div>
                    <div class="pl-3 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider">Mantenimiento</span>
                            <span class="text-[10px] font-bold text-blue-500 dark:text-blue-300">{{ $m->id_orden }}</span>
                        </div>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $m->equipo->nombre ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $m->equipo->cliente->nombre ?? '—' }}</p>
                    </div>
                    <span class="shrink-0 text-blue-500 dark:text-blue-400 group-hover:translate-x-1 transition-transform text-lg">→</span>
                </a>
                @endforeach

                {{-- Electrónica --}}
                @foreach($elecList as $e)
                <a href="{{ route('electronicas.show', $e->id) }}" onclick="closeNotifModal()"
                   class="flex items-center justify-between gap-3 p-3 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-purple-500 rounded-l-xl"></div>
                    <div class="pl-3 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-wider">Electrónica</span>
                            <span class="text-[10px] font-bold text-purple-500 dark:text-purple-300">{{ $e->id_orden }}</span>
                        </div>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $e->equipo->nombre ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $e->equipo->cliente->nombre ?? '—' }}</p>
                    </div>
                    <span class="shrink-0 text-purple-500 dark:text-purple-400 group-hover:translate-x-1 transition-transform text-lg">→</span>
                </a>
                @endforeach

                {{-- Facturas con saldo pendiente --}}
                @foreach($cajaList as $f)
                <a href="{{ route('inventario.facturas.show', $f->id) }}" onclick="closeNotifModal()"
                   class="flex items-center justify-between gap-3 p-3 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800 hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-orange-500 rounded-l-xl"></div>
                    <div class="pl-3 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-wider">Saldo Pendiente</span>
                            <span class="text-[10px] font-bold text-orange-500 dark:text-orange-300">{{ $f->numero_factura }}</span>
                        </div>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $f->facturable->nombre ?? '—' }}</p>
                        <p class="text-xs text-orange-600 dark:text-orange-400 font-semibold">
                            Saldo: ${{ number_format($f->saldo_pendiente, 0, ',', '.') }}
                        </p>
                    </div>
                    <span class="shrink-0 text-orange-500 dark:text-orange-400 group-hover:translate-x-1 transition-transform text-lg">→</span>
                </a>
                @endforeach

            </div>

            <button onclick="closeNotifModal()" class="w-full btn-primary py-3 justify-center text-base">
                Cerrar
            </button>
        </div>
    </div>
    <script>
        function openNotifModal() {
            const modal = document.getElementById('ts-notif-modal');
            const card  = document.getElementById('ts-notif-card');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeNotifModal() {
            const modal = document.getElementById('ts-notif-modal');
            const card  = document.getElementById('ts-notif-card');
            if (!modal) return;
            modal.classList.add('opacity-0');
            card.classList.add('scale-95', 'opacity-0');
            document.body.style.overflow = 'auto';
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        // Abrir automáticamente al iniciar sesión si hay alertas de sesión pendientes
        @if(session('alertas_pendientes') && count(session('alertas_pendientes')) > 0)
        document.addEventListener('DOMContentLoaded', () => openNotifModal());
        @endif
    </script>
    @endif

    <!-- Scripts de Librerías (Flatpickr y Tom Select) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // 1. Flatpickr para todos los inputs de tipo date
            window._flatpickrInstances = [];
            flatpickr("input[type='date']", {
                locale: "es",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                altInputClass: "glass-input",
                disableMobile: true,
                monthSelectorType: "static",
                appendTo: document.body,
                onReady: function(_, __, fp) {
                    window._flatpickrInstances.push(fp);
                }
            });

            // 2. Tom Select para selects con clase glass-input
            document.querySelectorAll("select.glass-input").forEach((el) => {
                if (!el.classList.contains('tomselected')) {
                    let defaultPlaceholder = el.getAttribute('data-placeholder');
                    let isNoSearch = el.classList.contains('no-search');
                    
                    // Si no tiene placeholder explícito, usa la primera opción vacía
                    if (!defaultPlaceholder && el.options.length > 0 && el.options[0].value === "") {
                        defaultPlaceholder = el.options[0].text;
                    } else if (!defaultPlaceholder) {
                        defaultPlaceholder = 'Selecciona o busca...';
                    }

                    new TomSelect(el, {
                        create: false,
                        maxOptions: 100,
                        dropdownParent: 'body',
                        placeholder: defaultPlaceholder,
                        controlInput: isNoSearch ? null : undefined,
                        allowEmptyOption: true,
                        render: {
                            option: function(data, escape) {
                                return '<div class="ts-option-item">' + escape(data.text) + '</div>';
                            },
                            item: function(data, escape) {
                                return '<div class="ts-item-display">' + escape(data.text) + '</div>';
                            }
                        }
                    });
                }
            });
        });
    </script>

</body>
</html>
