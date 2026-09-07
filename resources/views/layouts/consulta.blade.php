<!DOCTYPE html>
<html lang="es" class="preload">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Consulta - Tecni Systemas</title>
    
    <!-- Favicon (Apple Liquid Glass) -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=1.0">
    
    {{-- Vite (Tailwind CSS compilado + JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- CSS Librerías --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    
    {{-- Scripts Librerías --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    
    {{-- CSS Propio --}}
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}?v=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    {{-- Lógica de Tema Temprana --}}
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.classList.remove('preload');
        });
    </script>
</head>
<body class="ts-bg text-gray-800 dark:text-gray-100 min-h-screen">
    {{-- Header mínimo solo logo y logout --}}
    <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-white/10 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('consulta.mantenimientos') }}" class="flex items-center gap-2">
                <span class="text-[#2563EB] dark:text-[#3B82F6] font-logo font-black text-xl">TECNI</span>
                <span class="text-slate-800 dark:text-white font-logo font-black text-xl">SYSTEMAS</span>
                <span class="ml-2 px-2 py-0.5 text-[10px] font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">CONSULTA</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-all group text-lg shadow-sm" title="Cerrar Sesión">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                </button>
            </form>
        </div>
    </header>

    <main class="min-h-[calc(100vh-80px)]">
        @yield('content')
    </main>
</body>
</html>