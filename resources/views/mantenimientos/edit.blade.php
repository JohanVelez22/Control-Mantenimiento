@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
    <h2 class="text-2xl font-bold mb-6">Editar Mantenimiento</h2>
    
    <form method="POST" action="{{ route('mantenimientos.update', $mantenimiento->id) }}" id="mantenimientoForm">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">ID Orden</label>
                <input type="text" name="id_orden" value="{{ $mantenimiento->id_orden }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600" readonly>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Equipo</label>
                <select name="equipo_id" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    @foreach($equipos as $equipo)
                        <option value="{{ $equipo->id }}" {{ (old('equipo_id', $mantenimiento->equipo_id) == $equipo->id) ? 'selected' : '' }}>
                            {{ $equipo->nombre }} ({{ $equipo->marca }} {{ $equipo->modelo }}) {{ $equipo->serie}}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Técnico</label>
                <select name="tecnico_id" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    @foreach($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id }}" {{ (old('tecnico_id', $mantenimiento->tecnico_id) == $tecnico->id) ? 'selected' : '' }}>
                            {{ $tecnico->nombre }} ({{ $tecnico->identificacion}})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Fecha Entrada</label>
                <input type="date" name="fecha_entrada" value="{{ old('fecha_entrada', $mantenimiento->fecha_entrada) }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Fecha Salida</label>
                <input type="date" name="fecha_salida" value="{{ old('fecha_salida', $mantenimiento->fecha_salida) }}" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Tipo Mantenimiento</label>
                <select name="tipo" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="preventivo" {{ old('tipo', $mantenimiento->tipo) == 'preventivo' ? 'selected' : '' }}>Preventivo</option>
                    <option value="correctivo" {{ old('tipo', $mantenimiento->tipo) == 'correctivo' ? 'selected' : '' }}>Correctivo</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Tipo Reparación</label>
                <select name="reparacion" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="software" {{ old('reparacion', $mantenimiento->reparacion) == 'software' ? 'selected' : '' }}>Software</option>
                    <option value="hardware" {{ old('reparacion', $mantenimiento->reparacion) == 'hardware' ? 'selected' : '' }}>Hardware</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Costo ($)</label>
                <!-- Input Visual con el valor inicial formateado -->
                <input type="text" id="costo_visual" value="{{ number_format(old('costo', $mantenimiento->costo), 0, ',', '.') }}" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                <!-- Input oculto para enviar a la DB -->
                <input type="hidden" name="costo" id="costo_real" value="{{ old('costo', $mantenimiento->costo) }}">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Estado</label>
                <select name="estado" required class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600">
                    <option value="pendiente" {{ old('estado', $mantenimiento->estado) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="terminado" {{ old('estado', $mantenimiento->estado) == 'terminado' ? 'selected' : '' }}>Terminado</option>
                </select>
            </div>

            <div class="mb-4 md:col-span-2">
                <label class="block text-sm font-medium mb-2">Observación</label>
                <textarea name="descripcion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 h-24">{{ old('descripcion', $mantenimiento->descripcion) }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('mantenimientos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Cancelar</a>
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">Actualizar</button>
        </div>
    </form>
</div>

<script>
    const inputVisual = document.getElementById('costo_visual');
    const inputReal = document.getElementById('costo_real');

    inputVisual.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, "");
        if (value !== "") {
            inputReal.value = value;
            e.target.value = new Intl.NumberFormat('es-CO').format(value);
        } else {
            inputReal.value = "";
        }
    });

    document.getElementById('mantenimientoForm').addEventListener('submit', function() {
        if(inputReal.value === "") inputReal.value = 0;
    });
</script>
@endsection
