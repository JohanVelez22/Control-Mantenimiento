@extends('layouts.app')
@section('title', 'Detalle de Movimiento de Caja')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        
        {{-- Alerta de estado anulado --}}
        @if($movimiento->anulado)
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-center shadow-[0_4px_20px_rgba(239,68,68,0.15)]">
            <span class="text-2xl drop-shadow-md">🚫</span>
            <h3 class="font-black text-red-600 dark:text-red-400 mt-1 uppercase tracking-widest">Movimiento Anulado</h3>
            <p class="text-sm font-medium text-red-500/80 mt-1">Este movimiento no afecta los saldos actuales de caja.</p>
        </div>
        @endif

        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('caja.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver a la lista de caja">⬅️</a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white tracking-tight">
                            Movimiento <span class="text-blue-600 dark:text-blue-400">#{{ $movimiento->id }}</span>
                        </h2>
                        <span class="pill {{ $movimiento->anulado ? 'pill-anulado' : 'pill-done' }} text-xs py-1 px-3 font-bold uppercase tracking-wider">
                            {{ $movimiento->anulado ? 'ANULADO' : 'ACTIVO' }}
                        </span>
                        <span class="pill {{ $movimiento->tipo_movimiento === 'ingreso' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' }} text-xs py-1 px-3 font-bold uppercase tracking-wider">
                            {{ $movimiento->tipo_movimiento === 'ingreso' ? '📈 INGRESO' : '📉 EGRESO' }}
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-2">
                        Registrado el: <span class="font-bold text-slate-700 dark:text-slate-300">{{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</span>
                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('caja.print', $movimiento->id) }}" target="_blank" class="btn-ghost border-gray-500/20 text-gray-600 dark:text-gray-300">
                    🖨️ Imprimir
                </a>
                
                @if(!auth()->user()->isInvitado())
                <a href="{{ route('caja.edit', $movimiento->id) }}" class="btn-ghost border-yellow-500/20 text-yellow-600">
                    ✏️ Editar
                </a>
                <button type="button" onclick="openAnularModal('{{ route('caja.anular', $movimiento->id) }}', {{ $movimiento->anulado ? 'true' : 'false' }})" class="btn-danger">
                    {{ $movimiento->anulado ? '✅ Reactivar' : '🚫 Anular' }}
                </button>
                @endif
            </div>
        </div>

        {{-- Datos Operativos del Movimiento --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 text-sm p-6 rounded-2xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200/60 dark:border-blue-500/20 mb-8">
            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">
                    {{ $movimiento->persona ? '👤 Cliente / Persona' : ($movimiento->empresa ? '🏢 Proveedor / Empresa' : '👤 Tercero') }}
                </span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 break-words">
                    {{ $movimiento->persona ?? ($movimiento->empresa ?? '—') }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">🏷️ Concepto de Caja</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 break-words">
                    {{ $movimiento->concepto->nombre ?? '—' }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">💳 Forma de Pago</span>
                <span class="pill {{ $movimiento->tipo_pago === 'efectivo' ? 'pill-efectivo' : 'pill-banco' }} text-xs font-bold mt-1 inline-block">
                    {{ $movimiento->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco / Consignación' }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">📊 Tipo Operación</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 capitalize flex items-center gap-1.5 mt-0.5">
                    {{ $movimiento->tipo_movimiento === 'ingreso' ? '📈 Ingreso' : '📉 Egreso' }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">👤 Registrado Por</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 break-words">
                    {{ $movimiento->user->name ?? 'Sistema' }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">📅 Fecha Registro</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}
                </span>
            </div>
        </div>

        {{-- Resumen Financiero --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Resumen Financiero</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-blue p-4 text-center">
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Monto Transacción</p>
                <p class="text-xl font-black text-slate-800 dark:text-white">${{ number_format($movimiento->monto, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-indigo p-4 text-center">
                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-1">Monto Total Servicio/Factura</p>
                <p class="text-xl font-black text-slate-800 dark:text-white">${{ number_format($movimiento->effective_monto_total ?: $movimiento->monto, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-emerald p-4 text-center">
                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Total Acumulado Pagado</p>
                <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">${{ number_format($movimiento->total_pagado, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow {{ $movimiento->saldo_pendiente > 0 ? 'glass-card-orange' : 'glass-card-teal' }} p-4 text-center">
                <p class="text-[10px] font-bold {{ $movimiento->saldo_pendiente > 0 ? 'text-orange-500' : 'text-teal-500' }} uppercase tracking-widest mb-1">Saldo Pendiente</p>
                <p class="text-xl font-black {{ $movimiento->saldo_pendiente > 0 ? 'text-orange-500' : 'text-slate-800 dark:text-white' }}">${{ number_format($movimiento->saldo_pendiente, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Descripción --}}
        @if($movimiento->descripcion)
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Descripción / Observaciones</h3>
        <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700 mb-8">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $movimiento->descripcion }}</p>
        </div>
        @endif

        @php
            $rootId = $movimiento->parent_id ?: $movimiento->id;
            $refSearch = $movimiento->ref_search;

            $relacionados = \App\Models\MovimientoCaja::where('estado', 'activo')
                ->where('id', '!=', $movimiento->id)
                ->where(function($q) use ($rootId, $refSearch) {
                    if ($rootId) {
                        $q->where('id', $rootId)
                          ->orWhere('parent_id', $rootId);
                    }
                    if ($refSearch) {
                        $q->orWhere('descripcion', 'like', "%{$refSearch}%");
                    }
                })
                ->orderBy('created_at', 'asc')
                ->get();
        @endphp

        {{-- Historial de Abonos / Pagos Relacionados --}}
        @if($relacionados->isNotEmpty())
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <span>📑</span> Pagos y Abonos Relacionados ({{ $relacionados->count() }})
        </h3>
        <div class="overflow-x-auto overflow-y-auto max-h-[400px] relative mb-8">
            <table class="ts-table mb-0 w-full">
                <thead>
                    <tr>
                        <th class="text-center whitespace-nowrap" style="width: 60px;">Código</th>
                        <th class="text-center whitespace-nowrap" style="width: 100px;">Fecha</th>
                        <th class="text-left">Descripción</th>
                        <th class="text-center whitespace-nowrap" style="width: 130px;">Método Pago</th>
                        <th class="text-center whitespace-nowrap" style="width: 140px;">Registrado Por</th>
                        <th class="text-right whitespace-nowrap" style="width: 100px;">Monto</th>
                        <th class="text-center whitespace-nowrap" style="width: 90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($relacionados as $child)
                    <tr class="{{ $child->anulado ? 'opacity-50 grayscale' : '' }}">
                        <td class="font-bold text-center text-slate-600 dark:text-slate-300">#{{ $child->id }}</td>
                        <td class="text-sm font-medium text-center whitespace-nowrap">{{ \Carbon\Carbon::parse($child->fecha)->format('d/m/Y') }}</td>
                        <td class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $child->descripcion }}</td>
                        <td class="text-center whitespace-nowrap">
                            <span class="pill {{ $child->tipo_pago === 'efectivo' ? 'pill-efectivo' : 'pill-banco' }} text-xs">
                                {{ $child->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}
                            </span>
                        </td>
                        <td class="text-sm font-bold text-center text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ $child->user->name ?? 'Sistema' }}
                        </td>
                        <td class="text-right font-black {{ $child->tipo_movimiento === 'ingreso' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} whitespace-nowrap">
                            ${{ number_format($child->monto, 0, ',', '.') }}
                        </td>
                        <td class="text-center whitespace-nowrap">
                            <a href="{{ route('caja.show', $child->id) }}" class="btn-ghost px-2.5 py-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30" title="Ver detalle de este pago">
                                👁️ Ver
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
            <span>Última actualización: {{ $movimiento->updated_at->format('d/m/Y H:i:s') }}</span>
            <span>Fecha creación: {{ $movimiento->created_at->format('d/m/Y H:i:s') }}</span>
        </div>

    </div>
</div>
@endsection
