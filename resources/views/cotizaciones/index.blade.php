@extends('layouts.app')

@section('content')
<div class="glass-card p-6">
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 relative z-10">
        <div>
            <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                <span class="text-3xl">📝</span> Cotizaciones
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">
                Gestiona presupuestos para clientes sin afectar caja ni stock.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
                <input type="text" id="search-cotizaciones" placeholder="Buscar cotización..." class="glass-input pl-9 w-48 sm:w-64">
            </div>
            @if(!auth()->user()->isInvitado())
            <a href="{{ route('cotizaciones.create') }}" class="btn-primary">
                ➕ Nueva Cotización
            </a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto pb-2">
        <table id="tabla-cotizaciones" class="ts-table responsive-table w-full">
            <thead>
                <tr>
                    <th class="w-24 text-left">Código</th>
                    <th class="text-left">Tipo</th>
                    <th class="w-[20%] text-left">Descripción</th>
                    <th class="text-left">Cliente</th>
                    <th class="w-32 text-center">Fecha</th>
                    <th class="w-32 text-center">Validez</th>
                    <th class="w-32 text-right">Total</th>
                    <th class="w-32 text-center">Estado</th>
                    <th class="w-32 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $cot)
                @php
                    $primerItem = $cot->items->first();
                    $tipoStr = $primerItem ? ($primerItem->tipo === 'stock' ? 'Producto' : 'Servicio') : '-';
                    if ($cot->items->count() > 1) {
                        $tipoStr .= ' (Mixto)';
                    }
                    $descStr = $primerItem ? $primerItem->descripcion : '-';
                    if ($cot->items->count() > 1) {
                        $descStr .= ' (+' . ($cot->items->count() - 1) . ' más)';
                    }
                @endphp
                <tr>
                    <td class="font-bold text-slate-600 dark:text-slate-300">{{ $cot->codigo }}</td>
                    <td class="font-bold text-indigo-600 dark:text-indigo-400 text-sm whitespace-nowrap">{{ $tipoStr }}</td>
                    <td class="text-gray-600 dark:text-gray-300 text-xs font-medium max-w-[200px] truncate" title="{{ $descStr }}">{{ $descStr }}</td>
                    <td class="font-bold text-slate-800 dark:text-white">{{ $cot->cliente->nombre ?? 'N/A' }}</td>
                    <td class="text-center font-medium">{{ \Carbon\Carbon::parse($cot->fecha)->format('d/m/Y') }}</td>
                    <td class="text-center font-medium">{{ $cot->validez_dias }} días</td>
                    <td class="text-right font-bold text-slate-800 dark:text-white">${{ number_format($cot->total, 0, '', '.') }}</td>
                    <td class="text-center">
                        <span class="pill {{ $cot->estado === 'aprobada' ? 'pill-done' : ($cot->estado === 'rechazada' ? 'pill-anulado' : 'pill-pending') }}">
                            {{ ucfirst($cot->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="flex flex-wrap justify-center gap-1.5 max-w-[85px] mx-auto">
                            <a href="{{ route('cotizaciones.show', $cot) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-indigo-600 flex items-center justify-center" title="Ver detalle">👁️</a>
                            
                            @if($cot->estado === 'aprobada')
                                <a href="{{ route('cotizaciones.pdf', $cot) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 hover:text-green-800 hover:bg-green-50/50 flex items-center justify-center" title="Imprimir PDF">🖨️</a>
                            @endif

@if($cot->estado === 'pendiente' && (!auth()->user() || auth()->user()->role !== 'invitado'))
    <a href="{{ route('cotizaciones.edit', $cot) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600 flex items-center justify-center" title="Editar">✏️</a>

    <button type="button" onclick="openAnularModal('{{ route('cotizaciones.rechazar', $cot) }}', false)" class="btn-ghost px-2.5 py-1.5 text-xs text-red-600 border-red-500/20 hover:bg-red-500/10 flex items-center justify-center" title="Rechazar cotización">
        🚫
    </button>
@elseif($cot->estado === 'rechazada' && (!auth()->user() || auth()->user()->role !== 'invitado'))
    <button type="button" onclick="openAnularModal('{{ route('cotizaciones.reactivar', $cot) }}', true)" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 border-green-500/20 hover:bg-green-50/10 flex items-center justify-center" title="Reactivar cotización">
        ✅
    </button>
@endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center p-12 bg-white/30 dark:bg-slate-800/30">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="text-5xl opacity-80">📝</div>
                            <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No hay cotizaciones aún</h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Las cotizaciones creadas aparecerán aquí.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 px-4 pb-4">
        {{ $cotizaciones->links() }}
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof filterTable === 'function') {
            filterTable('search-cotizaciones', 'tabla-cotizaciones');
        }
    });
</script>
@endsection
