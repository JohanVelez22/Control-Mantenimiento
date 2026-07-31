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
                <tr class="text-slate-700 dark:text-slate-200 font-semibold">
                    <th class="w-24 text-left px-4 py-3">Código</th>
                    <th class="text-left px-4 py-3">Tipo</th>
                    <th class="w-[20%] text-left px-4 py-3">Descripción</th>
                    <th class="text-left px-4 py-3">Cliente</th>
                    <th class="w-32 text-center px-4 py-3">Fecha</th>
                    <th class="w-32 text-center px-4 py-3">Validez</th>
                    <th class="w-32 text-right px-4 py-3">Total</th>
                    <th class="w-32 text-center px-4 py-3">Estado</th>
                    <th class="w-28 text-center px-4 py-3">Acciones</th>
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
                    $dim = $cot->anulado ? 'opacity-60 grayscale' : '';
                @endphp
                <tr id="cotizacion-{{ $cot->id }}" class="scroll-mt-[6.5rem] {{ $dim }}">
                    <td data-label="Código" class="font-bold text-slate-600 dark:text-slate-300">{{ $cot->codigo }}</td>
                    <td data-label="Tipo" class="font-bold text-indigo-600 dark:text-indigo-400 text-sm whitespace-nowrap">{{ $tipoStr }}</td>
                    <td data-label="Descripción" class="text-gray-600 dark:text-gray-300 text-xs font-medium max-w-[200px] truncate" title="{{ $descStr }}">{{ $descStr }}</td>
                    <td data-label="Cliente" class="font-bold text-slate-800 dark:text-white">{{ $cot->cliente->nombre ?? 'N/A' }}</td>
                    <td data-label="Fecha" class="text-center font-medium">{{ \Carbon\Carbon::parse($cot->fecha)->format('d/m/Y') }}</td>
                    <td data-label="Validez" class="text-center font-medium">{{ $cot->validez_dias }} días</td>
                    <td data-label="Total" class="text-right font-bold text-slate-800 dark:text-white">${{ number_format($cot->total, 0, '', '.') }}</td>
                    <td data-label="Estado" class="text-center">
                        <span class="pill {{ $cot->anulado ? 'pill-anulado' : ($cot->estado === 'aprobada' ? 'pill-done' : ($cot->estado === 'rechazada' ? 'pill-egreso' : 'pill-pending')) }}">
                            @if($cot->anulado)
                                Anulada
                            @elseif($cot->estado === 'rechazada')
                                Rechazada
                            @else
                                {{ ucfirst($cot->estado) }}
                            @endif
                        </span>
                    </td>
                    <td data-label="Acciones" class="text-center w-28">
                        <div class="actions-grid">
                            <a href="{{ route('cotizaciones.show', $cot) }}" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-indigo-600" title="Ver detalle">👁️</a>
                            
                            @if($cot->estado === 'aprobada')
                                <a href="{{ route('cotizaciones.pdf', $cot) }}" target="_blank" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-green-600" title="Imprimir PDF">🖨️</a>
                            @else
                                <span class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs opacity-40 cursor-not-allowed" title="Requiere estar aprobada para imprimir PDF">🖨️</span>
                            @endif

                            @if(!auth()->user()->isInvitado())
                                @if($cot->estado === 'pendiente' && !$cot->anulado)
                                    <a href="{{ route('cotizaciones.edit', $cot) }}" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-yellow-600" title="Editar">✏️</a>
                                @endif

                                <button type="button" onclick="openAnularModal('{{ route('cotizaciones.anular', $cot) }}', {{ $cot->anulado ? 'true' : 'false' }})" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs {{ $cot->anulado ? 'text-emerald-600' : 'text-red-600' }}" title="{{ $cot->anulado ? 'Reactivar cotización' : 'Anular cotización' }}">
                                    {{ $cot->anulado ? '✅' : '🚫' }}
                                </button>
                            @else
                                <span class="text-xs text-gray-400 font-medium">👁️ Lectura</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="p-16 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <div class="text-6xl drop-shadow-md mb-2">📝</div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin cotizaciones aún</h3>
                            <p class="text-gray-500 font-medium max-w-sm mb-4">Las cotizaciones registradas aparecerán aquí.</p>
                            @if(!auth()->user()->isInvitado())
                            <a href="{{ route('cotizaciones.create') }}" class="btn-primary">
                                ➕ Nueva Cotización
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 flex justify-end">
        {{ $cotizaciones->appends(request()->query())->links() }}
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