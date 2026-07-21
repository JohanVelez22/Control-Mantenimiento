@extends('layouts.guest')

@section('title', 'Tecni Systemas')

@section('content')
<div class="min-h-screen relative flex items-center justify-center p-4 sm:p-8">

    {{-- Botón modo oscuro --}}
    <div class="absolute top-5 right-5 z-50">
        <button id="theme-toggle-guest"
            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg"
            title="Cambiar tema" aria-label="Cambiar tema">
            <span class="dark:hidden">☀️</span>
            <span class="hidden dark:inline">🌙</span>
        </button>
    </div>

    <!-- Elementos decorativos de fondo -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-500/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500/20 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-2xl z-10 flex flex-col items-center pb-16">
        
        <!-- Logo TECNI SYSTEMAS (Fuera del recuadro) -->
        <div class="text-center mt-0 mb-8">
            <div class="flex justify-center mb-3">
                <div class="text-[32px] font-black tracking-widest font-logo flex items-center gap-2">
                    <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
                    <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
                </div>
            </div>
            <div>
                <span style="font-size: 100px;" class="drop-shadow-[0_0_15px_rgba(255,255,255,0.8)] dark:drop-shadow-[0_0_15px_rgba(255,255,255,0.1)] text-slate-800 dark:text-white leading-none">💼</span>
            </div>
        </div>

        <!-- Tarjeta Principal (Liquid Glass) -->
        <div class="glass-card w-full p-6 sm:p-10 relative">
            <!-- Encabezado -->
            <div class="text-center mb-5">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-2 tracking-tight">Consulta de Servicios</h1>
                @if($cliente)
                    <p class="text-slate-600 dark:text-slate-400 text-lg">Hola, <span class="text-blue-600 dark:text-blue-400 font-black">{{ $cliente->nombres }}</span>. Aquí tienes el estado actual de tus equipos.</p>
                @elseif(isset($searched))
                    <p class="text-slate-600 dark:text-slate-400 text-lg">Resultados de la orden o identificación: <span class="text-blue-600 dark:text-blue-400 font-black">{{ strtoupper($query) }}</span></p>
                @else
                    <p class="text-slate-600 dark:text-slate-400 text-lg">Hola. Ingresa tu número de orden o identificación para hacer el seguimiento de tu equipo.</p>
                @endif
            </div>
            
            @if($cliente || isset($searched))
                @if($mantenimientos->isEmpty() && $electronicas->isEmpty())
                    <div class="text-center py-12 bg-gray-50/50 dark:bg-slate-800/30 rounded-2xl border border-gray-200 dark:border-slate-700/50">
                        @if(isset($searched))
                            <p class="text-slate-600 dark:text-slate-400 font-medium">No se encontró ningun resultado relacionado con tu búsqueda, revisa si ya fue entregado.</p>
                        @else
                            <p class="text-slate-600 dark:text-slate-400 font-medium">No tienes órdenes activas en este momento.</p>
                        @endif
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($mantenimientos as $m)
                            <div class="group relative overflow-hidden bg-white/40 dark:bg-slate-800/40 hover:bg-white/60 dark:hover:bg-slate-800/60 transition-all duration-300 border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-2.5 py-1 rounded-md bg-blue-50 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-wider">Mantenimiento</span>
                                            <span class="text-slate-500 dark:text-slate-400 font-medium text-sm">{{ $m->id_orden }}</span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ $m->equipo->nombre ?? 'Equipo sin registro' }}</h3>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="inline-flex items-center px-4 py-2 rounded-full border
                                            {{ $m->estado === 'terminado' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400' }}">
                                            <span class="w-2 h-2 rounded-full mr-2 {{ $m->estado === 'terminado' ? 'bg-emerald-500 dark:bg-emerald-400 shadow-[0_0_8px_#34d399]' : 'bg-amber-500 dark:bg-amber-400 shadow-[0_0_8px_#fbbf24]' }}"></span>
                                            <span class="font-bold text-sm uppercase tracking-wide">{{ $m->estado }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-row justify-between items-center">
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Ingreso: <span class="font-bold text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</span></p>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Salida: <span class="font-bold {{ $m->fecha_salida ? 'text-slate-700 dark:text-slate-300' : 'text-amber-600 dark:text-amber-400' }}">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</span></p>
                                </div>
                                
                                <!-- Detalles Extendidos (Ancho Completo) -->
                                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-900/40 p-4 rounded-xl border border-gray-100 dark:border-slate-700/50">
                                    <div><span class="font-bold text-slate-700 dark:text-slate-200">Marca:</span> {{ $m->equipo->marca ?? 'N/D' }}</div>
                                    <div><span class="font-bold text-slate-700 dark:text-slate-200">Serial:</span> {{ $m->equipo->serie ?? 'N/D' }}</div>
                                    <div class="sm:col-span-2"><span class="font-bold text-slate-700 dark:text-slate-200">Descripción:</span> {{ $m->descripcion ?? 'Sin detalles' }}</div>
                                    <div class="sm:col-span-2 mt-2 pt-3 border-t border-gray-200 dark:border-slate-700">
                                        <span class="text-lg font-black text-blue-600 dark:text-blue-400">Total: ${{ number_format($m->costo, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @foreach($electronicas as $e)
                            <div class="group relative overflow-hidden bg-white/40 dark:bg-slate-800/40 hover:bg-white/60 dark:hover:bg-slate-800/60 transition-all duration-300 border border-gray-200 dark:border-slate-700/50 rounded-2xl p-6">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500"></div>
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-2.5 py-1 rounded-md bg-purple-50 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 text-xs font-bold uppercase tracking-wider">Electrónica</span>
                                            <span class="text-slate-500 dark:text-slate-400 font-medium text-sm">{{ $e->id_orden }}</span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ $e->equipo->nombre ?? 'Equipo sin registro' }}</h3>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="inline-flex items-center px-4 py-2 rounded-full border
                                            {{ $e->estado === 'terminado' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400' }}">
                                            <span class="w-2 h-2 rounded-full mr-2 {{ $e->estado === 'terminado' ? 'bg-emerald-500 dark:bg-emerald-400 shadow-[0_0_8px_#34d399]' : 'bg-amber-500 dark:bg-amber-400 shadow-[0_0_8px_#fbbf24]' }}"></span>
                                            <span class="font-bold text-sm uppercase tracking-wide">{{ $e->estado }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-row justify-between items-center">
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Ingreso: <span class="font-bold text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y') }}</span></p>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Salida: <span class="font-bold {{ $e->fecha_salida ? 'text-slate-700 dark:text-slate-300' : 'text-amber-600 dark:text-amber-400' }}">{{ $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</span></p>
                                </div>
                                
                                <!-- Detalles Extendidos (Ancho Completo) -->
                                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-900/40 p-4 rounded-xl border border-gray-100 dark:border-slate-700/50">
                                    <div><span class="font-bold text-slate-700 dark:text-slate-200">Marca:</span> {{ $e->equipo->marca ?? 'N/D' }}</div>
                                    <div><span class="font-bold text-slate-700 dark:text-slate-200">Serial:</span> {{ $e->equipo->serie ?? 'N/D' }}</div>
                                    <div class="sm:col-span-2"><span class="font-bold text-slate-700 dark:text-slate-200">Descripción:</span> {{ $e->descripcion_problema ?? 'Sin detalles' }}</div>
                                    <div class="sm:col-span-2 mt-2 pt-3 border-t border-gray-200 dark:border-slate-700">
                                        <span class="text-lg font-black text-purple-600 dark:text-purple-400">Total: ${{ number_format($e->costo, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                @if(isset($searched) && !$cliente)
                <div class="mt-8 text-center">
                    <a href="{{ route('guest.dashboard') }}" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-400 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1 inline-flex items-center gap-2">
                        <span>🔍</span> Buscar Otra Orden
                    </a>
                </div>
                @endif
            @else
                <!-- Formulario de Búsqueda si no hay cliente asociado -->
                <form method="GET" action="{{ route('guest.search') }}" class="max-w-xl mx-auto">
                    <div class="flex bg-white dark:bg-slate-800/50 p-1 rounded-xl mb-6 border border-gray-200 dark:border-slate-700/50 shadow-sm">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo" value="mantenimiento" class="peer sr-only" checked>
                            <div class="text-center py-2.5 rounded-lg text-sm font-bold text-slate-500 dark:text-slate-400 peer-checked:bg-blue-500 peer-checked:text-white transition-all peer-checked:shadow-md">
                                Mantenimientos
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo" value="electronica" class="peer sr-only">
                            <div class="text-center py-2.5 rounded-lg text-sm font-bold text-slate-500 dark:text-slate-400 peer-checked:bg-purple-500 peer-checked:text-white transition-all peer-checked:shadow-md">
                                Electrónica
                            </div>
                        </label>
                    </div>

                    <div class="relative mt-2">
                        <input type="text" name="query" class="glass-input w-full pl-12 pr-4 py-4 text-lg" placeholder="Ej: ORD-001 o 123456789" required>
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>

                    <button type="submit" class="w-full mt-6 bg-gradient-to-r from-blue-500 to-cyan-400 hover:from-blue-600 hover:to-cyan-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/25 transition-all transform hover:scale-[1.02]">
                        Consultar Estado
                    </button>
                </form>
            @endif
            
            <!-- Botón de salida -->
            @if(Auth::check())
            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors text-sm font-medium inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 013-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const themeBtn = document.getElementById('theme-toggle-guest');
    if(themeBtn) {
        themeBtn.addEventListener('click', function() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });
    }
});
</script>
@endsection
