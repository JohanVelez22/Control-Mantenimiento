@extends('layouts.app')

@section('content')
<style>
@media print {
 .no-print, nav, aside, header, footer, form, button { display: none !important; }
 a:not(.no-print-link) { display: none !important; }
 .no-print-link { color: black !important; text-decoration: none !important; cursor: default !important; }
 body { background: white !important; color: black !important; margin: 1cm !important; padding: 0 !important; }
 .shadow, .rounded-lg { box-shadow: none !important; border: none !important; }
 table { width: 100% !important; border: 1px solid #000 !important; font-size: 8pt !important; border-collapse: collapse !important; }
 th, td { border: 1px solid #000 !important; padding: 4px !important; }
 h2 { text-align: center !important; font-size: 18pt !important; margin-bottom: 20px !important; }
}
</style>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
 <div class="glass-card p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">💻</span> Equipos</div>
 <div class="text-3xl font-black text-slate-800 dark:text-white z-10">{{ $totalEquipos ?? 0 }}</div>
 </div>
 
 <div class="glass-card p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">🔧</span> Órdenes</div>
 <div class="text-3xl font-black text-slate-800 dark:text-white z-10">{{ $totalMantenimientos ?? 0 }}</div>
 </div>
 
 <div class="glass-card p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-amber-500/20 rounded-full blur-2xl group-hover:bg-amber-500/30 transition-all"></div>
 <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">⏳</span> Mantenimientos Pend.</div>
 <div class="text-3xl font-black text-slate-800 dark:text-white z-10">{{ $stats['pendientes'] ?? 0 }}</div>
 </div>
 
 <div class="glass-card p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
 <div class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">⚡</span> Electrónica Pend.</div>
 <div class="text-3xl font-black text-slate-800 dark:text-white z-10">{{ $stats['electronica_pendientes'] ?? 0 }}</div>
 </div>

 <div class="glass-card p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
 <div class="text-[11px] font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">📦</span> Stock Bajo (<5)</div>
 <div class="text-3xl font-black text-slate-800 dark:text-white z-10">{{ $stats['stock_bajo'] ?? 0 }}</div>
 </div>
</div>

<!-- Carrusel de Gráficos -->
<h3 class="text-lg font-bold mb-4 text-gray-700 dark:text-gray-300 flex items-center gap-2">
 <span class="text-xl leading-none shrink-0" aria-hidden="true">📆</span>
 Análisis Visual de Rendimiento
</h3>
<div class="mb-6 glass-card relative overflow-hidden group " id="statsCarouselContainer">
 
 <!-- Indicadores -->
 <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-3 z-10 flex-wrap px-2" id="carouselIndicators">
 <button type="button" class="w-8 h-2.5 rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 shadow-md transition-all duration-500 backdrop-blur-sm"></button>
 <button type="button" class="w-2.5 h-2.5 rounded-full bg-gray-300/60 dark:bg-gray-600/60 transition-all duration-500 backdrop-blur-sm hover:bg-gray-400/80 dark:hover:bg-gray-500/80"></button>
 <button type="button" class="w-2.5 h-2.5 rounded-full bg-gray-300/60 dark:bg-gray-600/60 transition-all duration-500 backdrop-blur-sm hover:bg-gray-400/80 dark:hover:bg-gray-500/80"></button>
 <button type="button" class="w-2.5 h-2.5 rounded-full bg-gray-300/60 dark:bg-gray-600/60 transition-all duration-500 backdrop-blur-sm hover:bg-gray-400/80 dark:hover:bg-gray-500/80"></button>
 </div>

 <!-- Contenedor Deslizante -->
 <div class="flex" id="carouselTrack" style="width: 400%; transition: transform 0.7s cubic-bezier(0.25, 1, 0.5, 1) !important;">
 
 <!-- Slide 1: Gráfico de Barras (Tendencia 7 Días) -->
 <div class="w-1/4 p-6 flex flex-col bg-transparent" style="height: 420px;">
 <div class="flex justify-between items-center mb-6 px-4">
 <div>
 <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Crecimiento Semanal</h4>
 <p class="text-sm text-gray-500 dark:text-gray-400">Comparativa de ingresos de equipos vs órdenes creadas</p>
 </div>
 <span class="text-xs font-bold px-3 py-1.5 bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded-full shadow-sm">Últimos 7 Días</span>
 </div>
 <div class="w-full flex-grow relative px-2 pb-6">
 <canvas id="barChart"></canvas>
 </div>
 </div>

 <!-- Slide 2: Gráfico Circular (Distribución de Órdenes) -->
 <div class="w-1/4 p-6 flex flex-col bg-transparent" style="height: 420px;">
 <div class="flex justify-between items-center mb-2 px-4">
 <div>
 <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Distribución Global</h4>
 <p class="text-sm text-gray-500 dark:text-gray-400">Estado actual de todos los mantenimientos históricos</p>
 </div>
 <span class="text-xs font-bold px-3 py-1.5 bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 rounded-full shadow-sm">Tiempo Real</span>
 </div>
 <div class="w-full flex-grow relative flex justify-center items-center pb-8">
 <!-- Wrapper para forzar tamaño pequeño -->
 <div style="height: 250px; width: 250px; position: relative;">
 <canvas id="pieChart"></canvas>
 </div>
 </div>
 </div>

 <!-- Slide 3: Ingresos por día (últimos 7) -->
 <div class="w-1/4 p-6 flex flex-col bg-transparent" style="height: 420px;">
 <div class="flex justify-between items-center mb-6 px-4">
 <div>
 <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Ingresos por día</h4>
 <p class="text-sm text-gray-500 dark:text-gray-400">Órdenes terminadas: suma de costo por fecha de salida</p>
 </div>
 <span class="text-xs font-bold px-3 py-1.5 bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-200 rounded-full shadow-sm">Últimos 7 días</span>
 </div>
 <div class="w-full flex-grow relative px-2 pb-8">
 <canvas id="ingresosChart"></canvas>
 </div>
 </div>

 <!-- Slide 4: Estadísticas Electrónica -->
 <div class="w-1/4 p-6 flex flex-col bg-transparent" style="height: 420px;">
 <div class="flex justify-between items-center mb-2 px-4">
 <div>
 <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">⚡ Electrónica</h4>
 <p class="text-sm text-gray-500 dark:text-gray-400">Estado actual de órdenes de electrónica</p>
 </div>
 <span class="text-xs font-bold px-3 py-1.5 bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 rounded-full shadow-sm">Tiempo Real</span>
 </div>
 <div class="w-full flex-grow relative flex justify-center items-center pb-8">
 <div style="height: 250px; width: 250px; position: relative;">
 <canvas id="elecSlideDonut"></canvas>
 </div>
 </div>
 </div>

 </div>

 <!-- Controles: fondo muy suave para no tapar los gráficos -->
 <button type="button" id="btnPrev" aria-label="Anterior" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center z-10 border border-gray-200/40 dark:border-gray-500/30 bg-white/25 dark:bg-gray-900/25 backdrop-blur-[2px] text-gray-700/45 dark:text-gray-100/45 shadow-sm opacity-30 transition-all duration-300 group-hover:opacity-55 hover:!opacity-85 hover:bg-white/40 dark:hover:bg-gray-800/45 hover:text-gray-900 dark:hover:text-white hover:scale-105">
 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
 </button>
 <button type="button" id="btnNext" aria-label="Siguiente" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full flex items-center justify-center z-10 border border-gray-200/40 dark:border-gray-500/30 bg-white/25 dark:bg-gray-900/25 backdrop-blur-[2px] text-gray-700/45 dark:text-gray-100/45 shadow-sm opacity-30 transition-all duration-300 group-hover:opacity-55 hover:!opacity-85 hover:bg-white/40 dark:hover:bg-gray-800/45 hover:text-gray-900 dark:hover:text-white hover:scale-105">
 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
 </button>
</div>

<!-- Tarjetas de ingresos (resumen rápido bajo el carrusel) -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
 <div class="glass-card p-4 flex flex-col justify-center relative overflow-hidden group items-center text-center">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <div class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💰</span> Saldo en Caja (Histórico)</div>
 <div class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ $totalCostoFormateado ?? '0.00' }}</div>
 </div>
 <div class="glass-card p-4 flex flex-col justify-center relative overflow-hidden group items-center text-center">
 <div class="absolute -right-4 -top-4 w-20 h-20 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💵</span> Ingresos a Caja (Hoy)</div>
 <div class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ $totalCostoDiaFormateado ?? '0.00' }}</div>
 </div>
