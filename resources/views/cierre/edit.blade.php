@extends('layouts.app')
@section('title', 'Editar Cierre de Caja — ' . $cierre->fecha->format('d/m/Y'))

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="glass-card p-6 md:p-8">
        
        {{-- Encabezado --}}
        <div class="flex items-center gap-4 mb-6 border-b border-gray-200/50 dark:border-white/10 pb-5">
            <a href="{{ route('cierre.show', $cierre->id) }}" class="btn-ghost px-3 py-2 text-xl" title="Volver al detalle del cierre">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">
                    Editar Observaciones del Cierre <span class="text-blue-600 dark:text-blue-400">#{{ $cierre->id }}</span>
                </h2>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                    Cierre del día: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $cierre->fecha->format('d/m/Y') }}</span>
                </p>
            </div>
        </div>

        {{-- Resumen Informativo de Saldos (Solo lectura) --}}
        <div class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200/60 dark:border-blue-500/20 mb-6">
            <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">🔒 Valores Contables Consolidados (Lectura Única)</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="p-2 rounded-xl bg-white/50 dark:bg-slate-800/50">
                    <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase">Ingresos</p>
                    <p class="font-bold text-slate-800 dark:text-white">${{ number_format($cierre->total_ingresos, 0, ',', '.') }}</p>
                </div>
                <div class="p-2 rounded-xl bg-white/50 dark:bg-slate-800/50">
                    <p class="text-[10px] font-bold text-red-600 dark:text-red-400 uppercase">Egresos</p>
                    <p class="font-bold text-slate-800 dark:text-white">${{ number_format($cierre->total_egresos, 0, ',', '.') }}</p>
                </div>
                <div class="p-2 rounded-xl bg-white/50 dark:bg-slate-800/50">
                    <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase">Efectivo</p>
                    <p class="font-bold text-slate-800 dark:text-white">${{ number_format($cierre->efectivo, 0, ',', '.') }}</p>
                </div>
                <div class="p-2 rounded-xl bg-white/50 dark:bg-slate-800/50">
                    <p class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase">Saldo Final</p>
                    <p class="font-bold text-slate-800 dark:text-white">${{ number_format($cierre->saldo_final, 0, ',', '.') }}</p>
                </div>
            </div>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-3 text-center">
                ℹ️ Los valores financieros se preservan para garantizar la integridad contable. Si necesitas recalcular los valores, debes eliminar el cierre ingresando la clave de administrador.
            </p>
        </div>

        {{-- Formulario de Edición --}}
        <form action="{{ route('cierre.update', $cierre->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="observaciones" class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-2">
                    📝 Observaciones / Notas de Arqueo
                </label>
                <textarea name="observaciones" id="observaciones" rows="5" placeholder="Escribe notas adicionales sobre el arqueo o la jornada..." class="glass-input w-full p-3 font-medium text-slate-800 dark:text-slate-200">{{ old('observaciones', $cierre->observaciones) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200/50 dark:border-white/10">
                <a href="{{ route('cierre.show', $cierre->id) }}" class="btn-ghost px-5 py-2.5 font-bold">
                    Cancelar
                </a>
                <button type="submit" class="btn-primary px-6 py-2.5 font-bold">
                    💾 Guardar Cambios
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
