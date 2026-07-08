@php
    $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
    $logoBase64 = null;
    if ($empresa->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->logo_path)) {
        $path = \Illuminate\Support\Facades\Storage::disk('public')->path($empresa->logo_path);
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
@endphp
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
    
    <!-- Scripts de Librerías (cargados en el head para prevenir FOUC) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    <!-- CSS Propio (Liquid Glass) - va DESPUÉS para sobreescribir estilos base -->
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ time() }}">
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Ajuste de placeholder para TomSelect nativo */
        .ts-control input::placeholder {
            color: #94a3b8 !important;
            opacity: 1 !important;
            font-weight: 500 !important;
            transition: color 0.2s ease;
        }
        /* Hacer que el placeholder desaparezca al hacer clic (focus) para que quede en blanco */
        .ts-control.focus input::placeholder,
        .ts-control input:focus::placeholder {
            color: transparent !important;
        }
        /* Evitar Flash of Unstyled Content (FOUC) en los selects nativos:
           Usamos visibility:hidden (no color) para no afectar elementos hijos */
        select.glass-input:not(.tomselected) {
            visibility: hidden !important;
        }
        /* Evitar expansión vertical (multilínea) cuando se seleccionan textos muy largos */
        .ts-wrapper.single .ts-control {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            overflow: hidden !important;
            padding-right: 35px !important; /* Espacio para el botón X */
            position: relative !important;
            box-shadow: none !important; /* Eliminar sombra nativa para evitar doble borde */
        }

        .ts-control > .item {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 100% !important;
            display: inline-block !important;
            pointer-events: none; /* Que el clic pase al control */
        }
        .ts-item-display {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: inline-block !important;
            max-width: 100% !important;
        }
        /* Botón de limpiar siempre a la derecha */
        .ts-control .clear-button {
            position: absolute !important;
            right: 8px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            z-index: 10;
        }
         /* Si hay un ítem seleccionado, ocultar el input para que no estorbe */
        .ts-wrapper.single.has-items:not(.focus):not(.dropdown-active) .ts-control > input {
            display: none !important;
        }
        /* NOTA: NO ocultamos .item al abrir dropdown para selects sin búsqueda.
           TomSelect con controlInput:null no necesita esto y causa el flash. */
        
        /* Ocultar el icono de calendario nativo de Chrome para evitar salto visual */
        input[type="date"]:not(.flatpickr-input)::-webkit-calendar-picker-indicator {
            display: none !important;
            -webkit-appearance: none !important;
        }
        input[type="date"]:not(.flatpickr-input)::-webkit-datetime-edit,
        input[type="date"]:not(.flatpickr-input)::-webkit-datetime-edit-fields-wrapper {
            padding: 0 !important;
            margin: 0 !important;
        }
        

        .flatpickr-calendar {
            background: rgba(255, 255, 255, 0.55) !important;
            backdrop-filter: blur(24px) !important;
            -webkit-backdrop-filter: blur(24px) !important;
            border: 1px solid rgba(148,163,184,0.3) !important;
            border-radius: 16px !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15), 0 0 0 1px rgba(255,255,255,0.5) inset !important;
            padding: 10px !important;
            font-family: inherit !important;
            width: 310px !important; /* Ancho fijo suficiente para 7 columnas */
        }
        .dark .flatpickr-calendar {
            background: rgba(15, 23, 42, 0.65) !important;
            border: 1px solid rgba(100,116,139,0.4) !important;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05) inset !important;
        }
        /* Cabecera del mes */
        .flatpickr-months {
            padding: 4px 0 8px !important;
            align-items: center !important;
        }
        .flatpickr-month {
            height: 36px !important;
        }
        .flatpickr-current-month {
            font-size: 15px !important;
            font-weight: 800 !important;
            color: #1e293b !important;
            padding-top: 4px !important;
        }
        .dark .flatpickr-current-month {
            color: #e2e8f0 !important;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            font-weight: 800 !important;
            background: transparent !important;
            font-size: 15px !important;
            color: #1e293b !important;
            cursor: pointer;
        }
        .dark .flatpickr-current-month .flatpickr-monthDropdown-months {
            color: #e2e8f0 !important;
        }
        .flatpickr-current-month .numInputWrapper {
            width: 7.5ch !important;
            margin-left: 2px !important;
        }
        .flatpickr-current-month input.cur-year {
            font-weight: 700 !important;
            font-size: 16px !important;
            color: #000000 !important; /* Negro absoluto en fondo claro */
            padding-right: 22px !important; /* Crea el espacio entre el número y las flechas */
            box-sizing: border-box !important;
        }
        .dark .flatpickr-current-month input.cur-year {
            color: #ffffff !important;
        }
        /* Flechas de navegación de mes */
        .flatpickr-prev-month, .flatpickr-next-month {
            width: 32px !important;
            height: 32px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 8px !important;
            transition: background 0.2s !important;
            top: 8px !important;
            padding: 6px !important;
        }
        .flatpickr-prev-month:hover, .flatpickr-next-month:hover {
            background: rgba(59,130,246,0.15) !important;
        }
        .flatpickr-prev-month svg, .flatpickr-next-month svg {
            width: 16px !important;
            height: 16px !important;
        }
        .flatpickr-prev-month svg path, .flatpickr-next-month svg path {
            fill: #2563eb !important;
        }
        .dark .flatpickr-prev-month svg path, .dark .flatpickr-next-month svg path {
            fill: #60a5fa !important;
        }
        /* Flechas de año (arriba/abajo) */
        .flatpickr-current-month .arrowUp,
        .flatpickr-current-month .arrowDown {
            border: none !important;
            padding: 2px 5px !important;
            border-radius: 4px !important;
            cursor: pointer !important;
            transition: background 0.15s !important;
            right: 0 !important; /* Alineado a la derecha del wrapper */
        }
        .flatpickr-current-month .arrowUp:after {
            border-bottom-color: #334155 !important; /* Gris oscuro en vez de azul */
            border-width: 0 4px 5px !important;
        }
        .flatpickr-current-month .arrowDown:after {
            border-top-color: #334155 !important; /* Gris oscuro en vez de azul */
            border-width: 5px 4px 0 !important;
        }
        .dark .flatpickr-current-month .arrowUp:after {
            border-bottom-color: #60a5fa !important;
        }
        .dark .flatpickr-current-month .arrowDown:after {
            border-top-color: #60a5fa !important;
        }
        .flatpickr-current-month .numInputWrapper:hover .arrowUp,
        .flatpickr-current-month .numInputWrapper:hover .arrowDown,
        .flatpickr-current-month .numInputWrapper span:hover {
            background: rgba(59,130,246,0.15) !important;
        }
        /* Días de la semana */
        .flatpickr-weekdays {
            padding: 4px 0 !important;
        }
        .flatpickr-weekday {
            color: #64748b !important;
            font-weight: 700 !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
        }
        .dark .flatpickr-weekday {
            color: #94a3b8 !important;
        }
        /* Días del mes */
        .flatpickr-days, .dayContainer {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }
        .flatpickr-day {
            border-radius: 8px !important;
            color: #334155 !important;
            font-weight: 600 !important;
            transition: all 0.15s !important;
            height: 36px !important;
            line-height: 36px !important;
            max-width: 38px !important;
            margin: 0 auto !important; /* El margin causaba el wrap de los días */
        }
        .dark .flatpickr-day {
            color: #cbd5e1 !important;
        }
        .flatpickr-day:hover {
            background: rgba(59,130,246,0.12) !important;
            border-color: transparent !important;
            color: #2563eb !important;
        }
        .dark .flatpickr-day:hover {
            background: rgba(96,165,250,0.15) !important;
            color: #93c5fd !important;
        }
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
            border-color: #2563eb !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(37,99,235,0.4) !important;
            font-weight: 800 !important;
        }
        .flatpickr-day.today {
            border-color: #3b82f6 !important;
            font-weight: 800 !important;
        }
        .dark .flatpickr-day.today {
            border-color: #60a5fa !important;
        }
        .flatpickr-day.prevMonthDay, .flatpickr-day.nextMonthDay {
            opacity: 0.3 !important;
        }
        /* Ocultar la 6ta fila si solo contiene días del mes siguiente */
        .flatpickr-day.nextMonthDay:nth-child(n+36) {
            display: none !important;
        }
        /* Input alternativo (el que se muestra con altInput: true) */
        .flatpickr-input.flatpickr-mobile, input.flatpickr-input.form-control {
            display: none !important;
        }

        /* Cursores correctos para TomSelect */
        .ts-control { cursor: pointer !important; }
        .ts-control input { 
            cursor: text !important; 
            user-select: text !important; 
            -webkit-user-select: text !important;
        }

        /* Centrado absoluto del logo en el topbar ignorando el sidebar */
        .topbar-logo-container {
            position: absolute !important;
            left: calc(50vw - var(--sidebar-w)) !important;
            transform: translate(-50%, -50%) !important;
            top: 50% !important;
            transition: left 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        #ts-sidebar:hover ~ #main-wrapper .topbar-logo-container,
        #ts-sidebar.expanded ~ #main-wrapper .topbar-logo-container {
            left: calc(50vw - var(--sidebar-expanded)) !important;
        }
        @media (max-width: 1024px) {
            .topbar-logo-container {
                left: 50% !important;
            }
        }

        /* ─── PRINT MEDIA QUERIES ─── */
        .hidden-screen {
            display: none !important;
        }

        @media print {
            .no-print,
            header,
            aside,
            nav,
            .btn,
            button,
            .btn-primary,
            .btn-secondary,
            .btn-danger,
            .btn-ghost,
            a[href*="export"],
            .filters-section,
            .filter-container,
            .search-bar,
            form,
            .ts-dropdown,
            .ts-wrapper,
            .ts-control,
            .pagination,
            .breadcrumbs,
            .actions,
            #toast-container,
            #ts-modal,
            #global-anular-modal,
            #ts-notif-modal {
                display: none !important;
            }

            .hidden-screen {
                display: block !important;
            }

            @page {
                margin: 10mm 8mm 15mm 8mm;
            }

            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                counter-reset: page 0;
            }

            /* Disable flexbox layouts during print to prevent desktop viewport scaling and right-side clipping */
            .flex.min-h-screen,
            #main-wrapper {
                display: block !important;
                width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }

            #ts-main,
            main {
                display: block !important;
                width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 8mm 6mm !important; /* Force physical margins even if browser margin is set to None */
                box-sizing: border-box !important;
                box-shadow: none !important;
                background: transparent !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
            }

            span.pill, .badge, .pill, .pill-pending, .pill-done, .pill-preventivo, .pill-especialidad, .pill-efectivo, .pill-anulado, table td span, .ts-table td span, .reportes-tabla-imprimir td span {
                display: inline !important;
                border: none !important;
                background: none !important;
                background-color: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
                font-weight: normal !important;
                text-transform: uppercase !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            .no-print-emoji, 
            table td span.no-print-emoji,
            .ts-table td span.no-print-emoji,
            .reportes-tabla-imprimir td span.no-print-emoji,
            .grid p span, 
            .ts-table td span.mr-2,
            .reportes-tabla-imprimir td span.mr-2,
            span.text-lg {
                display: none !important;
            }

            /* Forzar visualización de tablas completas */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 15px !important;
                font-size: 9px !important;
            }
            th, td {
                border: 1px solid #cbd5e0 !important;
                padding: 5px 6px !important;
                color: #000000 !important;
                background-color: #ffffff !important;
            }
            th {
                background-color: #2d3748 !important;
                color: #ffffff !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }
            tbody tr:nth-child(even) td {
                background-color: #f7fafc !important;
            }
            tr {
                page-break-inside: avoid !important;
            }

            /* Convertir glass-cards en contenedores limpios */
            .glass-card {
                background: none !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin-bottom: 20px !important;
            }

            /* Ocultar sombras e inputs */
            input, select, textarea {
                border: none !important;
                background: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }

            /* Footer de impresión */
            .print-footer {
                display: block !important;
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 30px !important;
                border-top: 1px solid #cbd5e0 !important;
                padding-top: 8px !important;
                font-size: 8pt !important;
                color: #4a5568 !important;
                background-color: #ffffff !important;
                z-index: 9999 !important;
            }
            .print-page-number::after {
                counter-increment: page;
                content: counter(page) !important;
            }
        }
    </style>
    
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
        <aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col">
            <!-- Brand / Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200/40 dark:border-white/5 shrink-0 px-6 relative transition-all duration-150">
                <span class="text-[11px] font-semibold tracking-[0.15em] text-[#06B6D4] uppercase font-logo text-center w-full sidebar-brand-text transition-opacity duration-200">NAVEGACIÓN</span>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 flex flex-col justify-between overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 gap-1">
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
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') && !request()->routeIs('stocks.reportes') ? 'active' : '' }}" title="Control Stock">
                    <span class="nav-icon">📦</span>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Operaciones (C/V)">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') && !request()->routeIs('mantenimientos.reportes') ? 'active' : '' }}" title="Mantenimientos">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') && !request()->routeIs('electronicas.reportes') ? 'active' : '' }}" title="Electrónica">
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
                <a href="{{ route('reportes.financiero.diario') }}" class="nav-item {{ request()->routeIs('reportes.*') || request()->routeIs('mantenimientos.reportes') || request()->routeIs('electronicas.reportes') || request()->routeIs('stocks.reportes') ? 'active' : '' }}" title="Info Operativos">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Info Operativos</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-label">Empresa</span>
                </a>
                @if(auth()->user() && auth()->user()->isAdmin())
                <a href="{{ route('eventos.index') }}" class="nav-item {{ request()->routeIs('eventos.*') ? 'active' : '' }}" title="Eventos (Auditoría)">
                    <span class="nav-icon">🕵️</span>
                    <span class="nav-label">Eventos</span>
                </a>
                @endif
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
                <div class="topbar-logo-container hidden md:flex justify-center items-center">
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
                        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg relative" onclick="toggleNotifDropdown()">
                            🔔
                            @if(isset($totalPendientes) && $totalPendientes > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border border-white dark:border-slate-800"></span>
                                </span>
                            @endif
                        </button>
                        
                        <!-- Notification Dropdown - Acrylic Glass, centered, rectangular -->
                        <div id="notif-dropdown" class="hidden absolute right-1/2 translate-x-1/2 mt-3 z-50 opacity-0 scale-95 transition-all duration-200 origin-top" style="width:240px">

                            <!-- Arrow tip -->
                            <div class="flex justify-center">
                                <div id="notif-arrow" class="w-3 h-3 rotate-45 border-l border-t" style="margin-bottom:-7px; background:rgba(255,255,255,0.82); border-color:rgba(255,255,255,0.4);"></div>
                            </div>

                            <!-- Acrylic panel -->
                            <div id="notif-panel" class="rounded-lg overflow-hidden"
                                 style="background:rgba(255,255,255,0.82); border:1px solid rgba(255,255,255,0.5); backdrop-filter:blur(32px) saturate(200%); -webkit-backdrop-filter:blur(32px) saturate(200%); box-shadow:0 8px 32px rgba(0,0,0,0.18), inset 0 1px 0 rgba(255,255,255,0.6);">

                                <!-- Header -->
                                <div id="notif-header" class="px-3 py-2.5 relative border-b text-center"
                                     style="background:rgba(37,99,235,0.12); border-color:rgba(37,99,235,0.15);">
                                    <span id="notif-title" class="text-[11px] font-semibold tracking-[0.15em] font-logo inline-block uppercase" style="color:#06B6D4;">Pendientes</span>
                                    @if(isset($totalPendientes) && $totalPendientes > 0)
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center min-w-[20px] h-[20px] px-1 rounded-full text-white text-[10px] font-black" style="background:#ef4444; box-shadow:0 2px 6px rgba(239,68,68,0.5)">{{ $totalPendientes }}</span>
                                    @endif
                                </div>

                                <!-- Items -->
                                @if(isset($totalPendientes) && $totalPendientes > 0)
                                <div class="py-1">
                                    @if($mantPendientes > 0)
                                    <button onclick="openNotifModal('mant'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                            style="border-bottom:1px solid rgba(0,0,0,0.05);">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(59,130,246,0.15);">🔧</div>
                                        <span class="text-sm font-semibold flex-1" style="color:#1e293b;">Mantenimiento</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#3b82f6; box-shadow:0 2px 6px rgba(59,130,246,0.4)">{{ $mantPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($elecPendientes > 0)
                                    <button onclick="openNotifModal('elec'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-purple-50 dark:hover:bg-purple-900/30"
                                            style="border-bottom:1px solid rgba(0,0,0,0.05);">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(168,85,247,0.15);">⚡</div>
                                        <span class="text-sm font-semibold flex-1" style="color:#1e293b;">Electrónica</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#a855f7; box-shadow:0 2px 6px rgba(168,85,247,0.4)">{{ $elecPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($cajaPendientes > 0)
                                    <button onclick="openNotifModal('caja'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(245,158,11,0.15);">💰</div>
                                        <span class="text-sm font-semibold flex-1" style="color:#1e293b;">Saldos / Caja</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#f59e0b; box-shadow:0 2px 6px rgba(245,158,11,0.4)">{{ $cajaPendientes }}</span>
                                    </button>
                                    @endif
                                </div>
                                <!-- Footer CTA -->
                                <button id="notif-footer" onclick="openNotifModal('all'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                        class="w-full py-2.5 text-xs font-bold text-center tracking-wide transition-colors hover:bg-blue-50 dark:hover:bg-slate-800/50"
                                        style="border-top:1px solid rgba(37,99,235,0.15); background:rgba(37,99,235,0.10); color:#1d4ed8;">
                                    Ver todos →
                                </button>
                                @else
                                <div class="py-1">
                                    <div class="px-4 py-5 text-center">
                                        <div class="text-2xl mb-1">✅</div>
                                        <p class="text-sm font-semibold" style="color:#374151;">¡Todo al día!</p>
                                        <p class="text-xs mt-0.5" style="color:#9ca3af;">Sin tareas pendientes.</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <script>
                    // Adapt acrylic dropdown to dark mode
                    function applyNotifDropdownTheme() {
                        const isDark = document.documentElement.classList.contains('dark');
                        const panel = document.getElementById('notif-panel');
                        const header = document.getElementById('notif-header');
                        const arrow = document.getElementById('notif-arrow');
                        const title = document.getElementById('notif-title');
                        const svg = document.getElementById('notif-header-svg');
                        const footer = document.getElementById('notif-footer');
                        
                        if (!panel) return;
                        
                        if (isDark) {
                            panel.style.background = 'rgba(15,23,42,0.98)';
                            panel.style.border = '1px solid rgba(255,255,255,0.12)';
                            panel.style.boxShadow = '0 20px 40px rgba(0,0,0,0.8), inset 0 1px 0 rgba(255,255,255,0.1)';
                            if (arrow)  { arrow.style.background = 'rgba(15,23,42,0.98)'; arrow.style.borderColor = 'rgba(255,255,255,0.12)'; }
                            
                            if (header) { 
                                header.style.background = 'transparent'; 
                                header.style.borderColor = 'rgba(255,255,255,0.1)'; 
                            }
                            if (title) { title.style.color = '#06B6D4'; } // cyan-500 matching NAVEGACION
                            
                            if (footer) {
                                footer.style.background = 'rgba(30,41,59,0.5)'; // bg-slate-800/50
                                footer.style.borderColor = 'rgba(255,255,255,0.05)';
                                footer.style.color = '#38bdf8';
                            }
                            
                            document.querySelectorAll('.notif-row span.flex-1').forEach(el => el.style.color = '#f1f5f9');
                            document.querySelectorAll('.notif-row').forEach(el => el.style.borderBottomColor = 'rgba(255,255,255,0.05)');
                        } else {
                            panel.style.background = 'rgba(255,255,255,0.98)';
                            panel.style.border = '1px solid rgba(0,0,0,0.1)';
                            panel.style.boxShadow = '0 20px 40px rgba(0,0,0,0.1)';
                            if (arrow)  { arrow.style.background = 'rgba(255,255,255,0.98)'; arrow.style.borderColor = 'rgba(0,0,0,0.1)'; }
                            
                            if (header) { 
                                header.style.background = 'transparent'; 
                                header.style.borderColor = 'rgba(0,0,0,0.08)'; 
                            }
                            if (title) { title.style.color = '#06B6D4'; } // cyan-500 matching NAVEGACION
                            
                            if (footer) {
                                footer.style.background = 'rgba(248,250,252,0.8)'; // bg-slate-50/80
                                footer.style.borderColor = 'rgba(0,0,0,0.05)';
                                footer.style.color = '#2563eb';
                            }
                            
                            document.querySelectorAll('.notif-row span.flex-1').forEach(el => el.style.color = '#1e293b');
                            document.querySelectorAll('.notif-row').forEach(el => el.style.borderBottomColor = 'rgba(0,0,0,0.05)');
                        }
                    }
                    document.addEventListener('DOMContentLoaded', applyNotifDropdownTheme);
                    // Re-apply when theme toggle is clicked
                    document.addEventListener('click', e => {
                        if (e.target.closest('#theme-toggle') || e.target.closest('#theme-toggle-login')) {
                            setTimeout(applyNotifDropdownTheme, 50);
                        }
                    });
                    </script>

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
            <main id="ts-main" class="flex-1 p-4 sm:p-6 lg:p-8 pb-[50vh] relative z-10">
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
                                <div><strong>Fecha Impresión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
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

            {{-- Filtros / Tabs --}}
            <div class="flex flex-wrap gap-2 mb-4 w-full justify-center">
                <button onclick="filterNotifs('all')" id="btn-notif-all" class="notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60">Todos</button>
                @if($mantPendientes > 0)
                <button onclick="filterNotifs('mant')" id="btn-notif-mant" class="notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60">Mantenimiento</button>
                @endif
                @if($elecPendientes > 0)
                <button onclick="filterNotifs('elec')" id="btn-notif-elec" class="notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-900/60">Electrónica</button>
                @endif
                @if($cajaPendientes > 0)
                <button onclick="filterNotifs('caja')" id="btn-notif-caja" class="notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60">Saldos/Caja</button>
                @endif
            </div>

            {{-- Scrollable list of all pending items --}}
            <div class="w-full max-h-[50vh] overflow-y-auto space-y-2 pr-1 scrollbar-hide mb-5">

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
                            <span class="text-[10px] font-bold {{ $idClass }}">#{{ str_pad($mov->id, 4, '0', STR_PAD_LEFT) }}</span>
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

            <button onclick="closeNotifModal()" class="w-full btn-primary py-3 justify-center text-base">
                Cerrar
            </button>
        </div>
    </div>
    <script>
        function filterNotifs(type) {
            // Update tabs styling
            const allTabs = document.querySelectorAll('.notif-tab');
            allTabs.forEach(btn => {
                // Reset to unselected state
                if(btn.id === 'btn-notif-all') {
                    btn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-mant') {
                    btn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-elec') {
                    btn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-900/60'; btn.removeAttribute('style');
                } else if(btn.id === 'btn-notif-caja') {
                    btn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60'; btn.removeAttribute('style');
                }
            });

            // Set active state
            const activeBtn = document.getElementById('btn-notif-' + type);
            if(activeBtn) {
                if(type === 'all') {
                    activeBtn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-emerald-200 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700'; activeBtn.removeAttribute('style');
                } else if(type === 'mant') {
                    activeBtn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-blue-200 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 border border-blue-300 dark:border-blue-700'; activeBtn.removeAttribute('style');
                } else if(type === 'elec') {
                    activeBtn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-purple-200 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200 border border-purple-300 dark:border-purple-700'; activeBtn.removeAttribute('style');
                } else if(type === 'caja') {
                    activeBtn.className = 'notif-tab px-3 py-1 rounded-full text-xs font-bold transition-colors bg-amber-200 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-300 dark:border-amber-700'; activeBtn.removeAttribute('style');
                }
            }

            // Filter items
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
                // Close on outside click
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

                // CRÍTICO: Copiar clase 'no-search' al wrapper generado por TomSelect.
                // TomSelect NO copia clases del select original al wrapper,
                // pero nuestro CSS necesita .ts-wrapper.no-search para saber
                // si debe ocultar el item al abrir el dropdown.
                if (isNoSearch && tsInstance.wrapper) {
                    tsInstance.wrapper.classList.add('no-search');
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
                // Si el input está centrado (ej: total a pagar) y se borra todo, dejar un '0'
                let val = input.value.replace(/\D/g, ''); 
                if (val === '') val = '0';
                
                // Si el primer carácter es 0 y hay más números, quitamos el 0 a la izquierda
                if (val.length > 1 && val.startsWith('0')) {
                    val = val.substring(1);
                }

                input.value = parseInt(val, 10).toLocaleString('es-CO');
                
                if (typeof window.recalcular === 'function') {
                    window.recalcular();
                }
            };

            // Remover el formato antes de enviar cualquier formulario que tenga inputs de moneda
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    this.querySelectorAll('.precio-input, #total_pagado').forEach(input => {
                        input.value = input.value.replace(/\./g, '');
                    });
                });
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

    @stack('modals')
</body>
</html>
