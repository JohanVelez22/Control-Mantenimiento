<!DOCTYPE html>
<html lang="es" class="preload">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta — WolfCore System</title>
    
    <!-- Favicon (Isotipo e Insignia WolfCore System con invalidación de caché) -->
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48x48.png') }}?v={{ filemtime(public_path('favicon-48x48.png')) }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ filemtime(public_path('favicon-32x32.png')) }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ filemtime(public_path('favicon-16x16.png')) }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ filemtime(public_path('favicon.svg')) }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ filemtime(public_path('apple-touch-icon.png')) }}">
    
    {{-- Vite (Tailwind CSS compilado + JS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- CSS Librerías --}}
    <link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" integrity="sha384-RkASv+6KfBMW9eknReJIJ6b3UnjKOKC5bOUaNgIY778NFbQ8MtWq9Lr/khUgqtTt">
    <link rel="stylesheet" href="{{ asset('vendor/tom-select/tom-select.css') }}" integrity="sha384-dC18+guBm9Lk5IBQfkKIOw2YSxXWMsiuPfIOZNPilutZQn6MgBvcx+vejNQYlrbc">
    
    {{-- Scripts Librerías --}}
    <script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}" integrity="sha384-5JqMv4L/Xa0hfvtF06qboNdhvuYXUku9ZrhZh3bSk8VXF0A/RuSLHpLsSV9Zqhl6"></script>
    <script src="{{ asset('vendor/flatpickr/l10n/es.js') }}" integrity="sha384-j/aEP2b+3OKmGqank2qCSosSrlrF9jpIpdgApXq2ryJYBpLSbEi63/PDdL+rKmcQ"></script>
    <script src="{{ asset('vendor/tom-select/tom-select.complete.min.js') }}" integrity="sha384-cnROoUgVILyibe3J0zhzWoJ9p2WmdnK7j/BOTSWqVDbC1pVw2d+i6Q/1ESKJKCYf"></script>
    
    {{-- CSS Propio (Vite) --}}
    @vite('resources/css/glass/glass.css')
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
    {{-- Header mínimo con logo WolfCore System y cerrar sesión --}}
    <header id="ts-topbar" class="sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            @php
                $isElecHeader = request()->routeIs('consulta.electronicas') || request()->routeIs('electronicas.*');
                $headerHome = $isElecHeader ? route('consulta.electronicas') : route('consulta.mantenimientos');
            @endphp
            <a href="{{ $headerHome }}" class="flex items-center gap-2.5 min-w-0 hover:scale-105 transition-transform duration-200">
                <img src="{{ asset('images/wolfcore.png') }}" alt="WolfCore System" class="w-7 h-7 object-contain">
                <span class="text-[#2563EB] dark:text-[#3B82F6] font-logo font-black text-base sm:text-xl">WOLFCORE</span>
                <span class="hidden sm:inline text-slate-800 dark:text-white font-logo font-black text-xl">SYSTEM</span>
                <span class="hidden sm:inline ml-2 px-2 py-0.5 text-[10px] font-bold {{ $isElecHeader ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' }} rounded">CONSULTA</span>
            </a>
            <div class="flex items-center gap-2.5">
                <button id="theme-toggle-consulta" type="button" class="theme-toggle-btn group" title="Cambiar tema" aria-label="Cambiar tema" onclick="const h=document.documentElement;const s=()=>{h.classList.add('preload');h.classList.toggle('dark');localStorage.setItem('color-theme',h.classList.contains('dark')?'dark':'light');void h.offsetHeight;};if(document.startViewTransition&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){document.startViewTransition(s).ready.then(()=>requestAnimationFrame(()=>h.classList.remove('preload'))).catch(()=>h.classList.remove('preload'));}else{h.classList.add('theme-transition');h.classList.toggle('dark');localStorage.setItem('color-theme',h.classList.contains('dark')?'dark':'light');setTimeout(()=>h.classList.remove('theme-transition'),280);}this.blur();">
                    <span class="dark:hidden inline-block transition-transform duration-200 group-hover:scale-110">☀️</span>
                    <span class="hidden dark:inline inline-block transition-transform duration-200 group-hover:scale-110">🌙</span>
                </button>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-all group text-lg shadow-sm" title="Cerrar Sesión" aria-label="Cerrar Sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100dvh-64px)] flex flex-col justify-center items-center p-4 py-6 md:py-10">
        @yield('content')
    </main>
</body>
</html>