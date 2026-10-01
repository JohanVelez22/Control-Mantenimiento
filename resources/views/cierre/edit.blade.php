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

        {{-- Resumen Informativo de Saldos y Arqueo (Solo lectura) --}}
        <div class="p-5 rounded-2xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200/60 dark:border-blue-500/20 mb-8">
            <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                <span>🔒</span> Valores Contables y Arqueo Consolidado (Auditoría Inmutable)
            </h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                <div class="p-3 rounded-xl bg-white/20 dark:bg-slate-900/30 border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Ingresos</p>
                    <p class="font-black text-slate-800 dark:text-white text-base">${{ number_format($cierre->total_ingresos, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/20 dark:bg-slate-900/30 border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                    <p class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase">Egresos</p>
                    <p class="font-black text-slate-800 dark:text-white text-base">${{ number_format($cierre->total_egresos, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/20 dark:bg-slate-900/30 border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                    <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase">Efectivo Sistema</p>
                    <p class="font-black text-slate-800 dark:text-white text-base">${{ number_format($cierre->efectivo, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/20 dark:bg-slate-900/30 border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                    <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase">Contado en Mano</p>
                    <p class="font-black text-slate-800 dark:text-white text-base">${{ number_format($cierre->efectivo_real_contado ?? $cierre->efectivo, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-white/20 dark:bg-slate-900/30 border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                    <p class="text-[10px] font-bold uppercase {{ $cierre->estado_diferencia === 'cuadrado' ? 'text-emerald-600 dark:text-emerald-400' : ($cierre->estado_diferencia === 'faltante' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400') }}">
                        Diferencia
                    </p>
                    <p class="font-black text-base {{ $cierre->estado_diferencia === 'cuadrado' ? 'text-emerald-600 dark:text-emerald-400' : ($cierre->estado_diferencia === 'faltante' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400') }}">
                        {{ ($cierre->diferencia > 0 ? '+' : ($cierre->diferencia < 0 ? '-' : '')) . '$' . number_format(abs($cierre->diferencia), 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-3 rounded-xl bg-white/20 dark:bg-slate-900/30 border border-white/40 dark:border-white/5 backdrop-blur-sm shadow-sm">
                    <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase">Saldo Final</p>
                    <p class="font-black text-slate-800 dark:text-white text-base">${{ number_format($cierre->saldo_final, 0, ',', '.') }}</p>
                </div>
            </div>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-4 text-center">
                ℹ️ Los valores financieros se preservan para garantizar la integridad contable. Si necesitas recalcular los valores de la jornada, debes eliminar el cierre ingresando la clave de administrador.
            </p>
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
