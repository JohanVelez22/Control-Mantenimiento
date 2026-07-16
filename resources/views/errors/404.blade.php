@extends('layouts.app')

@section('title', 'Página no encontrada - 404')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center p-6">
    <div class="glass-panel p-12 text-center max-w-lg w-full relative overflow-hidden">
        <!-- Decoración abstracta -->
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-purple-500/20 rounded-full blur-3xl"></div>
        
        <h1 class="text-8xl font-black text-transparent bg-clip-text bg-gradient-to-br from-blue-600 to-purple-600 mb-4 drop-shadow-sm relative z-10">404</h1>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-4 relative z-10">Página no encontrada</h2>
        <p class="text-slate-600 dark:text-slate-400 mb-8 relative z-10">Lo sentimos, la página que estás buscando no existe, ha sido movida o está temporalmente fuera de servicio.</p>
        
        <a href="{{ route('dashboard') }}" class="btn-primary inline-flex items-center gap-2 relative z-10 shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 transition-all hover:-translate-y-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Volver al Inicio
        </a>
    </div>
</div>
@endsection