</div>

<div class="glass-card p-6 md:p-8 pb-4 md:pb-5">

 {{-- Tabs --}}
 <div class="flex items-center gap-1 mb-5 border-b border-gray-200 dark:border-gray-600">
 <button type="button" onclick="switchDashTab('mant')" id="tabBtnMant"
 class="dash-tab-btn px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all border-b-[3px] border-blue-600 text-blue-700 dark:text-blue-300 bg-blue-50/60 dark:bg-blue-900/20">
 🔧 Mantenimientos
 </button>
 <button type="button" onclick="switchDashTab('elec')" id="tabBtnElec"
 class="dash-tab-btn px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all border-b-[3px] border-transparent text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-300 hover:bg-purple-50/40 dark:hover:bg-purple-900/10">
 ⚡ Electrónica
 </button>
 </div>

 {{-- ═══════════════ TAB: Mantenimientos ═══════════════ --}}
 <div id="tabPanelMant">
 <div class="overflow-x-auto rounded-xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
 <table class="ts-table responsive-table">
 <thead>
 <tr>
 <th class="text-center">Orden</th>
 <th class="text-center">Equipo</th>
 <th class="text-center">Costo</th>
 <th class="text-center">Progreso</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Entrada</th>
 <th class="text-center">Días</th>
 <th class="text-center">Salida</th>
 </tr>
 </thead>
 <tbody class="text-sm">
 @forelse($recentMant ?? [] as $m)
 @php
 $fechaEntrada = \Carbon\Carbon::parse($m->fecha_entrada)->startOfDay();
 $fechaFin = $m->fecha_salida 
 ? \Carbon\Carbon::parse($m->fecha_salida)->startOfDay() 
 : \Carbon\Carbon::now()->startOfDay();
 $diasTranscurridos = $fechaEntrada->diffInDays($fechaFin);
 $dim = $m->anulado ? 'opacity-60 grayscale' : '';
 $dimLight = $m->anulado ? 'opacity-60' : '';
 @endphp
 <tr>
 <td class="text-center font-bold whitespace-nowrap {{ $dim }}">
 <a href="{{ route('mantenimientos.index', ['locate' => $m->id]) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline no-print-link">
 {{ $m->id_orden }}
 </a>
 </td>
 
 <td class="text-center {{ $dim }}">
 <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="flex items-baseline justify-center gap-1 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de equipos">
 <span class="font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap group-hover:underline">
 {{ $m->equipo->nombre ?? '-' }}
 </span>
 <span class="font-bold text-[13px] text-gray-400 italic whitespace-nowrap">
 ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }})
 </span>
 </a>
 </td>

 <td class="text-center {{ $dim }}">
 <div class="flex flex-col items-center gap-1">
 <span class="font-bold text-gray-900 dark:text-white">${{ number_format($m->costo, 0, ',', '.') }}</span>
 @if($m->total_abonado > 0)
 <span class="text-[11px] font-semibold text-green-600">Abonado: ${{ number_format($m->total_abonado, 0, ',', '.') }}</span>
 <span class="text-[11px] font-semibold text-red-500">Saldo: ${{ number_format($m->saldo_pendiente, 0, ',', '.') }}</span>
 @endif
 </div>
 </td>

 <td class="text-center {{ $dimLight }}">
 <span class="pill {{ $m->estado == 'terminado' ? 'pill-done' : 'pill-pending' }} {{ $m->anulado ? 'opacity-70' : '' }}">
 {{ strtoupper($m->estado) }}
 </span>
 </td>
 <td class="text-center">
 <span class="pill {{ $m->anulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $m->anulado ? 'ANULADO' : 'ACTIVO' }}
 </span>
 </td>

 <td class="text-center whitespace-nowrap text-gray-600 dark:text-gray-300 {{ $dim }}">{{ $fechaEntrada->format('d/m/Y') }}</td>
 
 <td class="text-center font-bold {{ $dim }}">
 <span class="{{ !$m->fecha_salida && $diasTranscurridos > 3 ? 'text-red-500' : 'text-gray-600 dark:text-gray-400' }}">
 {{ $diasTranscurridos }}
 </span>
 </td>

 <td class="text-center whitespace-nowrap {{ $m->fecha_salida ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 italic' }} {{ $dim }}">
 {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'En proceso' }}
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400">
 No hay mantenimientos registrados recientemente.
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
    <div class="mt-4 md:mt-5 text-right">
        <a href="{{ route('mantenimientos.reportes') }}" class="btn-primary">
            📈 Ver reporte detallado →
        </a>
    </div>
 </div>

 {{-- ═══════════════ TAB: Electrónica ═══════════════ --}}
 <div id="tabPanelElec" class="hidden">
 <div class="overflow-x-auto rounded-xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
 <table class="ts-table table-electronica responsive-table">
 <thead>
 <tr>
 <th class="text-center">Orden</th>
 <th class="text-center">Equipo</th>
 <th class="text-center">Costo</th>
 <th class="text-center">Progreso</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Entrada</th>
 <th class="text-center">Días</th>
 <th class="text-center">Salida</th>
 </tr>
 </thead>
 <tbody class="text-sm">
 @forelse($recentElec ?? [] as $e)
 @php
 $fechaEntrada = \Carbon\Carbon::parse($e->fecha_entrada)->startOfDay();
 $fechaFin = $e->fecha_salida 
 ? \Carbon\Carbon::parse($e->fecha_salida)->startOfDay() 
 : \Carbon\Carbon::now()->startOfDay();
 $diasTranscurridos = $fechaEntrada->diffInDays($fechaFin);
 $dim = $e->anulado ? 'opacity-60 grayscale' : '';
 $dimLight = $e->anulado ? 'opacity-60' : '';
 @endphp
 <tr>
 <td class="text-center font-bold whitespace-nowrap {{ $dim }}">
 <a href="{{ route('electronicas.index', ['locate' => $e->id]) }}" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 hover:underline no-print-link">
 {{ $e->id_orden }}
 </a>
 </td>

 <td class="text-center {{ $dim }}">
 <a href="{{ route('equipos.index') }}#equipo-{{ $e->equipo_id }}" class="flex items-baseline justify-center gap-1 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de equipos">
 <span class="font-bold text-gray-900 dark:text-gray-100 whitespace-nowrap group-hover:underline">
 {{ $e->equipo->nombre ?? '-' }}
 </span>
 <span class="font-bold text-[13px] text-gray-400 italic whitespace-nowrap">
 ({{ $e->equipo->marca ?? '' }} {{ $e->equipo->modelo ?? '' }})
 </span>
 </a>
 </td>

 <td class="text-center font-bold text-gray-900 dark:text-white {{ $dim }}">
 ${{ number_format($e->costo, 0, ',', '.') }}
 </td>

 <td class="text-center {{ $dimLight }}">
 <span class="pill {{ $e->estado == 'terminado' ? 'pill-done' : 'pill-pending' }} {{ $e->anulado ? 'line-through opacity-70' : '' }}">
 {{ strtoupper($e->estado) }}
 </span>
 </td>
 <td class="text-center">
 <span class="pill {{ $e->anulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $e->anulado ? 'ANULADO' : 'ACTIVO' }}
 </span>
 </td>

 <td class="text-center whitespace-nowrap text-gray-600 dark:text-gray-300 {{ $dim }}">{{ $fechaEntrada->format('d/m/Y') }}</td>
 
 <td class="text-center font-bold {{ $dim }}">
 <span class="{{ !$e->fecha_salida && $diasTranscurridos > 3 ? 'text-red-500' : 'text-gray-600 dark:text-gray-400' }}">
 {{ $diasTranscurridos }}
 </span>
 </td>

 <td class="text-center whitespace-nowrap {{ $e->fecha_salida ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 italic' }} {{ $dim }}">
 {{ $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : 'En proceso' }}
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400">
 No hay órdenes de electrónica registradas recientemente.
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
    <div class="mt-4 md:mt-5 text-right">
        <a href="{{ route('electronicas.reportes') }}" class="btn-purple">
 📈 Ver reporte detallado →
 </a>
 </div>
 </div>

