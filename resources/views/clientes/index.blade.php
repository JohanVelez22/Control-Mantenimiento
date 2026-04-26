@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Clientes</h2>
        <a href="{{ route('clientes.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Nuevo Cliente</a>
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
                    <th class="p-3 border dark:border-gray-600">Móvil</th>
                    <th class="p-3 border dark:border-gray-600">Email</th>
                    <th class="p-3 border dark:border-gray-600">Dirección</th>
                    <th class="p-3 border dark:border-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientes as $cliente)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
                    <td class="p-3 border dark:border-gray-600">{{ $cliente->id }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $cliente->nombre }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $cliente->identificacion }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $cliente->movil }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $cliente->email ?? '-' }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $cliente->direccion ?? '-' }}</td>
                    <td class="p-3 border dark:border-gray-600">
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('clientes.edit', $cliente->id) }}" class="text-yellow-500 hover:underline mr-2">Editar</a>
                            <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este cliente?');">
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
