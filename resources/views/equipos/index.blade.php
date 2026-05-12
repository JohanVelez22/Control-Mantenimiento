@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Equipos</h2>
        @if(!auth()->user()->isInvitado())
            <a href="{{ route('equipos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Nuevo Equipo</a>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
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
                @foreach($equipos as $equipo)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
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
                        @if(!auth()->user()->isInvitado())
                            <a href="{{ route('equipos.edit', $equipo->id) }}" class="text-yellow-500 hover:underline mr-2">Editar</a>
                            @if(auth()->user()->isAdmin())
                                <form action="{{ route('equipos.destroy', $equipo->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este equipo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                                </form>
                            @endif
                        @else
                            <span class="text-gray-500 text-sm">Lectura</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
