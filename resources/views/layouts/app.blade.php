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
            <div class="h-20 flex items-center justify-center border-b border-gray-200/40 dark:border-white/5 shrink-0 px-3 relative">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 w-full justify-center group-hover:justify-start transition-all">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center text-white font-black text-xl shadow-[0_4px_12px_rgba(37,99,235,0.4)] shrink-0 relative overflow-hidden">
                        <div class="absolute inset-0 bg-white/20 transform -translate-y-full hover:translate-y-0 transition-transform"></div>
                        T
                    </div>
                    <div class="nav-label flex flex-col justify-center">
                        <span class="text-base font-black tracking-tight leading-tight text-slate-800 dark:text-white">TECNI</span>
                        <span class="text-[10px] font-bold tracking-widest text-blue-600 dark:text-cyan-400 uppercase">Systemas</span>
                    </div>
                </a>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 space-y-1">
                
                <!-- GRUPO: PRINCIPAL -->
                <div class="nav-group-label">General</div>
                
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <div class="nav-icon">📊</div>
                    <span class="nav-label">Dashboard</span>
                </a>

                <!-- GRUPO: OPERATIVO -->
                <div class="mt-4 nav-group-label">Motor Operativo</div>
                
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') ? 'active' : '' }}" title="Mantenimientos">
                    <div class="nav-icon relative">
                        📋
                        <!-- Badge dinámico de pendientes (Ejemplo estático, luego dinámico) -->
                        <span class="nav-badge">3</span>
                    </div>
                    <span class="nav-label">Mantenimientos</span>
                </a>

                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') ? 'active' : '' }}" title="Servicio Técnico">
                    <div class="nav-icon">⚡</div>
                    <span class="nav-label">Serv. Técnico</span>
                </a>

                <!-- GRUPO: INVENTARIO Y SUMINISTRO -->
                <div class="mt-4 nav-group-label">Logística</div>
                
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}" title="Control de Stock">
                    <div class="nav-icon">📦</div>
                    <span class="nav-label">Control Stock</span>
                </a>

                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Compra y Venta">
                    <div class="nav-icon">🧾</div>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>

                <!-- GRUPO: TERCEROS -->
                <div class="mt-4 nav-group-label">Directorio</div>
                
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" title="Clientes">
                    <div class="nav-icon">👤</div>
                    <span class="nav-label">Clientes</span>
                </a>

                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" title="Proveedores">
                    <div class="nav-icon">🏭</div>
                    <span class="nav-label">Proveedores</span>
                </a>

                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" title="Personal">
                    <div class="nav-icon">🛠️</div>
                    <span class="nav-label">Técnicos</span>
                </a>

                <!-- GRUPO: FINANCIERO Y AUDITORÍA -->
                <div class="mt-4 nav-group-label">Caja & Auditoría</div>
                
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" title="Caja Fuerte">
                    <div class="nav-icon">💵</div>
                    <span class="nav-label">Caja (Ing/Egr)</span>
                </a>

                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" title="Cierre Diario">
                    <div class="nav-icon">🔒</div>
                    <span class="nav-label">Arqueo / Cierre</span>
                </a>

                <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Informes Operativos">
                    <div class="nav-icon">📈</div>
                    <span class="nav-label">Inf. Operativos</span>
                </a>

                <a href="{{ route('reportes.financiero.acumulado') }}" class="nav-item {{ request()->routeIs('reportes.financiero.*') ? 'active' : '' }}" title="Informes Financieros">
                    <div class="nav-icon">💹</div>
                    <span class="nav-label">Inf. Financieros</span>
                </a>

                <a href="{{ route('usuarios.index') }}" class="nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" title="Usuarios del Sistema">
                    <div class="nav-icon">👨🏻‍💻</div>
                    <span class="nav-label">Seguridad</span>
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

                <!-- Derecha: Acciones rápidas y Perfil -->
                <div class="flex items-center gap-3">
                    <!-- Botones rápidos (Ocultos en móvil) -->
                    <div class="hidden sm:flex gap-2 mr-2">
                        <a href="{{ route('mantenimientos.create') }}" class="btn-ghost" title="Nueva Orden">➕ Orden</a>
                        <a href="{{ route('inventario.venta.create') }}" class="btn-ghost" title="Nueva Venta">🛒 Venta</a>
                    </div>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-700">
                        <span class="dark:hidden">🌙</span>
                        <span class="hidden dark:block">☀️</span>
                    </button>

                    <!-- Avatar de Usuario -->
                    <div class="flex items-center gap-2 pl-3 border-l border-gray-300 dark:border-gray-700">
                        <div class="flex flex-col text-right hidden md:block">
                            <span class="text-sm font-bold text-slate-800 dark:text-white leading-tight">{{ auth()->user()->name }}</span>
                            <span class="text-[10px] text-blue-600 dark:text-cyan-400 uppercase font-bold">{{ auth()->user()->role ?? 'Admin' }}</span>
                        </div>
                        @if(auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Avatar" class="w-9 h-9 rounded-xl object-cover border border-white/20 shadow-sm">
                        @else
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-500 to-cyan-400 flex items-center justify-center text-white font-bold shadow-sm">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
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

    </script>
</body>
</html>
