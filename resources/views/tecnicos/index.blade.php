@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Técnicos</h2>
        <a href="{{ route('tecnicos.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Nuevo Técnico</a>
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
                    <th class="p-3 border dark:border-gray-600">ID</th>
                    <th class="p-3 border dark:border-gray-600">Nombre</th>
                    <th class="p-3 border dark:border-gray-600">Identificación</th>
                    <th class="p-3 border dark:border-gray-600">Especialidad</th>
                    <th class="p-3 border dark:border-gray-600">Móvil</th>
                    <th class="p-3 border dark:border-gray-600">Email</th>
                    <th class="p-3 border dark:border-gray-600">Dirección</th>
                    <th class="p-3 border dark:border-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tecnicos as $tecnico)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->id }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->nombre }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->identificacion }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->especialidad }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->movil }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->email ?? '-' }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $tecnico->direccion ?? '-' }}</td>
                    <td class="p-3 border dark:border-gray-600">
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('tecnicos.edit', $tecnico->id) }}" class="text-yellow-500 hover:underline mr-2">Editar</a>
                            <form action="{{ route('tecnicos.destroy', $tecnico->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este técnico?');">
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
