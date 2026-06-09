@extends('layouts.app')

@section('content')
<style>
    /* Fila resaltada al llegar por ancla (#mantenimiento-id) */
    tr:target {
        background-color: rgba(59, 130, 246, 0.2) !important;
        outline: 2px solid #3b82f6;
    }
</style>

{{-- Modal de contraseña para anular --}}
<div id="pwd-anular-modal" class="fixed inset-0 z-[300] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-orange-300/40 dark:border-orange-700/50 rounded-2xl shadow-2xl p-6 max-w-sm w-full">
        <div class="text-center mb-4">
            <div class="text-5xl mb-2">🚫</div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Confirmar anulación</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tu contraseña para anular este registro.</p>
        </div>
        <form id="anular-pwd-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <input type="password" name="password_confirm" id="pwd-anular-input" required placeholder="Contraseña..."
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-orange-500 transition-all">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeAnularModal()" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-xl font-semibold hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/30">Anular</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
        <h2 class="text-2xl font-bold">Órdenes de Mantenimiento</h2>
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" id="search-mantenimientos" placeholder="🔍 Buscar..." class="search-input bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 text-sm font-semibold transition-all shadow-sm focus:outline-none w-48">
            @if(!auth()->user()->isInvitado())
                <a href="{{ route('mantenimientos.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
                    ➕ Nueva Orden
                </a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tabla-mantenimientos" class="w-full text-left border-collapse">
            <thead class="bg-gray-200 dark:bg-gray-700 text-center">
                <tr>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Orden</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Equipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Cliente</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Técnico</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Tipo / Rep.</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Observación</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Costo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Estado</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Entrada</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Salida</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Usuario</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mantenimientos as $m)
                <tr id="mantenimiento-{{ $m->id }}" class="scroll-mt-[6.5rem] hover:bg-gray-100 dark:hover:bg-gray-700 text-center transition-colors duration-500">
                    <td class="p-3 border border-gray-300 dark:border-gray-500 whitespace-nowrap font-bold text-center">
                        <a href="#mantenimiento-{{ $m->id }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $m->id_orden }}
                        </a>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group" title="Ver en tabla de equipos">
                            <span class="text-gray-900 dark:text-gray-100 font-bold whitespace-nowrap group-hover:underline">
                                {{ $m->equipo->nombre ?? '-' }}
                            </span>
                            <span class="font-bold text-[14px] text-gray-400 italic whitespace-nowrap">
                                ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }})
                            </span>
                            <span class="text-gray-900 dark:text-gray-100 text-[13.5px] whitespace-nowrap">
                                {{ $m->equipo->serie ?? '' }}
                            </span>
                        </a>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 max-w-[150px]">
                        <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group text-center" title="Ver en tabla de clientes">
                            <span class="text-gray-900 dark:text-gray-100 font-bold group-hover:underline">
                                {{ $m->equipo->cliente->nombre ?? '-' }}
                            </span>
                            <span class="font-bold text-[14px] text-gray-400 italic">
                                {{ $m->equipo->cliente->identificacion ?? '-' }}
                            </span>
                            {{-- Espacio extra para nivelar con la columna equipo --}}
                            <span class="text-[13.5px] select-none opacity-0">.</span>
                        </a>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->tecnico->nombre ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex flex-col items-center gap-0">
                            <span class="text-gray-900 dark:text-gray-100 font-bold capitalize">{{ $m->tipo }}</span>
                            <span class="text-[15px] text-gray-400 italic font-bold capitalize">({{ $m->reparacion }})</span>
                        </div>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->descripcion ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ number_format($m->costo, 2, '.', ',') }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        @if($m->estado === 'anulado')
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 border border-red-500/30 font-bold shadow-sm" title="Anulado">
                                🚫
                            </span>
                        @else
                            @php
                                $bgEstado = $m->estado === 'pendiente' ? 'bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border-yellow-500/30' : 'bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/30';
                            @endphp
                            <span class="px-2 py-1 rounded-md text-sm backdrop-blur-sm font-semibold border {{ $bgEstado }}">
                                {{ ucfirst($m->estado) }}
                            </span>
                        @endif
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 whitespace-nowrap">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->user->name ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            {{-- Ver Detalle (con abonos) --}}
                            <a href="{{ route('mantenimientos.show', $m->id) }}" class="inline-flex items-center gap-1 bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all text-sm" title="Ver detalle y abonos">
                                👁️ <span class="hidden md:inline">Ver</span>
                            </a>

                            @if($m->estado === 'terminado' && $m->fecha_salida)
                                <a href="{{ route('mantenimientos.factura', $m->id) }}" target="_blank" class="inline-flex items-center gap-1 bg-green-500/20 text-green-700 dark:text-green-400 border border-green-500/30 hover:bg-green-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-green-500/20 text-sm" title="Factura POS (requiere fecha de salida)">
                                    🖨️ <span class="hidden md:inline">Factura</span>
                                </a>
                            @elseif($m->estado === 'terminado')
                                <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-1 text-sm cursor-default" title="Agregue fecha de salida para generar la factura">
                                    🖨️ <span class="hidden md:inline">Factura</span>
                                </span>
                            @endif

                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('mantenimientos.edit', $m->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20 text-sm">
                                    ✏️ <span class="hidden md:inline">Editar</span>
                                </a>

                                <form action="{{ route('mantenimientos.duplicate', $m->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all text-sm" title="Duplicar orden">
                                        📋 <span class="hidden md:inline">Duplicar</span>
                                    </button>
                                </form>

                                @if($m->estado !== 'anulado')
                                    <button type="button" onclick="openAnularModal('{{ route('mantenimientos.anular', $m->id) }}')" class="inline-flex items-center gap-1 bg-orange-500/20 text-orange-700 dark:text-orange-400 border border-orange-500/30 hover:bg-orange-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all text-sm" title="Anular orden">
                                        🚫 <span class="hidden md:inline">Anular</span>
                                    </button>
                                @endif

                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('mantenimientos.destroy', $m->id) }}" method="POST" class="inline-block" data-confirm-delete="¿Eliminar la orden '{{ $m->id_orden }}'? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-red-500/20 text-sm">
                                            🗑️ <span class="hidden md:inline">Eliminar</span>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="text-6xl">🔍</div>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">No se encontraron mantenimientos</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Parece que aún no hay registros o el filtro no arrojó resultados.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('mantenimientos.create') }}" class="inline-flex items-center gap-2 bg-blue-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/30">
                                    ➕ Crear Primera Orden
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $mantenimientos->appends(request()->query())->links() }}
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => filterTable('search-mantenimientos', 'tabla-mantenimientos'));

    function openAnularModal(actionUrl) {
        const modal = document.getElementById('pwd-anular-modal');
        document.getElementById('anular-pwd-form').action = actionUrl;
        document.getElementById('pwd-anular-input').value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => document.getElementById('pwd-anular-input').focus(), 100);
    }
    function closeAnularModal() {
        const modal = document.getElementById('pwd-anular-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    // Cerrar con ESC
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAnularModal(); });
</script>
@endsection