</div>

{{-- Script para cambio de tabs --}}
<script>
function switchDashTab(tab) {
 // Panels
 document.getElementById('tabPanelMant').classList.toggle('hidden', tab !== 'mant');
 document.getElementById('tabPanelElec').classList.toggle('hidden', tab !== 'elec');
 // Buttons
 const btnMant = document.getElementById('tabBtnMant');
 const btnElec = document.getElementById('tabBtnElec');
 const activeClasses = 'border-b-[3px] text-blue-700 dark:text-blue-300 bg-blue-50/60 dark:bg-blue-900/20 border-blue-600';
 const activeClassesPurple = 'border-b-[3px] text-purple-700 dark:text-purple-300 bg-purple-50/60 dark:bg-purple-900/20 border-purple-600';
 const inactiveClasses = 'border-b-[3px] border-transparent text-gray-500 dark:text-gray-400';

 if (tab === 'mant') {
 btnMant.className = 'dash-tab-btn px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all ' + activeClasses;
 btnElec.className = 'dash-tab-btn px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all ' + inactiveClasses + ' hover:text-purple-600 dark:hover:text-purple-300 hover:bg-purple-50/40 dark:hover:bg-purple-900/10';
 } else {
 btnElec.className = 'dash-tab-btn px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all ' + activeClassesPurple;
 btnMant.className = 'dash-tab-btn px-5 py-2.5 text-sm font-bold rounded-t-xl transition-all ' + inactiveClasses + ' hover:text-blue-600 dark:hover:text-blue-300 hover:bg-blue-50/40 dark:hover:bg-blue-900/10';
 }
}
</script>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
 document.addEventListener('DOMContentLoaded', function() {
 // --- LÓGICA DEL CARRUSEL ---
 const track = document.getElementById('carouselTrack');
 const btnPrev = document.getElementById('btnPrev');
 const btnNext = document.getElementById('btnNext');
 const indicators = document.getElementById('carouselIndicators').children;
 const totalSlides = 4;
 let currentSlide = 0;
 let autoPlayInterval;

 function updateSlide() {
 track.style.transform = `translateX(-${currentSlide * 25}%)`;
 for (let i = 0; i < totalSlides; i++) {
            const ind = indicators[i];
            if(ind) {
                ind.classList.remove('w-8', 'w-2.5', 'h-3', 'h-2.5', 'bg-gradient-to-r', 'from-blue-500', 'to-cyan-400', 'shadow-md', 'bg-blue-600', 'dark:bg-blue-500', 'bg-gray-300', 'dark:bg-gray-600', 'bg-gray-300/60', 'dark:bg-gray-600/60');
                if (i === currentSlide) {
                    ind.classList.add('w-8', 'h-2.5', 'bg-gradient-to-r', 'from-blue-500', 'to-cyan-400', 'shadow-md');
                } else {
                    ind.classList.add('w-2.5', 'h-2.5', 'bg-gray-300/60', 'dark:bg-gray-600/60');
                }
            }
 }
 }

 function nextSlide() {
 currentSlide = (currentSlide + 1) % totalSlides;
 updateSlide();
 }

 function prevSlide() {
 currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
 updateSlide();
 }

 function startAutoPlay() {
 autoPlayInterval = setInterval(nextSlide, 15000); // Demora de 15 SEGUNDOS
 }

 function resetAutoPlay() {
 clearInterval(autoPlayInterval);
 startAutoPlay();
 }

 if (btnNext) btnNext.addEventListener('click', () => { nextSlide(); resetAutoPlay(); });
 if (btnPrev) btnPrev.addEventListener('click', () => { prevSlide(); resetAutoPlay(); });

 Array.from(indicators).forEach((ind, index) => {
 ind.addEventListener('click', () => {
 currentSlide = index;
 updateSlide();
 resetAutoPlay();
 });
 });

 startAutoPlay();
 updateSlide();

 // --- LÓGICA DE GRÁFICOS (CHART.JS) MEJORADA ---
 Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
 Chart.defaults.color = '#6B7280'; // gray-500

    Chart.Tooltip.positioners.cursorCustom = function(elements, eventPosition) {
        return {
            x: eventPosition.x,
            y: eventPosition.y
        };
    };
 
 const chartData = @json($chartData);
 const stats = @json($stats);
 const ingresosData = (chartData && chartData.ingresos) ? chartData.ingresos : [];

 function formatMoneyEs(value) {
 const n = Number(value) || 0;
 return '$' + n.toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
 }

 // Configurar gradientes para el gráfico de barras
 const canvasBar = document.getElementById('barChart');
 if (canvasBar) {
 const ctxBar = canvasBar.getContext('2d');
 const gradientBlue = ctxBar.createLinearGradient(0, 0, 0, 400);
 gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.9)'); // solid blue
 gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0.1)'); // transparent blue
 
 const gradientGreen = ctxBar.createLinearGradient(0, 0, 0, 400);
 gradientGreen.addColorStop(0, 'rgba(16, 185, 129, 0.9)'); // solid green
 gradientGreen.addColorStop(1, 'rgba(16, 185, 129, 0.1)'); // transparent green

 new Chart(ctxBar, {
 type: 'bar',
 data: {
 labels: chartData.labels,
 datasets: [
 {
 label: 'Equipos Registrados',
 data: chartData.equipos,
 backgroundColor: gradientBlue,
 borderColor: 'rgb(59, 130, 246)',
 borderWidth: 2,
 borderRadius: 6, // bordes muy redondeados
 borderSkipped: false,
 barPercentage: 0.6,
 categoryPercentage: 0.8
 },
 {
 label: 'Mantenimientos Creados',
 data: chartData.mantenimientos,
 backgroundColor: gradientGreen,
 borderColor: 'rgb(16, 185, 129)',
 borderWidth: 2,
 borderRadius: 6,
 borderSkipped: false,
 barPercentage: 0.6,
 categoryPercentage: 0.8
 }
 ]
 },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 interaction: {
 mode: 'index',
 intersect: false,
 },
 plugins: { 
 legend: { 
 position: 'top',
 labels: { usePointStyle: true, padding: 20, font: { weight: 'bold' } }
 },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: true,
                    usePointStyle: true,
                    position: 'cursorCustom'
                }
 },
 scales: { 
 y: { 
 beginAtZero: true, 
 ticks: { precision: 0, padding: 10 },
 grid: { borderDash: [4, 4], color: 'rgba(156, 163, 175, 0.2)', drawBorder: false }
 },
 x: {
 grid: { display: false, drawBorder: false },
 ticks: { font: { weight: 'bold' }, padding: 10 }
 }
 }
 }
 });
 }

 // Texto central de la dona: debe dibujarse DESPUÉS de los segmentos (no en beforeDraw) para que no quede tapado.
 const centerTextPlugin = {
 id: 'centerText',
 afterDraw: function(chart) {
 if (chart.config.type !== 'doughnut') return;
 const width = chart.width;
 const height = chart.height;
        const { ctx, chartArea } = chart;
        if (!chartArea) return;
        const centerX = (chartArea.left + chartArea.right) / 2;
        const centerY = (chartArea.top + chartArea.bottom) / 2;
        const isDark = document.documentElement.classList.contains('dark');

        ctx.save();
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'center';

        const total = chart.config.data.datasets[0].data.reduce(function (a, b) { return a + b; }, 0);

        // Label arriba
        ctx.font = '600 0.7em sans-serif';
        ctx.fillStyle = isDark ? '#d1d5db' : '#6b7280';
        ctx.fillText('Órdenes Totales', centerX, centerY - 12);
        
        // Número grande centrado
        ctx.font = 'bold 2.2em sans-serif';
        ctx.fillStyle = isDark ? '#f9fafb' : '#111827';
        ctx.fillText(total, centerX, centerY + 16);

        ctx.restore();
 }
 };

 // Slide 2: Gráfico Circular (Doughnut) mejorado
 const canvasPie = document.getElementById('pieChart');
 if (canvasPie) {
 const ctxPie = canvasPie.getContext('2d');
 
 // Gradientes para la dona translucidos
 const gradTerminado = ctxPie.createLinearGradient(0, 0, 0, 400);
 gradTerminado.addColorStop(0, 'rgba(16, 185, 129, 0.7)');
 gradTerminado.addColorStop(1, 'rgba(16, 185, 129, 0.1)');
 
 const gradPendiente = ctxPie.createLinearGradient(0, 0, 0, 400);
 gradPendiente.addColorStop(0, 'rgba(245, 158, 11, 0.7)');
 gradPendiente.addColorStop(1, 'rgba(245, 158, 11, 0.1)');

 const pieChart = new Chart(ctxPie, {
 type: 'doughnut',
 plugins: [centerTextPlugin],
        data: {
            labels: ['Pendientes', 'Terminados'],
            datasets: [{
                data: [
                    {{ $stats['pendientes'] ?? 0 }},
                    {{ $stats['terminados'] ?? 0 }}
                ],
                backgroundColor: [gradPendiente, gradTerminado],
                hoverBackgroundColor: ['rgba(245, 158, 11, 1)', 'rgba(16, 185, 129, 1)'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            layout: { padding: 6 },
 plugins: { 
 legend: { 
 position: 'bottom',
 labels: { usePointStyle: true, padding: 15, font: { weight: 'bold', size: 12 } }
 },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    bodyFont: { size: 14, weight: 'bold' },
                    padding: 12,
                    cornerRadius: 8,
                    usePointStyle: true,
                    position: 'cursorCustom',
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed !== null) { label += context.parsed + ' órdenes'; }
                            return label;
                        }
                    }
                }
            },
            animation: { animateScale: true, animateRotate: true }
        }
 });

 // Al cambiar claro/oscuro el canvas no se redibuja solo: forzar actualización del gráfico circular
 if (typeof MutationObserver !== 'undefined') {
 new MutationObserver(function () {
 pieChart.update('none');
 }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
 }
 }

 const canvasIngresos = document.getElementById('ingresosChart');
 if (canvasIngresos) {
 const ctxIng = canvasIngresos.getContext('2d');
 const gradientTeal = ctxIng.createLinearGradient(0, 0, 0, 400);
 gradientTeal.addColorStop(0, 'rgba(20, 184, 166, 0.85)'); // green/teal
 gradientTeal.addColorStop(1, 'rgba(20, 184, 166, 0.12)');

 const gradientBlueIng = ctxIng.createLinearGradient(0, 0, 0, 400);
 gradientBlueIng.addColorStop(0, 'rgba(59, 130, 246, 0.85)'); // blue
 gradientBlueIng.addColorStop(1, 'rgba(59, 130, 246, 0.12)');

 const ingresosAcumuladosData = (chartData && chartData.ingresosAcumulados) ? chartData.ingresosAcumulados : [];

 new Chart(ctxIng, {
 type: 'bar',
 data: {
 labels: chartData.labels,
 datasets: [
 {
 label: 'Ingresos Acumulados',
 data: ingresosAcumuladosData,
 backgroundColor: gradientTeal,
 borderColor: 'rgb(13, 148, 136)',
 borderWidth: 2,
 borderRadius: 6,
 borderSkipped: false,
 barPercentage: 0.55,
 categoryPercentage: 0.85
 },
 {
 label: 'Ingresos del Día',
 data: ingresosData,
 backgroundColor: gradientBlueIng,
 borderColor: 'rgb(59, 130, 246)',
 borderWidth: 2,
 borderRadius: 6,
 borderSkipped: false,
 barPercentage: 0.55,
 categoryPercentage: 0.85
 }
 ]
 },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 plugins: {
 legend: {
 display: true,
 position: 'top',
 labels: { usePointStyle: true, padding: 15, font: { weight: 'bold' } }
 },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 12,
                    cornerRadius: 8,
                    usePointStyle: true,
                    position: 'cursorCustom',
 callbacks: {
 label: function(ctx) {
 return ctx.dataset.label + ': ' + formatMoneyEs(ctx.parsed.y);
 }
 }
 }
 },
 scales: {
 y: {
 beginAtZero: true,
 ticks: {
 precision: 0,
 callback: function(v) { return '$' + Number(v).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }
 },
 grid: { borderDash: [4, 4], color: 'rgba(156, 163, 175, 0.2)', drawBorder: false }
 },
 x: {
 grid: { display: false, drawBorder: false },
 ticks: { font: { weight: 'bold' }, padding: 8 }
 }
 }
 }
 });
 }

 // Slide 4: Dona Electrónica (Pendientes vs Terminados)
 const canvasSlideDonut = document.getElementById('elecSlideDonut');
 if (canvasSlideDonut) {
 const ctxSD = canvasSlideDonut.getContext('2d');
 const gPendS = ctxSD.createLinearGradient(0, 0, 0, 220);
 gPendS.addColorStop(0, 'rgba(245, 158, 11, 0.9)');
 gPendS.addColorStop(1, 'rgba(245, 158, 11, 0.3)');
 const gTermS = ctxSD.createLinearGradient(0, 0, 0, 220);
 gTermS.addColorStop(0, 'rgba(16, 185, 129, 0.9)');
 gTermS.addColorStop(1, 'rgba(16, 185, 129, 0.3)');

 const slideCenterPlugin = {
 id: 'elecSlideCenter',
 afterDraw(chart) {
 if (chart.config.type !== 'doughnut') return;
 const { ctx, chartArea } = chart;
 if (!chartArea) return;
 const centerX = (chartArea.left + chartArea.right) / 2;
 const centerY = (chartArea.top + chartArea.bottom) / 2;
 const isDark = document.documentElement.classList.contains('dark');
 ctx.save();
 const total = chart.config.data.datasets[0].data.reduce((a, b) => a + b, 0);
 ctx.textBaseline = 'middle';
 ctx.textAlign = 'center';
 // Label arriba
 ctx.font = '600 0.7em sans-serif';
 ctx.fillStyle = isDark ? '#d1d5db' : '#6b7280';
 ctx.fillText('Total Órdenes', centerX, centerY - 12);
 // Número grande centrado
 ctx.font = 'bold 2.2em sans-serif';
 ctx.fillStyle = isDark ? '#f9fafb' : '#111827';
 ctx.fillText(total, centerX, centerY + 16);
 ctx.restore();
 }
 };

 const slideDonut = new Chart(ctxSD, {
 type: 'doughnut',
 plugins: [slideCenterPlugin],
 data: {
 labels: ['Pendientes', 'Terminados'],
 datasets: [{
 data: [{{ $chartData['electronicaPendientes'] }}, {{ $chartData['electronicaTerminados'] }}],
 backgroundColor: [gPendS, gTermS],
 hoverBackgroundColor: ['rgba(245,158,11,1)', 'rgba(16,185,129,1)'],
 borderWidth: 0
 }]
 },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 cutout: '68%',
 layout: { padding: 6 },
 plugins: {
 legend: {
 position: 'bottom',
 labels: { usePointStyle: true, padding: 14, font: { weight: 'bold', size: 12 } }
 },
                tooltip: {
                    backgroundColor: 'rgba(17,24,39,0.9)',
                    bodyFont: { size: 13, weight: 'bold' },
                    padding: 10, cornerRadius: 8, usePointStyle: true,
                    position: 'cursorCustom',
                    callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' órdenes' }
                }
 },
 animation: { animateScale: true, animateRotate: true }
 }
 });
 if (typeof MutationObserver !== 'undefined') {
 new MutationObserver(() => slideDonut.update('none'))
 .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
 }
 }

 // Tab Electrónica: Dona Pendientes vs Terminados
 const canvasTabDonut = document.getElementById('elecTabDonut');
 if (canvasTabDonut) {
 const ctxDonut = canvasTabDonut.getContext('2d');
 const gPend = ctxDonut.createLinearGradient(0, 0, 0, 170);
 gPend.addColorStop(0, 'rgba(245, 158, 11, 0.85)');
 gPend.addColorStop(1, 'rgba(245, 158, 11, 0.25)');
 const gTerm = ctxDonut.createLinearGradient(0, 0, 0, 170);
 gTerm.addColorStop(0, 'rgba(16, 185, 129, 0.85)');
 gTerm.addColorStop(1, 'rgba(16, 185, 129, 0.25)');

 const centerPlugin = {
 id: 'elecTabCenter',
 afterDraw(chart) {
 if (chart.config.type !== 'doughnut') return;
 const { width, height, ctx } = chart;
 const isDark = document.documentElement.classList.contains('dark');
 ctx.save();
 const total = chart.config.data.datasets[0].data.reduce((a, b) => a + b, 0);
 ctx.font = 'bold 1.6em sans-serif';
 ctx.textBaseline = 'middle';
 ctx.textAlign = 'center';
 ctx.fillStyle = isDark ? '#f9fafb' : '#111827';
 ctx.fillText(total, width / 2, height / 2 - 8);
 ctx.font = '600 0.65em sans-serif';
 ctx.fillStyle = isDark ? '#d1d5db' : '#6b7280';
 ctx.fillText('Total', width / 2, height / 2 + 14);
 ctx.restore();
 }
 };

 const tabDonut = new Chart(ctxDonut, {
 type: 'doughnut',
 plugins: [centerPlugin],
 data: {
 labels: ['Pendientes', 'Terminados'],
 datasets: [{
 data: [{{ $chartData['electronicaPendientes'] }}, {{ $chartData['electronicaTerminados'] }}],
 backgroundColor: [gPend, gTerm],
 hoverBackgroundColor: ['rgba(245,158,11,1)', 'rgba(16,185,129,1)'],
 borderWidth: 0
 }]
 },
 options: {
 responsive: true,
 maintainAspectRatio: false,
 cutout: '68%',
 layout: { padding: 4 },
 plugins: {
 legend: { position: 'bottom', labels: { usePointStyle: true, padding: 8, font: { weight: 'bold', size: 11 } } },
 tooltip: {
                            backgroundColor: 'rgba(17,24,39,0.9)',
                            bodyFont: { size: 13, weight: 'bold' },
                            padding: 10, cornerRadius: 8, usePointStyle: true,
                            position: 'cursorCustom',
                            callbacks: { label: ctx => ctx.label + ': ' + ctx.parsed + ' órdenes' }
                        }
 },
 animation: { animateScale: true, animateRotate: true }
 }
 });
 // Dark mode reactivity
 if (typeof MutationObserver !== 'undefined') {
 new MutationObserver(() => tabDonut.update('none'))
 .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
 }
 }

 });
</script>
@endsection

