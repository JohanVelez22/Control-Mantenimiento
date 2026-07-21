@php
    $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
    $logoBase64 = \Illuminate\Support\Facades\Cache::remember('empresa_logo_base64', 3600, function () use ($empresa) {
        if ($empresa->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->logo_path)) {
            $type = pathinfo($empresa->logo_path, PATHINFO_EXTENSION);
            $data = \Illuminate\Support\Facades\Storage::disk('public')->get($empresa->logo_path);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return null;
    });
@endphp
<!DOCTYPE html>
<html lang="es" class="preload">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sistema de Control de Mantenimiento y Gestión de Equipos. Solución integral para talleres y gestión de garantías.">
    <meta name="keywords" content="Mantenimiento, Talleres, Gestión de Inventario, Cotizaciones, Electrónica">
    
    <title>@yield('title', 'Tecni Systemas')</title>
    
    <!-- Vite (Tailwind CSS compilado + JS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- CSS Librerías: Flatpickr + TomSelect (NECESARIOS para que funcionen) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    
    <!-- Scripts de Librerías (cargados en el head para prevenir FOUC) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <!-- CSS Propio (Liquid Glass) - va DESPUÉS para sobreescribir estilos base -->
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    
    <!-- Lógica de Tema Temprana para evitar Flash de Contenido No Estilizado (FOUC) -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        // Usar DOMContentLoaded en lugar de 'load' para eliminar 'preload' ANTES
        // de que los CDN externos terminen de cargar. Esto evita que Tailwind CDN
        // re-inyecte estilos durante una animación activa (causa del flash).
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.classList.remove('preload');
        });
    </script>
</head>
<body class="ts-bg text-gray-800 dark:text-gray-100 overflow-x-hidden antialiased">

    @auth
    <!-- Envoltura Principal -->
    <div class="flex min-h-screen">
        
        <!-- SIDEBAR DE VIDRIO (Fijo) -->
        @include('layouts.partials.sidebar')

        <!-- CONTENEDOR PRINCIPAL (Margen dinámico según sidebar) -->
        <div id="main-wrapper" class="flex-1 flex flex-col min-w-0 transition-all duration-150">
            
            <!-- TOPBAR DE VIDRIO -->
            @include('layouts.partials.topbar')

            <!-- Barra de progreso superior -->
            <div id="nav-progress" class="no-print"></div>

            <!-- CONTENIDO DINÁMICO -->
            <main id="ts-main" class="flex-1 p-4 sm:p-6 lg:p-8 pb-[50vh] relative z-10 content-scroll">
                <!-- Encabezado de Impresión -->
                <div class="print-header hidden-screen" style="margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; border-bottom: 2px solid #000000;">
                        <tr>
                            <td style="width: 35%; border: none !important; padding: 0 0 10px 0 !important; vertical-align: middle;">
                                @if($logoBase64)
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 55px; max-width: 180px; object-fit: contain;">
                                @else
                                    <span style="font-size: 16px; font-weight: bold; color: #000000; text-transform: uppercase;">{{ $empresa->nombre }}</span>
                                @endif
                            </td>
                            <td style="width: 65%; border: none !important; padding: 0 0 10px 0 !important; text-align: right; vertical-align: middle; font-size: 9px; color: #1e293b; line-height: 1.35;">
                                <div style="font-size: 13px; font-weight: bold; color: #1e3a5f; text-transform: uppercase; margin-bottom: 3px;" id="print-page-title">INFORME DEL SISTEMA</div>
                                @if($empresa->nit)<div><strong>NIT:</strong> {{ $empresa->nit }}</div>@endif
                                @if($empresa->telefono)<div><strong>Tel:</strong> {{ $empresa->telefono }}</div>@endif
                                @if($empresa->direccion)<div><strong>Dir:</strong> {{ $empresa->direccion }}</div>@endif
                            </td>
                        </tr>
                    </table>
                </div>
                @yield('content')
            </main>
        </div>
    </div>
    @endauth

    @guest
        <div class="min-h-[100dvh] flex items-center justify-center p-4 pb-24 md:pb-32">
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

    <!-- MODAL GLOBAL DE ANULACIÓN (fuera del main-wrapper para centrado correcto) -->
    <div id="global-anular-modal" class="ts-modal-overlay hidden opacity-0 animate-fade-in">
        <div class="ts-modal-card scale-95 opacity-0 transition-all duration-300" id="global-anular-card">
            <div class="p-6">
                <div id="global-anular-icon-container" class="w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-500 flex items-center justify-center text-3xl mx-auto mb-4">
                    <span id="global-anular-icon">🚫</span>
                </div>
                <h3 id="global-anular-title" class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Confirmar Anulación</h3>
                <p id="global-anular-msg" class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                    Ingresa tu contraseña para anular este registro. Se mantendrá el historial pero no afectará saldos.
                </p>
                <form id="global-anular-form" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <input type="password" name="password_confirm" id="global-anular-input" required
                            placeholder="Contraseña..." 
                            class="glass-input text-center tracking-widest text-lg focus:ring-orange-500">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="closeAnularModal()" class="flex-1 btn-ghost justify-center">Cancelar</button>
                        <button type="submit" id="global-anular-submit" class="flex-1 btn-danger justify-center font-bold">🚫 Anular</button>
                    </div>
                </form>
