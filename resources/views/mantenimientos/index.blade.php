@extends('layouts.app')

@section('content')
<style>
    /* Efecto para resaltar la fila cuando se llega por un enlace de anclaje */
    tr:target {
        background-color: rgba(59, 130, 246, 0.2) !important;
        outline: 2px solid #3b82f6;
    }
</style>

<script>
    // Función para centrar el elemento del anclaje en la pantalla
    function centerAnchor() {
        const hash = window.location.hash;
        if (hash) {
            const target = document.querySelector(hash);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    // Ejecutar al cargar la página y cuando cambie el anclaje
    window.addEventListener('load', centerAnchor);
    window.addEventListener('hashchange', centerAnchor);
</script>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Órdenes de Mantenimiento</h2>
        <a href="{{ route('mantenimientos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Nueva Orden</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-200 dark:bg-gray-700 text-center">
                <tr>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Orden</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Equipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Técnico</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Tipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Reparación</th>
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
                @foreach($mantenimientos as $m)
                <tr id="mantenimiento-{{ $m->id }}" class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center transition-colors duration-500">
                    <td class="p-3 border border-gray-300 dark:border-gray-500 whitespace-nowrap font-bold text-center">
                        <a href="#mantenimiento-{{ $m->id }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                            {{ $m->id_orden }}
                        </a>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex flex-col items-center gap-0">
                            <span class="text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $m->equipo->nombre ?? '-' }}
                            </span>
                            <span class="font-bold text-[13px] text-gray-400 italic whitespace-nowrap">
                                ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }})
                            </span>
                            <span class="text-gray-900 dark:text-gray-100 text-[13.5px] whitespace-nowrap">
                                {{ $m->equipo->serie ?? '' }}
                            </span>
                        </div>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->tecnico->nombre ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->tipo }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->reparacion }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->descripcion ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ number_format($m->costo, 2, '.', ',') }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        @php
                            $bgEstado = $m->estado === 'pendiente' ? 'bg-yellow-500' : 'bg-green-500';
                        @endphp
                        <span class="px-2 py-1 rounded text-white text-sm {{ $bgEstado }}">
                            {{ ucfirst($m->estado) }}
                        </span>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 whitespace-nowrap">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->user->name ?? '-' }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('mantenimientos.edit', $m->id) }}" class="text-yellow-500 hover:underline mr-2">Editar</a>
                            <form action="{{ route('mantenimientos.destroy', $m->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar orden?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline">Eliminar</button>
                            </form>
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
