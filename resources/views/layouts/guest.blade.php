<!DOCTYPE html>
<html lang="es" class="preload dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal de Seguimiento')</title>

    {{-- Fuentes y Tailwind --}}
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Orbitron:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}?v={{ time() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Aplicar modo oscuro si está guardado en localStorage o si prefiere oscuro
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Evitar Flash of Unstyled Content (FOUC)
        document.addEventListener('DOMContentLoaded', () => {
            document.documentElement.classList.remove('preload');
        });
    </script>
</head>
<body class="ts-bg text-slate-800 dark:text-slate-100 font-sans antialiased overflow-x-hidden min-h-screen transition-colors duration-500">
    @yield('content')
</body>
</html>
