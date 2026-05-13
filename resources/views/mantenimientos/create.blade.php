@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Registrar Mantenimiento</h2>
    
    <form method="POST" action="{{ route('mantenimientos.store') }}" id="mantenimientoForm">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Equipo</label>
                <select name="equipo_id" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Seleccione...</option>
                    @foreach($equipos as $equipo)
                        <option value="{{ $equipo->id }}">{{ $equipo->nombre }} ({{ $equipo->marca }} {{ $equipo->modelo }}) {{ $equipo->serie}}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Técnico</label>
                <select name="tecnico_id" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="">Seleccione...</option>
                    @foreach($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id }}">{{ $tecnico->nombre }} ({{ $tecnico->identificacion}})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Fecha Entrada</label>
                <input type="date" name="fecha_entrada" value="{{ date('Y-m-d') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Fecha Salida</label>
                <input type="date" name="fecha_salida" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Tipo Mantenimiento</label>
                <select name="tipo" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="preventivo">Preventivo</option>
                    <option value="correctivo">Correctivo</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Tipo Reparación</label>
                <select name="reparacion" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="software">Software</option>
                    <option value="hardware">Hardware</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Costo ($)</label>
                <!-- Cambiado a type="text" para permitir puntos de miles visuales -->
                <input type="text" name="costo_visual" id="costo_visual" placeholder="0.00" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                <!-- Input oculto que enviará el número real (sin puntos) a la base de datos -->
                <input type="hidden" name="costo" id="costo_real">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Estado</label>
                <select name="estado" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="pendiente" selected>Pendiente</option>
                    <option value="terminado">Terminado</option>
                </select>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Descripción</label>
            <textarea name="descripcion" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 h-24 @error('descripcion') border-red-500 @enderror">{{ old('descripcion') }}</textarea>
            @error('descripcion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('mantenimientos.index') }}" class="w-1/2 text-center bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">Cancelar</a>
            <button type="submit" class="w-1/2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">Guardar</button>
        </div>
    </form>
</div>

<script>
    const inputVisual = document.getElementById('costo_visual');
    const inputReal = document.getElementById('costo_real');

    inputVisual.addEventListener('input', function(e) {
        // 1. Quitar todo lo que no sea número
        let value = e.target.value.replace(/\D/g, "");
        
        // 2. Formatear con puntos de miles
        if (value !== "") {
            // Guardamos el valor real para el servidor (ej: 1000)
            inputReal.value = value; 
            // Mostramos el valor con puntos (ej: 1.000)
            e.target.value = new Intl.NumberFormat('es-CO').format(value);
        } else {
            inputReal.value = "";
        }
    });

    // Antes de enviar, nos aseguramos de que el campo oculto tenga el valor correcto
    document.getElementById('mantenimientoForm').addEventListener('submit', function() {
        if(inputReal.value === "") inputReal.value = 0;
    });
</script>
@endsection