</div>
    </div>
    </div>

    <!-- MODAL GLOBAL DE RECHAZAR COTIZACIÓN (Sí/No sin contraseña) -->
    <div id="global-rechazar-modal" class="ts-modal-overlay hidden opacity-0 animate-fade-in">
        <div class="ts-modal-card scale-95 opacity-0 transition-all duration-300" id="global-rechazar-card">
            <div class="p-6">
                <div class="w-16 h-16 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 text-yellow-500 flex items-center justify-center text-3xl mx-auto mb-4">
                    <span>⚠️</span>
                </div>
                <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Confirmar Rechazo</h3>
                <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                    ¿Estás seguro de rechazar esta cotización? Cambiará su estado a "Rechazada".
                </p>
                <form id="global-rechazar-form" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex gap-3">
                        <button type="button" onclick="closeRechazarModal()" class="flex-1 btn-ghost justify-center">No, cancelar</button>
                        <button type="submit" id="global-rechazar-submit" class="flex-1 btn-danger justify-center font-bold">Sí, rechazar</button>
                    </div>
                </form>
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

        // ─── MODAL GLOBAL DE ANULACIÓN ───────────────────────────────────
        function openAnularModal(actionUrl, isReactivation = false) {
            const modal = document.getElementById('global-anular-modal');
            const card  = document.getElementById('global-anular-card');
            const form  = document.getElementById('global-anular-form');
            const input = document.getElementById('global-anular-input');
            const iconContainer = document.getElementById('global-anular-icon-container');
            const icon = document.getElementById('global-anular-icon');
            const title = document.getElementById('global-anular-title');
            const msg = document.getElementById('global-anular-msg');
            const submitBtn = document.getElementById('global-anular-submit');

            form.action  = actionUrl;
            input.value  = '';

            if (isReactivation) {
                // Modo Activación
                title.textContent = 'Confirmar Activación';
                msg.textContent = 'Ingresa tu contraseña para activar este registro.';
                icon.textContent = '✅';
                iconContainer.className = 'w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-center justify-center text-3xl mx-auto mb-4';
                submitBtn.innerHTML = '✅ Activar';
                submitBtn.className = 'flex-1 btn-primary justify-center text-white font-bold py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-500/20';
                input.className = 'glass-input text-center tracking-widest text-lg focus:ring-emerald-500';
            } else {
                // Modo Anulación
                title.textContent = 'Confirmar Anulación';
                msg.textContent = 'Ingresa tu contraseña para anular este registro. Se mantendrá el historial pero no afectará saldos.';
                icon.textContent = '🚫';
                iconContainer.className = 'w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-500 flex items-center justify-center text-3xl mx-auto mb-4';
                submitBtn.innerHTML = '🚫 Anular';
                submitBtn.className = 'flex-1 btn-danger justify-center font-bold py-2.5 rounded-xl';
                input.className = 'glass-input text-center tracking-widest text-lg focus:ring-orange-500';
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
                input.focus();
            }, 10);
        }

        function closeAnularModal() {
            const modal = document.getElementById('global-anular-modal');
            const card  = document.getElementById('global-anular-card');
            modal.classList.add('opacity-0');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // ─── MODAL GLOBAL DE RECHAZAR COTIZACIÓN ─────────────────────────────
        function openRechazarModal(actionUrl) {
            const modal = document.getElementById('global-rechazar-modal');
            const card  = document.getElementById('global-rechazar-card');
            const form  = document.getElementById('global-rechazar-form');

            form.action = actionUrl;

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeRechazarModal() {
            const modal = document.getElementById('global-rechazar-modal');
            const card  = document.getElementById('global-rechazar-card');
            modal.classList.add('opacity-0');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeAnularModal();
        });

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
                                var calendarWidth = fp.calendarContainer.offsetWidth;
                                var leftPos = rect.left + scrollX + (rect.width / 2) - (calendarWidth / 2);
                                fp.calendarContainer.style.top  = (rect.bottom + scrollY + 4) + 'px';
                                fp.calendarContainer.style.left = leftPos + 'px';
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

        // ─── REPOSICIONAR TOMSELECT DROPDOWNS AL CAMBIAR SIDEBAR ───────────────
        // Trackear instancias TomSelect para reposicionar al cambiar sidebar
        window._glassTomSelectInstances = window._glassTomSelectInstances || [];

        function trackTomSelectDropdowns(durationMs) {
            const start = performance.now();
            function tick(now) {
                if (window._glassTomSelectInstances) {
                    window._glassTomSelectInstances.forEach(function(ts) {
                        if (ts && ts.isOpen && ts.dropdown && ts.wrapper) {
                            var control = ts.wrapper;
                            var rect = control.getBoundingClientRect();
                            var scrollY = window.scrollY || window.pageYOffset;
                            var scrollX = window.scrollX || window.pageXOffset;
                            var dropdownWidth = ts.dropdown.offsetWidth;
                            var leftPos = rect.left + scrollX + (rect.width / 2) - (dropdownWidth / 2);
                            ts.dropdown.style.top = (rect.bottom + scrollY + 4) + 'px';
                            ts.dropdown.style.left = leftPos + 'px';
                        }
                    });
                }
                if (now - start < durationMs) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        }

        // Detectar cambios en el sidebar (hover/expanded)
        const sidebar = document.getElementById('ts-sidebar');
        if (sidebar) {
            sidebar.addEventListener('mouseenter', () => {
                trackTomSelectDropdowns(300);
            });
            sidebar.addEventListener('mouseleave', () => {
                trackTomSelectDropdowns(300);
            });
        }

        // Cierra el dropdown de notificaciones al hacer clic fuera
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
                    // Omite la fila de 'estado vacío' (colspan)
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
        <div id="ts-notif-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4" style="max-width: 550px;">

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

            {{-- Contenedor Central para alinear filtros e información al mismo ancho --}}
            <div class="w-full max-w-[450px] mx-auto flex flex-col flex-1 pb-4">
                {{-- Filtros / Tabs --}}
                <div class="flex flex-nowrap justify-center gap-1.5 mb-4 w-full">
                <button onclick="filterNotifs('all')" id="btn-notif-all" class="notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60">Todos</button>
                @if($mantPendientes > 0)
                <button onclick="filterNotifs('mant')" id="btn-notif-mant" class="notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60">Mantenimiento</button>
                @endif
                @if($elecPendientes > 0)
                <button onclick="filterNotifs('elec')" id="btn-notif-elec" class="notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-900/60">Electrónica</button>
                @endif
                @if($cotPendientes > 0)
                <button onclick="filterNotifs('cot')" id="btn-notif-cot" class="notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60">Cotizaciones</button>
                @endif
                @if($cajaPendientes > 0)
                <button onclick="filterNotifs('caja')" id="btn-notif-caja" class="notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60">Saldos</button>
                @endif
            </div>

            {{-- Scrollable list of all pending items --}}
            <div class="w-full max-h-[50vh] overflow-y-auto space-y-2 pr-1 mb-5 content-scroll">

                {{-- Mantenimientos --}}
                @foreach($mantList as $m)
                <a href="{{ route('mantenimientos.show', $m->id) }}" onclick="closeNotifModal()" data-notif-type="mant"
                   class="notif-item flex items-center justify-between gap-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors group relative overflow-hidden">
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
                <a href="{{ route('electronicas.show', $e->id) }}" onclick="closeNotifModal()" data-notif-type="elec"
                   class="notif-item flex items-center justify-between gap-3 p-3 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors group relative overflow-hidden">
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

                {{-- Cotizaciones --}}
                @foreach($cotList as $c)
                <a href="{{ route('cotizaciones.show', $c['id']) }}" onclick="closeNotifModal()" data-notif-type="cot"
                   class="notif-item flex items-center justify-between gap-3 p-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500 rounded-l-xl"></div>
                    <div class="pl-3 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Cotización</span>
                            <span class="text-[10px] font-bold text-indigo-500 dark:text-indigo-300">{{ $c['codigo'] }}</span>
                        </div>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $c['cliente']['nombre'] ?? 'N/A' }}</p>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold truncate">Total: ${{ number_format($c['total'], 0, ',', '.') }}</p>
                    </div>
                    <span class="shrink-0 text-indigo-500 dark:text-indigo-400 group-hover:translate-x-1 transition-transform text-lg">→</span>
                </a>
                @endforeach

                {{-- Facturas con saldo pendiente --}}
                @foreach($cajaList as $f)
                <a href="{{ route('inventario.facturas.show', $f->id) }}" onclick="closeNotifModal()" data-notif-type="caja"
                   class="notif-item flex items-center justify-between gap-3 p-3 rounded-xl bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800 hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-orange-500 rounded-l-xl"></div>
                    <div class="pl-3 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-wider">Saldo Factura</span>
                            <span class="text-[10px] font-bold text-orange-500 dark:text-orange-300">{{ $f->numero_factura }}</span>
                        </div>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $f->facturable->nombre ?? $f->facturable->nombre_razon_social ?? '—' }}</p>
                        <p class="text-xs text-orange-600 dark:text-orange-400 font-semibold">
                            Saldo: ${{ number_format($f->saldo_pendiente, 0, ',', '.') }}
                        </p>
                    </div>
                    <span class="shrink-0 text-orange-500 dark:text-orange-400 group-hover:translate-x-1 transition-transform text-lg">→</span>
                </a>
                @endforeach

                {{-- Ingresos/Egresos con saldo pendiente --}}
                @foreach($movimientosPendientes as $mov)
                @php
                    $isIngreso = $mov->tipo_movimiento === 'ingreso';
                    $bgClass = $isIngreso ? 'bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 border-emerald-100 dark:border-emerald-800' : 'bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/40 border-orange-100 dark:border-orange-800';
                    $barClass = $isIngreso ? 'bg-emerald-500' : 'bg-orange-500';
                    $titleClass = $isIngreso ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400';
                    $idClass = $isIngreso ? 'text-emerald-500 dark:text-emerald-300' : 'text-orange-500 dark:text-orange-300';
                    $montoClass = $isIngreso ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400';
                    $arrowClass = $isIngreso ? 'text-emerald-500 dark:text-emerald-400' : 'text-orange-500 dark:text-orange-400';
                @endphp
                <a href="{{ route('caja.edit', $mov->id) }}" onclick="closeNotifModal()" data-notif-type="caja"
                   class="notif-item flex items-center justify-between gap-3 p-3 rounded-xl border {{ $bgClass }} transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full {{ $barClass }} rounded-l-xl"></div>
                    <div class="pl-3 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="text-[10px] font-black {{ $titleClass }} uppercase tracking-wider">Saldo {{ ucfirst($mov->tipo_movimiento) }}</span>
                            <span class="text-[10px] font-bold {{ $idClass }}">#{{ $mov->id }}</span>
                        </div>
                        <p class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate">{{ $mov->concepto->nombre ?? '—' }} - {{ $mov->persona ?? '—' }}</p>
                        <p class="text-xs {{ $montoClass }} font-semibold">
                            Falta pagar: ${{ number_format($mov->monto_total - $mov->monto, 0, ',', '.') }}
                        </p>
                    </div>
                    <span class="shrink-0 {{ $arrowClass }} group-hover:translate-x-1 transition-transform text-lg">→</span>
                </a>
                @endforeach

            </div>
            </div>

            <button onclick="closeNotifModal()" class="w-full btn-primary py-3 justify-center text-base">
                Cerrar
            </button>
        </div>
    </div>
    <script>
        function filterNotifs(type) {
            // Actualiza estilo de las pestañas
            const allTabs = document.querySelectorAll('.notif-tab');
            allTabs.forEach(btn => {
                // Restablece a estado no seleccionado
                if(btn.id === 'btn-notif-all') {
                    btn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-mant') {
                    btn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-elec') {
                    btn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-cot') {
                    btn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-caja') {
                    btn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60'; btn.removeAttribute('style');
                }
            });

            // Establece estado activo
            const activeBtn = document.getElementById('btn-notif-' + type);
            if(activeBtn) {
                if(type === 'all') {
                    activeBtn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-emerald-200 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700'; activeBtn.removeAttribute('style');
                } else if(type === 'mant') {
                    activeBtn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-blue-200 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 border border-blue-300 dark:border-blue-700'; activeBtn.removeAttribute('style');
                } else if(type === 'elec') {
                    activeBtn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-purple-200 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200 border border-purple-300 dark:border-purple-700'; activeBtn.removeAttribute('style');
                } else if(type === 'cot') {
                    activeBtn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-indigo-200 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200 border border-indigo-300 dark:border-indigo-700'; activeBtn.removeAttribute('style');
                } else if(type === 'caja') {
                    activeBtn.className = 'notif-tab whitespace-nowrap px-2.5 py-1.5 rounded-full text-xs font-bold transition-colors bg-amber-200 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700'; activeBtn.removeAttribute('style');
                }
            }

            // Filtra elementos
            const items = document.querySelectorAll('.notif-item');
            items.forEach(item => {
                if(type === 'all' || item.dataset.notifType === type) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function toggleNotifDropdown() {
            const d = document.getElementById('notif-dropdown');
            if (d.classList.contains('hidden')) {
                d.classList.remove('hidden');
                setTimeout(() => { d.classList.remove('opacity-0', 'scale-95'); }, 10);
                // Cierra al hacer clic fuera
                setTimeout(() => {
                    document.addEventListener('click', function handler(e) {
                        if (!d.contains(e.target) && !e.target.closest('[onclick="toggleNotifDropdown()"]')) {
                            d.classList.add('opacity-0', 'scale-95');
                            setTimeout(() => d.classList.add('hidden'), 200);
                            document.removeEventListener('click', handler);
                        }
                    });
                }, 50);
            } else {
                d.classList.add('opacity-0', 'scale-95');
                setTimeout(() => d.classList.add('hidden'), 200);
            }
        }

        function openNotifModal(tab) {
            const modal = document.getElementById('ts-notif-modal');
            const card  = document.getElementById('ts-notif-card');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                card.classList.remove('scale-95', 'opacity-0');
            }, 10);
            // Apply tab filter if provided
            if (tab) {
                setTimeout(() => filterNotifs(tab), 50);
            }
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

    <script>
        // Inicialización INMEDIATA sin esperar a DOMContentLoaded para evitar FOUC
        (function() {
            // 1. Flatpickr para todos los inputs de tipo date
            window._flatpickrInstances = [];
            flatpickr("input[type='date']", {
                locale: "es",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                disableMobile: true,
                monthSelectorType: "static",
                appendTo: document.body,
                position: "auto center",
                onReady: function(_, __, fp) {
                    window._flatpickrInstances.push(fp);
                }
            });

            // Función global para inicializar TomSelect con el comportamiento "Limpieza Mágica"
            window.initGlassTomSelect = function(el) {
                if (el.classList.contains('tomselected')) return;

                let defaultPlaceholder = el.getAttribute('data-placeholder');
                let isNoSearch = el.classList.contains('no-search');
                
                if (el.options.length > 0 && el.options[0].value === "") {
                    if (!defaultPlaceholder) {
                        defaultPlaceholder = el.options[0].text.replace(/^[-—–\s]+|[-—–\s]+$/g, '');
                    }
                    if (!isNoSearch) {
                        el.options[0].textContent = ''; 
                    }
                } else if (!defaultPlaceholder) {
                    defaultPlaceholder = 'Seleccionar...';
                }

                if (!isNoSearch) {
                    defaultPlaceholder = ' '; 
                }

                let tsConfig = {
                    create: false,
                    maxOptions: 100,
                    placeholder: defaultPlaceholder,
                    highlight: false,
                    dropdownParent: 'body',
                    plugins: isNoSearch ? [] : ['clear_button'],
                    render: {
                        option: function(data, escape) {
                            return '<div class="ts-option-item">' + escape(data.text) + '</div>';
                        },
                        item: function(data, escape) {
                            return '<div class="ts-item-display">' + escape(data.text) + '</div>';
                        },
                        no_results: function(data, escape) {
                            return '<div class="no-results font-bold" style="padding: 8px 12px; color: #475569; font-size: 13px;">No se encontraron resultados para "<span class="text-blue-600">' + escape(data.input) + '</span>"</div>';
                        }
                    }
                };

                if (isNoSearch) {
                    tsConfig.onInitialize = function() {
                        if (this.control_input) {
                            this.control_input.readOnly = true;
                            this.control_input.style.caretColor = 'transparent';
                            this.control_input.style.cursor = 'pointer';
                        }
                    };
                }

                let tsInstance = new TomSelect(el, tsConfig);

                // Track open TomSelect instances for sidebar repositioning
                if (!window._glassTomSelectInstances) window._glassTomSelectInstances = [];
                window._glassTomSelectInstances.push(tsInstance);

                // CRÍTICO: Copiar clase 'no-search' al wrapper generado por TomSelect.
                // TomSelect NO copia clases del select original al wrapper,
                // pero nuestro CSS necesita .ts-wrapper.no-search para saber
                // si debe ocultar el item al abrir el dropdown.
                if (isNoSearch && tsInstance.wrapper) {
                    tsInstance.wrapper.classList.add('no-search');
                }

                if (el.classList.contains('stock-select')) {
                    if (tsInstance.wrapper) tsInstance.wrapper.classList.add('stock-select-wrapper');
                    if (tsInstance.dropdown) tsInstance.dropdown.classList.add('stock-select-dropdown');
                }

                if (!isNoSearch && tsInstance.options[""]) {
                    if (tsInstance.getValue() === "") {
                        tsInstance.clear(true);
                    }
                    tsInstance.removeOption("");
                }
            };

            // Formateador global de moneda (separadores de miles)
            window.formatCurrencyInput = function(input) {
                let val = input.value.replace(/[^0-9]/g, '');
                if (val === '') val = '0';
                
                if (val.length > 1 && val.startsWith('0')) {
                    val = val.substring(1);
                }

                const num = parseInt(val, 10);
                if (isNaN(num)) {
                    input.value = '0';
                } else {
                    input.value = window.formatNumber(num);
                }
                
                if (typeof window.recalcular === 'function') {
                    window.recalcular();
                }
            };

            // Formateador dual-input: visual (con puntos) + hidden (solo números)
            // Uso: oninput="formatCurrencyDual(this, 'hidden_input_id')"
            window.formatCurrencyDual = function(visualInput, realInputId) {
                const realInput = document.getElementById(realInputId);
                if (!realInput) return;
                
                // Solo dígitos en el valor real (hidden)
                let raw = visualInput.value.replace(/\D/g, '');
                realInput.value = raw || '0';
                
                // Formatear visual con puntos de miles (es-CO)
                if (raw !== '') {
                    const num = parseInt(raw, 10);
                    visualInput.value = num.toLocaleString('es-CO');
                } else {
                    visualInput.value = '0';
                }
                
                if (typeof window.recalcular === 'function') {
                    window.recalcular();
                }
            };

            window.formatNumber = function(num) {
                const parts = num.toString().split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return parts.join('.');
            };

            // Formatear inputs monetarios al cargar la página
            document.querySelectorAll('.precio-input, .price-input, #total_pagado').forEach(function(input) {
                const raw = input.value.replace(/\./g, '');
                const num = parseInt(raw, 10);
                if (!isNaN(num) && raw !== '') {
                    input.value = window.formatNumber(num);
                }
            });

            // 2. Tom Select para selects con clase glass-input en carga de página
            document.querySelectorAll("select.glass-input").forEach((el) => {
                window.initGlassTomSelect(el);
            });
            // Despachar evento global para que los formularios sepan cuándo TomSelect terminó
            document.dispatchEvent(new CustomEvent('tomselect:ready'));


            // 3. Auto-centrar elementos al navegar por ancla (hash)
            const activateHashTarget = (hashStr) => {
                try {
                    // Remover clase active-target anterior de cualquier elemento
                    document.querySelectorAll('.active-target').forEach(el => el.classList.remove('active-target'));
                    
                    const target = document.querySelector(hashStr);
                    if (target) {
                        // Agregar clase para simular :target de forma confiable
                        target.classList.add('active-target');
                        
                        setTimeout(() => {
                            // Desactivamos temporalmente el comportamiento CSS nativo que puede causar conflictos
                            const originalBehavior = document.documentElement.style.scrollBehavior;
                            document.documentElement.style.scrollBehavior = 'auto';
                            
                            // Calculamos matemáticamente el centro exacto
                            const targetPosition = target.getBoundingClientRect().top + window.scrollY;
                            const centerPosition = targetPosition - (window.innerHeight / 2) + (target.offsetHeight / 2);
                            
                            // Scroll suave manual absoluto
                            window.scrollTo({
                                top: centerPosition,
                                behavior: 'smooth'
                            });
                            
                            // Restauramos el comportamiento original después de la animación
                            setTimeout(() => {
                                document.documentElement.style.scrollBehavior = originalBehavior;
                            }, 800);
                            
                        }, 100);
                    }
                } catch(e) {}
            };

            const scrollToHash = () => {
                if (window.location.hash) {
                    activateHashTarget(window.location.hash);
                }
            };
            scrollToHash();
            window.addEventListener('hashchange', scrollToHash);

            // Interceptar clicks en enlaces a anclas para centrar incluso si el hash ya es el actual
            document.addEventListener('click', function(e) {
                const anchor = e.target.closest('a[href^="#"]');
                if (anchor) {
                    const hash = anchor.getAttribute('href');
                    if (hash && hash !== '#') {
                        try {
                            const id = hash.substring(1);
                            const target = document.getElementById(id);
                            if (target) {
                                e.preventDefault(); 
                                history.pushState(null, null, hash); 
                                activateHashTarget(hash);
                            }
                        } catch(err) {}
                    }
                }
            });
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const titleEl = document.querySelector('h1') || document.querySelector('.glass-card h2') || document.querySelector('h2');
            const printTitle = document.getElementById('print-page-title');
            if (titleEl && printTitle) {
                printTitle.innerText = titleEl.innerText.replace(/[📊⚙️⚡📦📈📋💵]/g, '').trim().toUpperCase();
            }
        });
    </script>
    <!-- Footer de impresión para el navegador -->
    <div class="hidden-screen print-footer" style="display: none;">
        <table style="width: 100%; border: none !important; margin: 0 !important; background: transparent !important;">
            <tr style="border: none !important; background: transparent !important;">
                <td style="border: none !important; text-align: right; padding: 0 !important; font-size: 8pt !important; color: #4a5568 !important; background: transparent !important;">
                    Página <span class="print-page-number"></span>
                </td>
            </tr>
        </table>
    </div>

    {{-- Visor de imagen minimalista - sin fondo, tamaño moderado --}}
    <div id="image-lightbox" onclick="closeImageLightbox()" style="display:none; position:fixed; inset:0; z-index:9999; background:transparent; align-items:center; justify-content:center; padding:2vh 2vw; opacity:0; visibility:hidden; transition:opacity 0.25s ease;">
        <div onclick="event.stopPropagation()" style="position:relative; display:inline-flex; flex-direction:column; align-items:center;">
            <button type="button" onclick="event.stopPropagation(); closeImageLightbox()" aria-label="Cerrar" style="position:absolute; top:-12px; right:-12px; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:16px; line-height:1; color:#64748b; background:rgba(255,255,255,0.9); border:1px solid rgba(0,0,0,0.05); border-radius:50%; cursor:pointer; box-shadow:0 2px 8px rgba(0,0,0,0.06); transition:all 0.15s ease; z-index:1;" onmouseover="this.style.transform='scale(1.1)'; this.style.background='#fff'" onmouseout="this.style.transform='scale(1)'; this.style.background='rgba(255,255,255,0.9)'">✕</button>
            <img id="image-lightbox-img" src="" alt="" style="max-width:70vw; max-height:65vh; width:auto; height:auto; border-radius:12px; box-shadow:0 12px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04); object-fit:contain; transform:scale(0.95); opacity:0; transition:transform 0.35s cubic-bezier(0.25,0.46,0.45,0.94), opacity 0.2s ease; cursor:pointer;">
        </div>
    </div>
    <script>
        let lightboxOpen = false;
        let lightboxOrigin = null;
        
        function openImageLightbox(src, alt = '', originEl = null) {
            const box = document.getElementById('image-lightbox');
            const img = document.getElementById('image-lightbox-img');
            
            if (originEl) {
                const rect = originEl.getBoundingClientRect();
                lightboxOrigin = {
                    x: rect.left + rect.width / 2,
                    y: rect.top + rect.height / 2,
                    width: rect.width,
                    height: rect.height
                };
            } else {
                lightboxOrigin = null;
            }
            
            img.src = src;
            img.alt = alt;
            box.style.display = 'flex';
            
            img.style.transform = 'scale(0.95)';
            img.style.opacity = '0';
            box.style.display = 'flex';
            
            requestAnimationFrame(() => {
                box.style.opacity = '1';
                box.style.visibility = 'visible';
                
                if (lightboxOrigin) {
                    const imgRect = img.getBoundingClientRect();
                    const dx = lightboxOrigin.x - (imgRect.left + imgRect.width / 2);
                    const dy = lightboxOrigin.y - (imgRect.top + imgRect.height / 2);
                    const scaleX = lightboxOrigin.width / imgRect.width;
                    const scaleY = lightboxOrigin.height / imgRect.height;
                    const scale = Math.min(scaleX, scaleY) * 0.95;
                    
                    img.style.transform = `translate(${dx}px, ${dy}px) scale(${scale})`;
                }
                
                img.offsetHeight;
                
                requestAnimationFrame(() => {
                    img.style.transition = 'transform 0.35s cubic-bezier(0.25,0.46,0.45,0.94), opacity 0.2s ease';
                    img.style.transform = 'scale(1)';
                    img.style.opacity = '1';
                });
            });
            
            lightboxOpen = true;
        }

        function closeImageLightbox() {
            const box = document.getElementById('image-lightbox');
            const img = document.getElementById('image-lightbox-img');
            
            box.style.opacity = '0';
            // Eliminado visibility='hidden' instantáneo para permitir ver la animación
            
            if (lightboxOrigin) {
                const imgRect = img.getBoundingClientRect();
                const dx = lightboxOrigin.x - (imgRect.left + imgRect.width / 2);
                const dy = lightboxOrigin.y - (imgRect.top + imgRect.height / 2);
                const scaleX = lightboxOrigin.width / imgRect.width;
                const scaleY = lightboxOrigin.height / imgRect.height;
                const scale = Math.min(scaleX, scaleY) * 0.95;
                
                img.style.transition = 'transform 0.35s cubic-bezier(0.25,0.46,0.45,0.94), opacity 0.3s ease';
                img.style.transform = `translate(${dx}px, ${dy}px) scale(${scale})`;
            } else {
                img.style.transition = 'transform 0.35s cubic-bezier(0.25,0.46,0.45,0.94), opacity 0.3s ease';
                img.style.transform = 'scale(0.95)';
            }
            img.style.opacity = '0';
            
            setTimeout(() => {
                box.style.display = 'none';
                box.style.visibility = 'hidden';
                img.style.transition = 'transform 0.35s cubic-bezier(0.25,0.46,0.45,0.94), opacity 0.2s ease';
            }, 350);
            
            lightboxOpen = false;
            lightboxOrigin = null;
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && lightboxOpen) closeImageLightbox();
        });

        document.getElementById('image-lightbox').addEventListener('click', function(e) {
            if (e.target === this) closeImageLightbox();
        });
    </script>

    @stack('modals')
</body>
</html>
