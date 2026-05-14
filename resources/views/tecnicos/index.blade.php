@extends('layouts.app')

@section('content')
<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Técnicos</h2>
        @if(!auth()->user()->isInvitado())
            <a href="{{ route('tecnicos.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
                ➕ Nuevo Técnico
            </a>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700 text-center">
                    <th class="p-3 border border-gray-300 dark:border-gray-500">ID</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Foto</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Nombre</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Identificación</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Especialidad</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Móvil</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Email</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Dirección</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tecnicos as $tecnico)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->id }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 text-center">
                        @if($tecnico->photo)
                            <img src="{{ asset('storage/' . $tecnico->photo) }}" width="40" height="40" class="rounded-full object-cover mx-auto">
                        @else
                            <span class="text-gray-400 text-xs">Sin foto</span>
                        @endif
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->nombre }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->identificacion }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->especialidad }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->movil }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->email ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $tecnico->direccion ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('tecnicos.edit', $tecnico->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20 text-sm">
                                    ✏️ Editar
                                </a>
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('tecnicos.destroy', $tecnico->id) }}" method="POST" class="inline-block m-0 p-0" onsubmit="return confirm('¿Eliminar este técnico?');">
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
                    <td colspan="9" class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="text-6xl">🛠️</div>
                            <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">No hay técnicos disponibles</h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Registra al personal técnico para asignar órdenes de mantenimiento.</p>
                            @if(!auth()->user()->isInvitado())
                                <a href="{{ route('tecnicos.create') }}" class="inline-flex items-center gap-2 bg-blue-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/30">
                                    ➕ Registrar Primer Técnico
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
        {{ $tecnicos->appends(request()->query())->links() }}
    </div>
</div>
@endsection
