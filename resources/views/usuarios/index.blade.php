@extends('layouts.app')

@section('content')
<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold">Listado de Usuarios</h2>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
            ➕ Nuevo Usuario
        </a>
        @endif
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
                    <th class="p-3 border border-gray-300 dark:border-gray-500">ID</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Foto</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Nombre</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Email</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Rol</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Estado</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Creado</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 text-center">
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $u->id }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 text-center">
                        @if($u->photo)
                            <img src="{{ asset('storage/' . $u->photo) }}" width="40" height="40" class="rounded-full object-cover mx-auto">
                        @else
                            <span class="text-gray-400 text-xs">Sin foto</span>
                        @endif
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $u->name }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $u->email }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <span class="capitalize">{{ $u->role }}</span>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <span class="px-2 py-1 rounded-md text-xs font-semibold backdrop-blur-sm border {{ $u->active ? 'bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/30' : 'bg-red-500/20 text-red-700 dark:text-red-400 border-red-500/30' }}">
                            {{ $u->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $u->created_at->format('d/m/Y') }}</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <div class="flex justify-center items-center gap-2 flex-wrap">
                            @if(auth()->user()->isAdmin() || auth()->id() === $u->id)
                            <a href="{{ route('usuarios.edit', $u->id) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20 text-sm">
                                ✏️ Editar
                            </a>
                            @else
                            <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-1 text-sm cursor-default" title="Solo lectura">
                                👁️ <span class="hidden md:inline">Lectura</span>
                            </span>
                            @endif
                            
                            @if(auth()->user()->isAdmin() && $u->id !== auth()->id())
                            <form action="{{ route('usuarios.destroy', $u->id) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar usuario?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-3 py-1 font-semibold transition-all shadow-sm hover:shadow-red-500/20 text-sm">
                                    🗑️ Borrar
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>
@endsection
