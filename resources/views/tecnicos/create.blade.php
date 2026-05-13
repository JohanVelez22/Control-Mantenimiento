@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Registrar Nuevo Técnico</h2>
    
    <form method="POST" action="{{ route('tecnicos.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Nombre Completo</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Identificación (DNI/NIT)</label>
                <input type="text" name="identificacion" value="{{ old('identificacion') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Especialidad</label>
                <select name="especialidad" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Seleccione...</option>
                    <option value="Hardware" {{ old('especialidad') == 'Hardware' ? 'selected' : '' }}>Hardware</option>
                    <option value="Software" {{ old('especialidad') == 'Software' ? 'selected' : '' }}>Software</option>
                    <option value="Redes" {{ old('especialidad') == 'Redes' ? 'selected' : '' }}>Redes</option>
                    <option value="General" {{ old('especialidad') == 'General' ? 'selected' : '' }}>General</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Teléfono Móvil</label>
                <input type="text" name="movil" value="{{ old('movil') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Dirección</label>
            <textarea name="direccion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">{{ old('direccion') }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Foto del Técnico (Opcional)</label>
            <input type="file" name="photo" accept="image/*" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 @error('photo') border-red-500 @enderror">
            @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('tecnicos.index') }}" class="w-1/2 text-center bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">Cancelar</a>
            <button type="submit" class="w-1/2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">Guardar Técnico</button>
        </div>
    </form>
</div>
@endsection
