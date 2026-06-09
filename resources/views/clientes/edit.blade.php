@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Editar Cliente</h2>
    
    <form method="POST" action="{{ route('clientes.update', $cliente->id) }}">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Nombre Completo</label>
                <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('nombre') border-red-500 @enderror">
                @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Identificación (DNI/NIT)</label>
                <input type="text" name="identificacion" value="{{ old('identificacion', $cliente->identificacion) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('identificacion') border-red-500 @enderror">
                @error('identificacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Teléfono Móvil</label>
                <input type="tel" pattern="[\d\+\-\s\(\)]+" name="movil" value="{{ old('movil', $cliente->movil) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('movil') border-red-500 @enderror">
                @error('movil') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $cliente->email) }}" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('email') border-red-500 @enderror">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Dirección</label>
            <textarea name="direccion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">{{ old('direccion', $cliente->direccion) }}</textarea>
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('clientes.index') }}" class="w-1/2 text-center bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">Cancelar</a>
            <button type="submit" class="w-1/2 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20">Actualizar Cliente</button>
        </div>
    </form>
</div>
@endsection


