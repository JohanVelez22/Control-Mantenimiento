@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Editar Equipo</h2>
    
    <form method="POST" action="{{ route('equipos.update', $equipo->id) }}">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Cliente Propietario</label>
                <select name="cliente_id" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_id', $equipo->cliente_id) == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }} ({{ $cliente->identificacion }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Nombre del Equipo</label>
                <input type="text" name="nombre" value="{{ old('nombre', $equipo->nombre) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Marca</label>
                <input type="text" name="marca" value="{{ old('marca', $equipo->marca) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Modelo</label>
                <input type="text" name="modelo" value="{{ old('modelo', $equipo->modelo) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Número de Serie</label>
                <input type="text" name="serie" value="{{ old('serie', $equipo->serie) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            {{-- El campo de Estado ha sido eliminado --}}
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Observaciones / Detalles</label>
            <textarea name="observacion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 h-24">{{ old('observacion', $equipo->observacion) }}</textarea>
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('equipos.index') }}" class="w-1/2 text-center bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">Cancelar</a>
            <button type="submit" class="w-1/2 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20">
                Actualizar Equipo
            </button>
        </div>
    </form>
</div>
@endsection

