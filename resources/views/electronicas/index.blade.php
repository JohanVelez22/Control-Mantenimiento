@extends('layouts.app')
@section('content')
<div class="glass-card p-6 md:p-8">

    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                <span class="text-3xl">⚡</span> Registros de Electrónica
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona las reparaciones de electrónica de consumo</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('electronicas.index') }}" method="GET" class="flex gap-2 flex-wrap">
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">🔍</span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cliente, dispositivo..." class="glass-input pl-9 w-40 sm:w-48 text-sm">
                </div>
                <select name="estado" class="glass-input w-auto text-sm font-semibold">
                    <option value="">Todos</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>⏳ Pendiente</option>
                    <option value="terminado" {{ request('estado') == 'terminado' ? 'selected' : '' }}>✅ Terminado</option>
                </select>
                <button type="submit" class="btn-ghost text-sm">Filtrar</button>
            </form>
            @if(!auth()->user()->isInvitado())
                <a href="{{ route('electronicas.create') }}" class="btn-primary shadow-purple-500/30 bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 border-none text-sm">
                    ➕ Nuevo Registro
                </a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
        <table class="ts-table responsive-table">
            <thead>
                <tr>
                    <th class="w-20 text-center">Orden</th>
                    <th>Cliente</th>
                    <th>Dispositivo</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Días</th>
                    <th class="text-right">Costo</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($electronicas as $e)
                <tr>
                    <td data-label="Orden:" class="text-center font-bold">
                        <span class="text-xs bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 px-2 py-0.5 rounded-lg font-mono">{{ $e->id_orden }}</span>
                    </td>
                    <td data-label="Cliente:" class="font-bold text-slate-800 dark:text-white">
                        {{ $e->cliente }}
                    </td>
                    <td data-label="Dispositivo:">
                        <div class="font-bold text-slate-800 dark:text-white leading-tight">{{ $e->dispositivo }}</div>
                        @if($e->marca)
                            <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mt-0.5">{{ $e->marca }}</div>
                        @endif
                    </td>
                    <td data-label="Tipo:" class="text-center">
                        <span class="pill {{ $e->tipo === 'correctivo' ? 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-opacity-20' : 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-opacity-20' }}">
                            {{ ucfirst($e->tipo) }}
                        </span>
                    </td>
                    <td data-label="Estado:" class="text-center">
                        <span class="pill {{ $e->estado === 'terminado' ? 'pill-done' : 'pill-pending' }}">
                            {{ $e->estado === 'terminado' ? '✅ Terminado' : '⏳ Pendiente' }}
                        </span>
                    </td>
                    <td data-label="Días:" class="text-center">
                        @php $dias = $e->dias_transcurridos; @endphp
                        <span class="font-bold {{ $dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-700 dark:text-gray-300') }}">
                            {{ $dias }} días
                        </span>
                    </td>
                    <td data-label="Costo:" class="text-right font-black text-purple-600 dark:text-purple-400">
                        ${{ number_format($e->costo, 0, ',', '.') }}
                    </td>
                    <td data-label="Acciones:" class="text-center">
                        <div class="flex justify-end md:justify-center gap-1.5 flex-wrap">
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('electronicas.edit', $e->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Editar">✏️</a>
                                <a href="{{ route('electronicas.factura', $e->id) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 hover:text-green-700 hover:bg-green-50/50" title="Imprimir Factura">🖨️</a>
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('electronicas.destroy', $e->id) }}" method="POST" class="inline" data-confirm-delete="¿Eliminar el registro {{ $e->id_orden }}?">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger px-2.5 py-1.5 text-xs" title="Eliminar">🗑️</button>
                                    </form>
                                @endif
                            @else
                                <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed">👁️ Lectura</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-16 text-center">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <div class="text-6xl drop-shadow-md mb-2">⚡</div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin registros de electrónica</h3>
                            <p class="text-gray-500 font-medium max-w-sm mb-4">Registra la primera reparación electrónica para iniciar el control.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('electronicas.create') }}" class="btn-primary shadow-purple-500/30 bg-gradient-to-r from-purple-500 to-indigo-500 hover:from-purple-600 hover:to-indigo-600 border-none">
                                    ➕ Nuevo Registro
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
        {{ $electronicas->appends(request()->query())->links() }}
    </div>
</div>
@endsection
