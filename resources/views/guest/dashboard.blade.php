@extends('layouts.guest')

@section('title', 'Tecni Systemas')

@section('content')
<div class="min-h-screen relative overflow-hidden flex items-center justify-center p-4 sm:p-8">

    {{-- Botones superiores: Tema y Cerrar Sesión (estilo Topbar Web) --}}
    <div class="absolute top-5 right-5 z-50 flex items-center gap-2">
        <button id="theme-toggle-guest"
            class="theme-toggle-btn group"
            title="Cambiar tema" aria-label="Cambiar tema">
            <span class="dark:hidden inline-block transition-transform duration-200 group-hover:scale-110">☀️</span>
            <span class="hidden dark:inline inline-block transition-transform duration-200 group-hover:scale-110">🌙</span>
        </button>

        @if(Auth::check())
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="logout-toggle-btn group text-lg" title="Cerrar Sesión" aria-label="Cerrar Sesión">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
            </button>
        </form>
        @endif
    </div>

    <!-- Elementos decorativos de fondo -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-500/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500/20 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-2xl z-10 flex flex-col items-center pb-16">
        
        <!-- Logo TECNI SYSTEMAS (Fuera del recuadro) -->
        <div class="text-center mt-0 mb-8 login-brand-title">
            <div class="flex justify-center mb-3">
                <div class="text-[24px] font-black tracking-widest font-logo flex items-center gap-2">
                    <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
                    <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
                </div>
            </div>
            <div>
                <span style="font-size: 80px;" class="drop-shadow-[0_0_15px_rgba(255,255,255,0.8)] dark:drop-shadow-[0_0_15px_rgba(255,255,255,0.1)] text-slate-800 dark:text-white leading-none">💼</span>
            </div>
        </div>

        <!-- Tarjeta Principal (Liquid Glass) -->
        <div class="glass-card guest-card w-full relative">
            <!-- Encabezado -->
            <div class="text-center mb-6 sm:mb-8">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-2 tracking-tight">Consulta de Servicios</h1>
                @if($cliente)
                    <p class="text-slate-600 dark:text-slate-400 text-lg">Hola, <span class="text-blue-600 dark:text-blue-400 font-black">{{ $cliente->nombres }}</span>. Aquí tienes el estado actual de tus equipos.</p>
                @elseif(isset($searched))
                    @php
                        $isElectronicaSearch = ($tipo ?? '') === 'electronica' || ($electronicas->isNotEmpty() && $mantenimientos->isEmpty());
                    @endphp
                    <p class="text-slate-600 dark:text-slate-400 text-lg">Resultados para la orden <span class="{{ $isElectronicaSearch ? 'text-purple-600 dark:text-purple-400' : 'text-blue-600 dark:text-blue-400' }} font-black">{{ strtoupper($id_orden ?? '') }}</span> (Doc: {{ $identificacion ?? '' }})</p>
                @else
                    <p class="text-slate-600 dark:text-slate-400 text-lg">Ingresa tu número de identificación y número de orden para hacer el seguimiento de tu equipo.</p>
                @endif
            </div>
            
            @if($cliente || isset($searched))
                @if($mantenimientos->isEmpty() && $electronicas->isEmpty())
                    <div class="text-center py-12 bg-white/10 dark:bg-slate-900/20 rounded-2xl border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                        @if(isset($searched))
                            <p class="text-slate-600 dark:text-slate-400 font-medium">No se encontró ningun resultado relacionado con tu búsqueda, revisa si ya fue entregado.</p>
                        @else
                            <p class="text-slate-600 dark:text-slate-400 font-medium">No tienes órdenes activas en este momento.</p>
                        @endif
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($mantenimientos as $m)
                            <div class="group relative overflow-hidden bg-white/20 dark:bg-slate-900/35 hover:bg-white/35 dark:hover:bg-slate-900/50 backdrop-blur-md transition-all duration-300 border border-white/40 dark:border-white/5 rounded-2xl p-6 shadow-sm">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-2.5 py-1 rounded-md bg-blue-50 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-wider">Mantenimiento</span>
                                            <span class="text-blue-600 dark:text-blue-400 font-bold text-sm">{{ $m->id_orden }}</span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ $m->equipo->nombre ?? 'Equipo sin registro' }}</h3>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="inline-flex items-center px-4 py-2 rounded-xl border
                                            {{ $m->estado === 'terminado' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400' }}">
                                            <span class="w-2 h-2 rounded-full mr-2 {{ $m->estado === 'terminado' ? 'bg-emerald-500 dark:bg-emerald-400 shadow-[0_0_8px_#34d399]' : 'bg-amber-500 dark:bg-amber-400 shadow-[0_0_8px_#fbbf24]' }}"></span>
                                            <span class="font-bold text-sm uppercase tracking-wide">{{ in_array($m->estado, ['terminado', 'entregado']) ? '✅' : '⏳' }} {{ $m->estado }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-row justify-between items-center">
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Ingreso: <span class="font-bold text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</span></p>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Salida: <span class="font-bold {{ $m->fecha_salida ? 'text-slate-700 dark:text-slate-300' : 'text-amber-600 dark:text-amber-400' }}">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</span></p>
                                </div>
                                
                                <!-- Detalles Extendidos (Ancho Completo) -->
                                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300 bg-white/10 dark:bg-slate-900/25 p-4 rounded-xl border border-white/30 dark:border-white/5 backdrop-blur-sm">
                                    <div><span class="font-bold text-slate-700 dark:text-slate-200">Marca:</span> {{ $m->equipo->marca ?? 'N/D' }}</div>
                                    <div class="sm:text-right"><span class="font-bold text-slate-700 dark:text-slate-200">Serial:</span> {{ $m->equipo->serie ?? 'N/D' }}</div>
                                    <div class="sm:col-span-2"><span class="font-bold text-slate-700 dark:text-slate-200">Descripción:</span> {{ $m->descripcion ?? 'Sin detalles' }}</div>
                                    
                                    @php
                                        $totalRepuestos = $m->stocks ? $m->stocks->sum(fn($s) => $s->pivot->cantidad * $s->pivot->precio_unitario) : 0;
                                        $valorServicio = max(0, $m->costo - $totalRepuestos);
                                    @endphp

                                    @if($m->stocks && $m->stocks->isNotEmpty())
                                    <div class="sm:col-span-2 mt-2 pt-3 border-t border-gray-200/60 dark:border-white/10">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 block mb-2">🛍️ Repuestos / Insumos y Mano de Obra:</span>
                                        <div class="space-y-2">
                                            @foreach($m->stocks as $repuesto)
                                                <div class="flex items-center justify-between text-xs sm:text-sm bg-white/20 dark:bg-slate-900/30 px-3 py-2 rounded-lg border border-white/40 dark:border-white/5">
                                                    <span class="text-slate-700 dark:text-slate-300 font-medium flex items-center gap-1.5">
                                                        <span>📦</span> {{ $repuesto->producto }} <span class="text-slate-500 text-xs">({{ $repuesto->pivot->cantidad }}x ${{ number_format($repuesto->pivot->precio_unitario, 0, ',', '.') }})</span>
                                                    </span>
                                                    <span class="font-bold text-slate-800 dark:text-slate-100">${{ number_format($repuesto->pivot->cantidad * $repuesto->pivot->precio_unitario, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach

                                            <div class="flex items-center justify-between text-xs sm:text-sm bg-white/20 dark:bg-slate-900/30 px-3 py-2 rounded-lg border border-white/40 dark:border-white/5">
                                                <span class="text-slate-700 dark:text-slate-300 font-medium flex items-center gap-1.5">
                                                    <span>🛠️</span> Servicio / Mano de Obra:
                                                </span>
                                                <span class="font-bold text-slate-800 dark:text-slate-100">${{ number_format($valorServicio, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="sm:col-span-2 mt-2 pt-3 border-t border-gray-200/60 dark:border-white/10 flex justify-between items-center">
                                        <span class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">Costo Total:</span>
                                        <span class="text-lg font-black text-blue-600 dark:text-blue-400">${{ number_format($m->costo, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @foreach($electronicas as $e)
                            <div class="group relative overflow-hidden bg-white/20 dark:bg-slate-900/35 hover:bg-white/35 dark:hover:bg-slate-900/50 backdrop-blur-md transition-all duration-300 border border-white/40 dark:border-white/5 rounded-2xl p-6 shadow-sm">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500"></div>
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="px-2.5 py-1 rounded-md bg-purple-50 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 text-xs font-bold uppercase tracking-wider">Electrónica</span>
                                            <span class="text-purple-600 dark:text-purple-400 font-bold text-sm">{{ $e->id_orden }}</span>
                                        </div>
                                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">{{ $e->equipo->nombre ?? 'Equipo sin registro' }}</h3>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="inline-flex items-center px-4 py-2 rounded-xl border
                                            {{ $e->estado === 'terminado' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400' : 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-600 dark:text-amber-400' }}">
                                            <span class="w-2 h-2 rounded-full mr-2 {{ $e->estado === 'terminado' ? 'bg-emerald-500 dark:bg-emerald-400 shadow-[0_0_8px_#34d399]' : 'bg-amber-500 dark:bg-amber-400 shadow-[0_0_8px_#fbbf24]' }}"></span>
                                            <span class="font-bold text-sm uppercase tracking-wide">{{ $e->estado === 'terminado' ? '✅' : '⏳' }} {{ $e->estado }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-row justify-between items-center">
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Ingreso: <span class="font-bold text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y') }}</span></p>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">Salida: <span class="font-bold {{ $e->fecha_salida ? 'text-slate-700 dark:text-slate-300' : 'text-amber-600 dark:text-amber-400' }}">{{ $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</span></p>
                                </div>
                                
                                <!-- Detalles Extendidos (Ancho Completo) -->
                                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300 bg-white/10 dark:bg-slate-900/25 p-4 rounded-xl border border-white/30 dark:border-white/5 backdrop-blur-sm">
                                    <div><span class="font-bold text-slate-700 dark:text-slate-200">Marca:</span> {{ $e->equipo->marca ?? 'N/D' }}</div>
                                    <div class="sm:text-right"><span class="font-bold text-slate-700 dark:text-slate-200">Serial:</span> {{ $e->equipo->serie ?? 'N/D' }}</div>
                                    <div class="sm:col-span-2"><span class="font-bold text-slate-700 dark:text-slate-200">Descripción:</span> {{ $e->descripcion_problema ?? 'Sin detalles' }}</div>
                                    
                                    @php
                                        $totalRepuestosE = $e->stocks ? $e->stocks->sum(fn($s) => $s->pivot->cantidad * $s->pivot->precio_unitario) : 0;
                                        $valorServicioE = max(0, $e->costo - $totalRepuestosE);
                                    @endphp

                                    @if($e->stocks && $e->stocks->isNotEmpty())
                                    <div class="sm:col-span-2 mt-2 pt-3 border-t border-gray-200/60 dark:border-white/10">
                                        <span class="font-bold text-slate-700 dark:text-slate-200 block mb-2">🛍️ Repuestos / Insumos y Mano de Obra:</span>
                                        <div class="space-y-2">
                                            @foreach($e->stocks as $repuesto)
                                                <div class="flex items-center justify-between text-xs sm:text-sm bg-white/20 dark:bg-slate-900/30 px-3 py-2 rounded-lg border border-white/40 dark:border-white/5">
                                                    <span class="text-slate-700 dark:text-slate-300 font-medium flex items-center gap-1.5">
                                                        <span>📦</span> {{ $repuesto->producto }} <span class="text-slate-500 text-xs">({{ $repuesto->pivot->cantidad }}x ${{ number_format($repuesto->pivot->precio_unitario, 0, ',', '.') }})</span>
                                                    </span>
                                                    <span class="font-bold text-slate-800 dark:text-slate-100">${{ number_format($repuesto->pivot->cantidad * $repuesto->pivot->precio_unitario, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach

                                            <div class="flex items-center justify-between text-xs sm:text-sm bg-white/20 dark:bg-slate-900/30 px-3 py-2 rounded-lg border border-white/40 dark:border-white/5">
                                                <span class="text-slate-700 dark:text-slate-300 font-medium flex items-center gap-1.5">
                                                    <span>🛠️</span> Servicio / Mano de Obra:
                                                </span>
                                                <span class="font-bold text-slate-800 dark:text-slate-100">${{ number_format($valorServicioE, 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <div class="sm:col-span-2 mt-2 pt-3 border-t border-gray-200/60 dark:border-white/10 flex justify-between items-center">
                                        <span class="text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">Costo Total:</span>
                                        <span class="text-lg font-black text-purple-600 dark:text-purple-400">${{ number_format($e->costo, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                @if(isset($searched) && !$cliente)
                <div class="mt-6 sm:mt-8 text-center">
                    <a href="{{ route('guest.dashboard') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-gradient-to-r from-blue-500 to-cyan-400 hover:from-blue-600 hover:to-cyan-500 text-white font-bold rounded-xl shadow-lg shadow-blue-500/25 transition-all transform hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        <span>🔍</span> Buscar Otra Consulta
                    </a>
                </div>
                @endif
            @else
                <!-- Formulario de Búsqueda si no hay cliente asociado -->
                @php
                    $isElec = ($tipo ?? '') === 'electronica';
                @endphp
                <form id="guestSearchForm" method="GET" action="{{ route('guest.search') }}" class="max-w-xl mx-auto space-y-4">
                    <div class="flex bg-white/40 dark:bg-slate-900/40 p-1.5 rounded-xl mb-6 border border-white/50 dark:border-white/10 shadow-sm backdrop-blur-md">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo" id="tipo-mantenimiento" value="mantenimiento" class="guest-type-radio sr-only" {{ !$isElec ? 'checked' : '' }} onchange="updateGuestTheme('mantenimiento')">
                            <div id="tab-label-mantenimiento" class="text-center py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 {{ !$isElec ? 'guest-tab-active-mant' : 'guest-tab-inactive' }}">
                                <span>🛠️</span> Mantenimientos
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo" id="tipo-electronica" value="electronica" class="guest-type-radio sr-only" {{ $isElec ? 'checked' : '' }} onchange="updateGuestTheme('electronica')">
                            <div id="tab-label-electronica" class="text-center py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 {{ $isElec ? 'guest-tab-active-elec' : 'guest-tab-inactive' }}">
                                <span>⚡</span> Electrónica
                            </div>
                        </label>
                    </div>

                    {{-- Campo Cédula / NIT --}}
                    <div>
                        <label for="guestIdentificacionInput" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5 ml-1">
                            🪪 Cédula o NIT del Cliente <span class="text-red-500 font-bold">*</span>
                        </label>
                        <input type="text" name="identificacion" id="guestIdentificacionInput" 
                               value="{{ old('identificacion', $identificacion ?? '') }}" 
                               class="glass-input w-full px-4 py-3.5 text-base sm:text-lg font-semibold focus:ring-2 focus:ring-blue-500 @error('identificacion') border-red-500 @enderror" 
                               placeholder="Ej: 123456789 o 900123456" required minlength="3" maxlength="30" autocomplete="off"
                               oninvalid="this.setCustomValidity('Debes digitar cédula / NIT')"
                               oninput="this.setCustomValidity('')">
                        <p id="error-identificacion" class="text-xs text-red-500 font-semibold mt-1.5 ml-1 @error('identificacion') @else hidden @enderror">
                            @error('identificacion') {{ $message }} @else Debes digitar cédula / NIT @enderror
                        </p>
                    </div>

                    {{-- Campo Número de Orden --}}
                    <div>
                        <label for="guestOrdenInput" class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5 ml-1">
                            📋 Número de Orden <span class="text-red-500 font-bold">*</span>
                        </label>
                        <input type="text" name="id_orden" id="guestOrdenInput" 
                               value="{{ old('id_orden', $id_orden ?? '') }}" 
                               class="glass-input w-full px-4 py-3.5 text-base sm:text-lg font-semibold focus:ring-2 focus:ring-blue-500 @error('id_orden') border-red-500 @enderror" 
                               placeholder="{{ $isElec ? 'Ej: ELC-001 o 1' : 'Ej: ORD-001 o 1' }}" required minlength="1" maxlength="30" autocomplete="off"
                               oninvalid="this.setCustomValidity('Debes digitar la orden')"
                               oninput="this.setCustomValidity('')">
                        <p id="error-id_orden" class="text-xs text-red-500 font-semibold mt-1.5 ml-1 @error('id_orden') @else hidden @enderror">
                            @error('id_orden') {{ $message }} @else Debes digitar la orden @enderror
                        </p>
                    </div>

                    <button type="submit" id="guestSubmitBtn" class="w-full mt-6 {{ $isElec ? 'guest-btn-elec' : 'guest-btn-mant' }} font-bold py-4 rounded-xl shadow-lg transition-all transform hover:scale-[1.01] active:scale-[0.99] cursor-pointer text-base sm:text-lg flex items-center justify-center gap-2">
                        <span id="guestSubmitBtnText">{{ $isElec ? '⚡ Consultar Estado de la Orden' : '🔍 Consultar Estado de la Orden' }}</span>
                    </button>
                </form>
            @endif
            

        </div>
    </div>
</div>

<style>
.guest-card {
  padding: 1.5rem !important;
  border-radius: 20px !important;
}

@media (min-width: 640px) {
  .guest-card {
    padding: 2rem !important;
    border-radius: 24px !important;
  }
}

@media (max-width: 639px) {
  .login-brand-title {
    margin-top: 3.5rem !important;
  }
}

/* Ocultar placeholder al hacer focus */
.glass-input:focus::placeholder,
.glass-input:focus::-webkit-input-placeholder,
.glass-input:focus::-moz-placeholder,
.glass-input:focus:-ms-input-placeholder {
  color: transparent !important;
  opacity: 0 !important;
}

/* Estilos de pestañas (Mantenimientos vs Electrónica) */
.guest-tab-inactive {
  color: #64748b;
  background: transparent;
}
html.dark .guest-tab-inactive {
  color: #94a3b8;
}
.guest-tab-inactive:hover {
  color: #0f172a;
  background: rgba(255, 255, 255, 0.25);
}
html.dark .guest-tab-inactive:hover {
  color: #f8fafc;
  background: rgba(255, 255, 255, 0.05);
}

.guest-tab-active-mant {
  background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%) !important;
  color: #ffffff !important;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
}

.guest-tab-active-elec {
  background: linear-gradient(135deg, #7C3AED 0%, #A855F7 100%) !important;
  color: #ffffff !important;
  box-shadow: 0 4px 14px rgba(124, 58, 237, 0.40) !important;
}

/* Botón Mantenimientos (Azul / Cyan) */
.guest-btn-mant {
  background: linear-gradient(135deg, #2563EB 0%, #06B6D4 100%) !important;
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35) !important;
  color: #ffffff !important;
}
.guest-btn-mant:hover {
  background: linear-gradient(135deg, #1D4ED8 0%, #0891B2 100%) !important;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.50) !important;
}

/* Botón Electrónica (Violeta / Púrpura) */
.guest-btn-elec {
  background: linear-gradient(135deg, #7C3AED 0%, #A855F7 100%) !important;
  box-shadow: 0 6px 20px rgba(124, 58, 237, 0.40) !important;
  color: #ffffff !important;
}
.guest-btn-elec:hover {
  background: linear-gradient(135deg, #6D28D9 0%, #9333EA 100%) !important;
  box-shadow: 0 8px 24px rgba(124, 58, 237, 0.55) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alternador de tema claro/oscuro
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
            this.blur();
        });
    }

    // Ocultar placeholder al hacer clic / focus
    document.querySelectorAll('.glass-input').forEach(function(input) {
        var originalPlaceholder = input.getAttribute('placeholder') || '';
        input.addEventListener('focus', function() {
            this.dataset.tempPlaceholder = this.getAttribute('placeholder') || '';
            this.setAttribute('placeholder', '');
        });
        input.addEventListener('blur', function() {
            this.setAttribute('placeholder', this.dataset.tempPlaceholder || originalPlaceholder);
        });
    });

    // Validación interactiva con avisos en rojo debajo de cada campo
    const form = document.getElementById('guestSearchForm');
    const idInput = document.getElementById('guestIdentificacionInput');
    const ordenInput = document.getElementById('guestOrdenInput');
    const errId = document.getElementById('error-identificacion');
    const errOrden = document.getElementById('error-id_orden');

    if (idInput) {
        idInput.addEventListener('input', function() {
            this.setCustomValidity('');
            if (this.value.trim().length > 0) {
                this.classList.remove('border-red-500');
                if (errId) errId.classList.add('hidden');
            }
        });
    }

    if (ordenInput) {
        ordenInput.addEventListener('input', function() {
            this.setCustomValidity('');
            if (this.value.trim().length > 0) {
                this.classList.remove('border-red-500');
                if (errOrden) errOrden.classList.add('hidden');
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            const idVal = idInput ? idInput.value.trim() : '';
            const ordenVal = ordenInput ? ordenInput.value.trim() : '';

            if (!idVal) {
                hasError = true;
                if (idInput) {
                    idInput.classList.add('border-red-500');
                    idInput.setCustomValidity('Debes digitar cédula / NIT');
                }
                if (errId) {
                    errId.textContent = 'Debes digitar cédula / NIT';
                    errId.classList.remove('hidden');
                }
            } else {
                if (idInput) {
                    idInput.classList.remove('border-red-500');
                    idInput.setCustomValidity('');
                }
                if (errId) errId.classList.add('hidden');
            }

            if (!ordenVal) {
                hasError = true;
                if (ordenInput) {
                    ordenInput.classList.add('border-red-500');
                    ordenInput.setCustomValidity('Debes digitar la orden');
                }
                if (errOrden) {
                    errOrden.textContent = 'Debes digitar la orden';
                    errOrden.classList.remove('hidden');
                }
            } else {
                if (ordenInput) {
                    ordenInput.classList.remove('border-red-500');
                    ordenInput.setCustomValidity('');
                }
                if (errOrden) errOrden.classList.add('hidden');
            }

            if (hasError) {
                e.preventDefault();
                if (!idVal && idInput) {
                    idInput.reportValidity();
                    idInput.focus();
                } else if (!ordenVal && ordenInput) {
                    ordenInput.reportValidity();
                    ordenInput.focus();
                }
            }
        });
    }
});

function updateGuestTheme(tipo) {
    const ordenInput = document.getElementById('guestOrdenInput');
    const submitBtn = document.getElementById('guestSubmitBtn');
    const submitText = document.getElementById('guestSubmitBtnText');
    const tabMant = document.getElementById('tab-label-mantenimiento');
    const tabElec = document.getElementById('tab-label-electronica');

    if (tipo === 'electronica') {
        const ph = 'Ej: ELC-001 o 1';
        if (ordenInput) {
            ordenInput.dataset.tempPlaceholder = ph;
            ordenInput.placeholder = ph;
        }
        if (tabMant) {
            tabMant.className = 'text-center py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 guest-tab-inactive';
        }
        if (tabElec) {
            tabElec.className = 'text-center py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 guest-tab-active-elec';
        }
        if (submitBtn) {
            submitBtn.className = 'w-full mt-6 guest-btn-elec font-bold py-4 rounded-xl shadow-lg transition-all transform hover:scale-[1.01] active:scale-[0.99] cursor-pointer text-base sm:text-lg flex items-center justify-center gap-2';
        }
        if (submitText) {
            submitText.textContent = '⚡ Consultar Estado de la Orden';
        }
    } else {
        const ph = 'Ej: ORD-001 o 1';
        if (ordenInput) {
            ordenInput.dataset.tempPlaceholder = ph;
            ordenInput.placeholder = ph;
        }
        if (tabElec) {
            tabElec.className = 'text-center py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 guest-tab-inactive';
        }
        if (tabMant) {
            tabMant.className = 'text-center py-2.5 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2 guest-tab-active-mant';
        }
        if (submitBtn) {
            submitBtn.className = 'w-full mt-6 guest-btn-mant font-bold py-4 rounded-xl shadow-lg transition-all transform hover:scale-[1.01] active:scale-[0.99] cursor-pointer text-base sm:text-lg flex items-center justify-center gap-2';
        }
        if (submitText) {
            submitText.textContent = '🔍 Consultar Estado de la Orden';
        }
    }
}
</script>
@endsection
