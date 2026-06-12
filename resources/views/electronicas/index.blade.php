@extends('layouts.app')

@section('content')
<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">

    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
        <h2 class="text-2xl font-bold">⚡ Registros de Electrónica</h2>
        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('electronicas.index') }}" method="GET" class="flex gap-2 flex-wrap">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Cliente, dispositivo..." class="bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 rounded-xl px-4 py-2 text-sm font-semibold focus:outline-none w-48">
                <select name="estado" class="bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="terminado" {{ request('estado') == 'terminado' ? 'selected' : '' }}>Terminado</option>
                </select>
                <button type="submit" class="bg-gray-200 dark:bg-gray-700 px-3 py-2 rounded-xl text-sm hover:bg-gray-300 transition-colors">Filtrar</button>
            </form>
            @if(!auth()->user()->isInvitado())
                <a href="{{ route('electronicas.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm">
                    ➕ Nuevo Registro
                </a>
            @endif
        </div>
    </div>

    <!-- Tabla adaptable: horizontal en desktop, vertical (tarjeta) en móvil -->
    <div class="w-full">
        <table class="w-full text-left border-collapse block md:table responsive-table">
            <thead class="hidden md:table-header-group">
                <tr class="bg-gray-200 dark:bg-gray-700 text-center block md:table-row">
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Orden</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Cliente</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Dispositivo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Tipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Estado</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Días</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Costo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="block md:table-row-group">
                @forelse($electronicas as $e)
                <tr class="bg-white dark:bg-gray-800 md:bg-transparent md:hover:bg-gray-100 md:dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 md:border-none rounded-xl md:rounded-none mb-4 md:mb-0 block md:table-row shadow-sm md:shadow-none transition-colors">
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Orden:</span>
                        <span class="font-mono text-xs bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-300 px-2 py-0.5 rounded-lg">{{ $e->id_orden }}</span>
                    </td>
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Cliente:</span>
                        <span class="font-semibold">{{ $e->cliente }}</span>
                    </td>
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Dispositivo:</span>
                        <span>{{ $e->dispositivo }}{{ $e->marca ? ' ('.$e->marca.')' : '' }}</span>
                    </td>
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Tipo:</span>
                        <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold {{ $e->tipo === 'correctivo' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' }}">
                            {{ ucfirst($e->tipo) }}
                        </span>
                    </td>
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Estado:</span>
                        <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold {{ $e->estado === 'terminado' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                            {{ $e->estado === 'terminado' ? '✅ Terminado' : '⏳ Pendiente' }}
                        </span>
                    </td>
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Días:</span>
                        @php $dias = $e->dias_transcurridos; @endphp
                        <span class="font-bold {{ $dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-700 dark:text-gray-300') }}">
                            {{ $dias }} días
                        </span>
                    </td>
                    <td class="p-3 block md:table-cell flex justify-between items-center border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <span class="md:hidden font-bold text-gray-500">Costo:</span>
                        <span>${{ number_format($e->costo, 0, ',', '.') }}</span>
                    </td>
                    <td class="p-3 block md:table-cell bg-gray-50 dark:bg-gray-800/50 md:bg-transparent md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                        <div class="flex justify-end md:justify-center gap-2 flex-wrap">
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('electronicas.edit', $e->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">✏️ Editar</a>
                                <a href="{{ route('electronicas.factura', $e->id) }}" target="_blank" class="inline-flex items-center gap-1 bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">🖨️ Factura</a>
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('electronicas.destroy', $e->id) }}" method="POST" class="inline-block" data-confirm-delete="¿Eliminar el registro {{ $e->id_orden }}?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">🗑️ Eliminar</button>
                                    </form>
                                @endif
                            @else
                                <span class="text-gray-400 text-sm">👁️ Lectura</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="block md:table-row">
                    <td colspan="8" class="p-12 text-center block md:table-cell">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="text-6xl">⚡</div>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Sin registros de electrónica</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Registra el primer arreglo electrónico para comenzar a hacer seguimiento.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('electronicas.create') }}" class="inline-flex items-center gap-2 bg-blue-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/30">➕ Nuevo Registro</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $electronicas->appends(request()->query())->links() }}</div>
</div>
@endsection



