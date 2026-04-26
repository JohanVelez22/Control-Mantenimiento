@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Usuarios</h2>
        <a href="{{ route('usuarios.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">+ Nuevo Usuario</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-200 dark:bg-gray-700 text-center">
                <tr>
                    <th class="p-3 border dark:border-gray-600">ID</th>
                    <th class="p-3 border dark:border-gray-600">Nombre</th>
                    <th class="p-3 border dark:border-gray-600">Email</th>
                    <th class="p-3 border dark:border-gray-600">Rol</th>
                    <th class="p-3 border dark:border-gray-600">Estado</th>
                    <th class="p-3 border dark:border-gray-600">Creado</th>
                    <th class="p-3 border dark:border-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
                    <td class="p-3 border dark:border-gray-600">{{ $u->id }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $u->name }}</td>
                    <td class="p-3 border dark:border-gray-600">{{ $u->email }}</td>
                    <td class="p-3 border dark:border-gray-600">
                        <span class="capitalize">{{ $u->role }}</span>
                    </td>
                    <td class="p-3 border dark:border-gray-600">
                        <span class="px-2 py-1 rounded text-white text-xs {{ $u->active ? 'bg-green-500' : 'bg-gray-500' }}">
                            {{ $u->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="p-3 border dark:border-gray-600">{{ $u->created_at->format('d/m/Y') }}</td>
                    <td class="p-3 border dark:border-gray-600">
                        <a href="{{ route('usuarios.edit', $u->id) }}" class="text-yellow-500 hover:underline mr-3 font-medium">Editar</a>
                        
                        @if($u->id !== auth()->id())
                        <form action="{{ route('usuarios.destroy', $u->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar este usuario?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:underline font-medium">Eliminar</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
