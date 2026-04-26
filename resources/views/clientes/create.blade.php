@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Registrar Nuevo Cliente</h2>
    
    <form method="POST" action="{{ route('clientes.store') }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Nombre Completo</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('nombre') border-red-500 @enderror">
                @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Identificación (DNI/NIT)</label>
                <input type="text" name="identificacion" value="{{ old('identificacion') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('identificacion') border-red-500 @enderror">
                @error('identificacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Teléfono Móvil</label>
                <input type="text" name="movil" value="{{ old('movil') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('movil') border-red-500 @enderror">
                @error('movil') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('email') border-red-500 @enderror">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Dirección</label>
            <textarea name="direccion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">{{ old('direccion') }}</textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('clientes.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Guardar Cliente</button>
        </div>
    </form>
</div>
@endsection
