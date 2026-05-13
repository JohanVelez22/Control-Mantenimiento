@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Editar Técnico</h2>
    
    <form method="POST" action="{{ route('tecnicos.update', $tecnico->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Nombre Completo</label>
                <input type="text" name="nombre" value="{{ old('nombre', $tecnico->nombre) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Identificación (DNI/NIT)</label>
                <input type="text" name="identificacion" value="{{ old('identificacion', $tecnico->identificacion) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Especialidad</label>
                <select name="especialidad" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="Hardware" {{ old('especialidad', $tecnico->especialidad) == 'Hardware' ? 'selected' : '' }}>Hardware</option>
                    <option value="Software" {{ old('especialidad', $tecnico->especialidad) == 'Software' ? 'selected' : '' }}>Software</option>
                    <option value="Redes" {{ old('especialidad', $tecnico->especialidad) == 'Redes' ? 'selected' : '' }}>Redes</option>
                    <option value="General" {{ old('especialidad', $tecnico->especialidad) == 'General' ? 'selected' : '' }}>General</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Teléfono Móvil</label>
                <input type="text" name="movil" value="{{ old('movil', $tecnico->movil) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $tecnico->email) }}" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Dirección</label>
            <textarea name="direccion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">{{ old('direccion', $tecnico->direccion) }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Foto del Técnico</label>
            @if($tecnico->photo)
                <div class="mb-3">
                    <img src="{{ asset('storage/' . $tecnico->photo) }}" width="100" height="100" class="rounded-full object-cover border-2 border-gray-300 dark:border-gray-600">
                </div>
            @endif
            <input type="file" name="photo" accept="image/*" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('photo') border-red-500 @enderror">
            <p class="text-xs text-gray-500 mt-1">Sube una nueva imagen para actualizar la foto.</p>
            @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('tecnicos.index') }}" class="w-1/2 text-center bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">Cancelar</a>
            <button type="submit" class="w-1/2 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20">Actualizar Técnico</button>
        </div>
    </form>
</div>
@endsection
