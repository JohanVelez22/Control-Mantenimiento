@extends('layouts.app')

@section('content')
<style>
    /* Fila resaltada al llegar por ancla (#equipo-id) */
    tr:target {
        background-color: rgba(59, 130, 246, 0.2) !important;
        outline: 2px solid #3b82f6;
    }
</style>

<script>
    function centerAnchor() {
        const hash = window.location.hash;
        if (!hash) return;
        function scrollToRow() {
            const target = document.querySelector(hash);
            if (!target) return false;
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return true;
        }
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (!scrollToRow()) {
                    setTimeout(scrollToRow, 50);
                    setTimeout(scrollToRow, 200);
                }
            });
        });
    }
    window.addEventListener('load', centerAnchor);
</script>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <h2 class="text-2xl font-bold">Listado de Equipos</h2>
        <div class="flex flex-wrap items-center gap-2">
            <input type="text" id="search-equipos" placeholder="🔍 Buscar..." class="search-input bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 text-sm font-semibold transition-all shadow-sm focus:outline-none w-48">
            @if(!auth()->user()->isInvitado())
                <a href="{{ route('equipos.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
                    ➕ Nuevo Equipo
                </a>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tabla-equipos" class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700 text-center">
                    <th class="p-3 border border-gray-300 dark:border-gray-500">ID</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Equipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Serie</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Cliente</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Observación</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Usuario</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipos as $equipo)
                <tr id="equipo-{{ $equipo->id }}" class="scroll-mt-[6.5rem] hover:bg-gray-100 dark:hover:bg-gray-700 text-center transition-colors duration-500">
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $equipo->id }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $equipo->nombre }}
                            </span>
                            <span class="font-bold text-[14px] text-gray-400 italic whitespace-nowrap">
                                ({{ $equipo->marca }} {{ $equipo->modelo }})
                            </span>
                            <span class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $equipo->serie }}
                            </span>
                        </div>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $equipo->serie }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $equipo->cliente->nombre ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $equipo->observacion ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $equipo->user->name ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('equipos.edit', $equipo->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20 text-sm">
                                    ✏️ Editar
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('equipos.destroy', $equipo->id) }}" method="POST" class="inline-block" data-confirm-delete="¿Eliminar el equipo '{{ $equipo->nombre }}'? Esta acción no se puede deshacer.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-red-500/20 text-sm">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-1 text-sm cursor-default" title="Solo lectura">
                                    👁️ <span class="hidden md:inline">Lectura</span>
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="text-6xl">🖥️</div>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">No hay equipos registrados</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Comienza vinculando un equipo a un cliente para iniciar el seguimiento.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('equipos.create') }}" class="inline-flex items-center gap-2 bg-blue-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/30">
                                    ➕ Registrar Primer Equipo
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
        {{ $equipos->appends(request()->query())->links() }}
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-equipos', 'tabla-equipos'));</script>
@endsection
