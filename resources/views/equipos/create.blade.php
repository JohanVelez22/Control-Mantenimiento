@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Registrar Nuevo Equipo</h2>
    
    <form method="POST" action="{{ route('equipos.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Cliente Propietario</label>
                <select name="cliente_id" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Seleccione un cliente...</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }} - {{ $cliente->identificacion }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Nombre del Equipo (ej. PC Escritorio)</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('nombre') border-red-500 @enderror">
                @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Marca</label>
                <input type="text" name="marca" value="{{ old('marca') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('marca') border-red-500 @enderror">
                @error('marca') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Modelo</label>
                <input type="text" name="modelo" value="{{ old('modelo') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('modelo') border-red-500 @enderror">
                @error('modelo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Número de Serie</label>
                <input type="text" name="serie" value="{{ old('serie') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('serie') border-red-500 @enderror">
                @error('serie') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            
            {{-- El campo Estado ha sido eliminado conforme a la nueva estructura --}}
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Observaciones / Detalles</label>
            <textarea name="observacion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 h-24">{{ old('observacion') }}</textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('equipos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Guardar Equipo</button>
        </div>
    </form>
</div>
@endsection
