@extends('layouts.app')
@section('title', 'Editar Observaciones de Cierre — ' . $cierre->fecha->format('d/m/Y'))

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="glass-card p-6 md:p-8">
        
        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('cierre.show', $cierre->id) }}" class="btn-ghost px-3 py-2 text-xl" title="Volver al detalle del cierre">⬅️</a>
                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white tracking-tight">
                        Editar Observaciones del Cierre <span class="text-blue-600 dark:text-blue-400">#{{ $cierre->id }}</span>
                    </h2>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                        Cierre del día: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $cierre->fecha->format('d/m/Y') }}</span>
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('cierre.show', $cierre->id) }}" class="btn-ghost border-blue-500/20 text-blue-600 dark:text-blue-400 text-xs px-3 py-2 font-bold">
                    👁️ Ver Detalle Completo
                </a>
            </div>
        </div>

        {{-- Resumen Financiero del Día Homogéneo --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <span>📊</span> Resumen Financiero del Día (Auditoría Inmutable)
        </h3>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-emerald p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📈</span> Ingresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->total_ingresos, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-red p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📉</span> Egresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->total_egresos, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-blue p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">💵</span> Efectivo Sistema</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->efectivo, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-purple p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🏦</span> Consignación</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->consignacion, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow {{ $cierre->saldo_final >= 0 ? 'glass-card-teal' : 'glass-card-orange' }} p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0 col-span-2 lg:col-span-1">
                <p class="text-xs font-bold {{ $cierre->saldo_final >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">⚖️</span> Saldo Teórico</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->saldo_final, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Conciliación Física / Arqueo en Mano Homogéneo --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <span>💵</span> Arqueo y Conciliación Física
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-blue p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">💻</span> Efectivo Teórico</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->efectivo, 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium z-10">Calculado por ingresos y egresos</p>
            </div>

            <div class="glass-card hover-glow glass-card-gray p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🖐🏻</span> Contado en Mano</p>
                @if($cierre->efectivo_real_contado !== null)
                    <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->efectivo_real_contado, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium z-10">Billetes y monedas físicos en caja</p>
                @else
                    <p class="text-2xl font-black text-slate-400 dark:text-slate-500 z-10">—</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium z-10">No se registró conteo físico</p>
                @endif
            </div>

            <div class="glass-card hover-glow {{ $cierre->estado_diferencia_card }} p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center
                          {{ match($cierre->estado_diferencia) {
                              'cuadrado' => 'text-emerald-600 dark:text-emerald-400',
                              'sobrante' => 'text-blue-600 dark:text-blue-400',
                              'faltante' => 'text-red-600 dark:text-red-400',
                              default    => 'text-slate-500 dark:text-slate-400',
                          } }}">
                    <span class="text-lg no-print-emoji">⚖️</span> Resultado del Arqueo
                </p>
                @if($cierre->estado_diferencia === 'sin_conciliar')
                    <p class="text-2xl font-black text-slate-400 dark:text-slate-500 z-10">—</p>
                @else
                    <p class="text-2xl font-black z-10
                              {{ match($cierre->estado_diferencia) {
                                  'cuadrado' => 'text-emerald-600 dark:text-emerald-400',
                                  'sobrante' => 'text-blue-600 dark:text-blue-400',
                                  default    => 'text-red-600 dark:text-red-400',
                              } }}">
                        {{ $cierre->diferencia > 0 ? '+' : ($cierre->diferencia < 0 ? '-' : '') }}${{ number_format(abs($cierre->diferencia), 0, ',', '.') }}
                    </p>
                @endif
                <div class="mt-1 z-10">
                    @if($cierre->estado_diferencia === 'cuadrado')
                        <span class="pill pill-done text-xs font-bold py-0.5 px-3">🟢 Cuadre Exacto</span>
                    @elseif($cierre->estado_diferencia === 'faltante')
                        <span class="pill pill-anulado text-xs font-bold py-0.5 px-3">🔴 Faltante en Caja</span>
                    @elseif($cierre->estado_diferencia === 'sobrante')
                        <span class="pill pill-efectivo text-xs font-bold py-0.5 px-3">🔵 Sobrante en Caja</span>
                    @else
                        <span class="pill pill-done text-xs font-bold py-0.5 px-3 opacity-60">⚪ Sin conciliar</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Formulario de Edición --}}
        <form action="{{ route('cierre.update', $cierre->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="motivo_diferencia" class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-2">
                    📝 Motivo / Justificación del Descuadre
                </label>
                <input type="text" 
                       name="motivo_diferencia" 
                       id="motivo_diferencia" 
                       value="{{ old('motivo_diferencia', $cierre->motivo_diferencia) }}" 
                       placeholder="Aclaración sobre sobrante o faltante de arqueo..." 
                       class="glass-input w-full p-3 font-medium text-slate-800 dark:text-slate-200 text-sm">
            </div>

            <div>
                <label for="observaciones" class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-2">
                    📋 Observaciones Generales de la Jornada
                </label>
                <textarea name="observaciones" 
                          id="observaciones" 
                          rows="4" 
                          placeholder="Escribe notas adicionales sobre la jornada o eventos extraordinarios..." 
                          class="glass-input w-full p-3 font-medium text-slate-800 dark:text-slate-200">{{ old('observaciones', $cierre->observaciones) }}</textarea>
            </div>

            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('cierre.show', $cierre->id) }}" class="btn-cancel">✕ Cancelar</a>
                <button type="submit" class="btn-save">
                    💾 Guardar Cambios
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
