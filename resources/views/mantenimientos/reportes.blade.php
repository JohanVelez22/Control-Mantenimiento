@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6 mb-6">
    
    <!-- Encabezado y Botones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Mantenimientos</h2>
        </div>
        
        <div class="flex flex-wrap gap-2 no-print">
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2 shadow transition">
                <span>🖨️</span> Imprimir
            </button>
            <a href="{{ route('mantenimientos.reportes', array_merge(request()->all(), ['export' => 'excel'])) }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2 shadow transition">
                <span>📊</span> Excel
            </a>
            <a href="{{ route('mantenimientos.reportes', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2 shadow transition">
                <span>📄</span> PDF
            </a>
        </div>
    </div>

    <!-- Formulario de Filtros Independientes -->
    <form action="{{ route('mantenimientos.reportes') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end mb-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg no-print">
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Cliente</label>
            <select name="cliente_id" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Todos los clientes</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }} ({{ $c->identificacion }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Equipo</label>
            <select name="equipo_id" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Todos los equipos</option>
                @foreach($equipos as $e)
                    <option value="{{ $e->id }}" {{ request('equipo_id') == $e->id ? 'selected' : '' }}>{{ $e->nombre }} ({{ $e->modelo}}) {{ $e->serie }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Técnico</label>
            <select name="tecnico_id" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Todos los técnicos</option>
                @foreach($tecnicos as $t)
                    <option value="{{ $t->id }}" {{ request('tecnico_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Registrado por</label>
            <select name="user_id" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Cualquier usuario</option>
                @foreach($usuarios as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Desde</label>
            <input type="date" name="fecha_desde" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('fecha_desde') }}">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Hasta</label>
            <input type="date" name="fecha_hasta" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('fecha_hasta') }}">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Tipo Mantenimiento</label>
            <select name="tipo" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Todos</option>
                <option value="preventivo" {{ request('tipo') == 'preventivo' ? 'selected' : '' }}>Preventivo</option>
                <option value="correctivo" {{ request('tipo') == 'correctivo' ? 'selected' : '' }}>Correctivo</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Reparación</label>
            <select name="reparacion" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Todas</option>
                <option value="software" {{ request('reparacion') == 'software' ? 'selected' : '' }}>Software</option>
                <option value="hardware" {{ request('reparacion') == 'hardware' ? 'selected' : '' }}>Hardware</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Estado</label>
            <select name="estado" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="todos">Todos</option>
                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="terminado" {{ request('estado') == 'terminado' ? 'selected' : '' }}>Terminado</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Costo Mínimo</label>
            <input type="text" id="min_cost_visual" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('min_cost') ? number_format(request('min_cost'), 0, ',', '.') : '' }}" placeholder="0">
            <input type="hidden" name="min_cost" id="min_cost" value="{{ request('min_cost') }}">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Costo Máximo</label>
            <input type="text" id="max_cost_visual" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('max_cost') ? number_format(request('max_cost'), 0, ',', '.') : '' }}" placeholder="0">
            <input type="hidden" name="max_cost" id="max_cost" value="{{ request('max_cost') }}">
        </div>

        <div class="lg:col-span-4 flex justify-end gap-2">
            <a href="{{ route('mantenimientos.reportes') }}" class="bg-gray-400 text-white font-bold py-2 px-6 rounded text-sm">Limpiar</a>
            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded text-sm shadow">Filtrar Reporte</button>
        </div>
    </form>

    <!-- Tabla con Datos Independientes -->
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse border dark:border-gray-700">
            <thead class="bg-gray-100 dark:bg-gray-800 text-center text-[12px] font-bold uppercase">
                <tr>
                    <th class="p-3 border dark:border-gray-700">Orden</th>
                    <th class="p-3 border dark:border-gray-700">Cliente</th>
                    <th class="p-3 border dark:border-gray-700">Equipo</th>
                    <th class="p-3 border dark:border-gray-700">Técnico</th>
                    <th class="p-3 border dark:border-gray-700">Tipo</th>
                    <th class="p-3 border dark:border-gray-700">Reparación</th>
                    <th class="p-3 border dark:border-gray-700">Costo</th>
                    <th class="p-3 border dark:border-gray-700">Entrada</th>
                    <th class="p-3 border dark:border-gray-700 text-center">Salida</th>
                </tr>
            </thead>
            <tbody class="text-center text-sm">
                @forelse($mantenimientos as $m)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 border-b dark:border-gray-700 transition">
                    <td class="p-3 font-bold whitespace-nowrap">
                        <a href="{{ route('mantenimientos.index') }}#mantenimiento-{{ $m->id }}" class="text-blue-600 hover:text-blue-800 hover:underline no-print-link">
                            {{ $m->id_orden }}
                        </a>
                    </td>
                    <td class="p-3 font-semibold">{{ $m->equipo->cliente->nombre ?? 'N/A' }}</td>
                    
                    <!-- Columna Equipo: Nombre arriba, Marca/Modelo abajo -->
                    <td class="p-3">
                        <div class="font-medium">{{ $m->equipo->nombre ?? 'N/A' }}</div>
                        <div class="font-bold text-[13px] text-gray-400 italic whitespace-nowrap">
                            ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}) - 
                            <span class="not-italic text-gray-900 dark:text-gray-100 font-medium text-[13.5px]">
                                {{ $m->equipo->serie ?? '' }}
                            </span>
                        </div>
                    </td>

                    <td class="p-3">{{ $m->tecnico->nombre ?? 'N/A' }}</td>
                    
                    <!-- Columna Tipo con Colores (Azul/Verde) -->
                    <td class="p-3">
                        <span class="px-2 py-1 rounded text-[11px] font-bold uppercase 
                            {{ $m->tipo == 'preventivo' 
                                ? 'bg-green-100 text-white-700 dark:bg-green-900 dark:text-white-200' 
                                : 'bg-sky-100 text-white-700 dark:bg-sky-900 dark:text-white-200' }}">
                            {{ $m->tipo }}
                        </span>
                    </td>

                    <td class="p-3 capitalize">{{ $m->reparacion }}</td>
                    <td class="p-3 font-bold text-green-600">${{ number_format($m->costo, 2) }}</td>
                    <td class="p-3 text-gray-600 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}
                    </td>
                    
                    <!-- Salida Centrada -->
                    <td class="p-3 text-center font-semibold {{ $m->fecha_salida ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 italic' }}">
                        {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="p-8 text-center text-gray-500 italic bg-gray-50 dark:bg-gray-800">No hay registros con los filtros actuales.</td></tr>
                @endforelse
            </tbody>
            @if($mantenimientos->count() > 0)
            <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold text-center">
                <tr>
                    <td class="p-3 border dark:border-gray-700">Total: {{ $mantenimientos->count() }}</td>
                    <td colspan="5" class="p-3 border dark:border-gray-700 text-right uppercase text-xs">Totales Filtrados:</td>
                    <td class="p-3 border dark:border-gray-700 text-green-600">${{ number_format($mantenimientos->sum('costo'), 2) }}</td>
                    <td colspan="2" class="p-3 border dark:border-gray-700"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<style>
@media print {
    .no-print, nav, aside, header, footer, form, button { display: none !important; }
    a:not(.no-print-link) { display: none !important; }
    .no-print-link { color: black !important; text-decoration: none !important; cursor: default !important; }
    body { background: white !important; color: black !important; margin: 1cm !important; padding: 0 !important; }
    .shadow, .rounded-lg { box-shadow: none !important; border: none !important; }
    table { width: 100% !important; border: 1px solid #000 !important; font-size: 8pt !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #000 !important; padding: 4px !important; }
    h2 { text-align: center !important; font-size: 18pt !important; margin-bottom: 20px !important; }
}
</style>
<script>
    function formatInput(visualId, realId) {
        const inputVisual = document.getElementById(visualId);
        const inputReal = document.getElementById(realId);

        inputVisual.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, "");
            if (value !== "") {
                inputReal.value = value;
                e.target.value = new Intl.NumberFormat('es-CO').format(value);
            } else {
                inputReal.value = "";
            }
        });
    }

    formatInput('min_cost_visual', 'min_cost');
    formatInput('max_cost_visual', 'max_cost');
</script>
@endsection
