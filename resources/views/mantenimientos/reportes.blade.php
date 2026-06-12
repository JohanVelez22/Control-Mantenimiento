@extends('layouts.app')

@section('content')
<div class="flex gap-4 mb-6 no-print">
    <a href="{{ route('mantenimientos.reportes') }}" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">⚙️ Reporte de Mantenimientos</a>
    <a href="{{ route('reportes.index') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💰 Informes Financieros</a>
    <a href="{{ route('electronicas.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Módulo Electrónica</a>
</div>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6 mb-6">
    
    <!-- Encabezado y Botones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Mantenimientos</h2>
        </div>
        
        <div class="flex flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">
                <span>🖨️</span> Imprimir
            </button>
            <a href="{{ route('mantenimientos.reportes', array_merge(request()->all(), ['export' => 'excel'])) }}" class="inline-flex items-center gap-2 bg-green-500/20 text-green-700 dark:text-green-400 border border-green-500/30 hover:bg-green-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-green-500/20">
                <span>📊</span> Excel
            </a>
            <a href="{{ route('mantenimientos.reportes', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="inline-flex items-center gap-2 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-red-500/20">
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

        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Búsqueda Rápida (Tiempo Real)</label>
            <input type="text" name="search" id="real_time_search" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('search') }}" placeholder="Orden, Cliente, Equipo...">
        </div>

        <div class="lg:col-span-4 flex justify-end gap-2 mt-2">
            <a href="{{ route('mantenimientos.reportes') }}" class="inline-flex items-center justify-center gap-2 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20">
                <span>🧹</span> Limpiar
            </a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
                <span>🔍</span> Filtrar Reporte
            </button>
        </div>
    </form>

    <!-- Tabla con Datos Independientes (vista en pantalla: como antes; impresión: clase reportes-tabla-imprimir) -->
    <div class="overflow-x-auto">
        <table class="reportes-tabla-imprimir w-full text-left border-collapse border border-gray-300 dark:border-gray-500">
            <thead class="bg-gray-200 dark:bg-gray-700 text-center text-[12px] font-bold uppercase">
                <tr>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Orden</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Cliente</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Equipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Técnico</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Tipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Reparación</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Costo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Entrada</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 text-center">Salida</th>
                </tr>
            </thead>
            <tbody class="text-center text-sm">
                @forelse($mantenimientos as $m)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition border-b border-gray-300 dark:border-gray-500">
                    <td class="p-3 font-bold whitespace-nowrap border border-gray-300 dark:border-gray-500">
                        <a href="{{ route('mantenimientos.index', ['locate' => $m->id]) }}" class="text-blue-600 hover:text-blue-800 hover:underline no-print-link">
                            {{ $m->id_orden }}
                        </a>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de clientes">
                            <span class="text-gray-900 dark:text-gray-100 font-bold whitespace-nowrap group-hover:underline">
                                {{ $m->equipo->cliente->nombre ?? 'N/A' }}
                            </span>
                            <span class="font-bold text-[14px] text-gray-400 italic">
                                {{ $m->equipo->cliente->identificacion ?? '-' }}
                            </span>
                        </a>
                    </td>
                    
                    <!-- Columna Equipo: Nombre arriba, Marca/Modelo abajo -->
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de equipos">
                            <div class="font-bold text-gray-900 dark:text-gray-100 group-hover:underline">{{ $m->equipo->nombre ?? 'N/A' }}</div>
                            <div class="font-bold text-[14px] text-gray-400 italic">
                                ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}) - 
                                <span class="not-italic text-gray-900 dark:text-gray-100 font-medium text-[13.5px]">
                                    {{ $m->equipo->serie ?? '' }}
                                </span>
                            </div>
                        </a>
                    </td>

                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->tecnico->nombre ?? 'N/A' }}</td>
                    
                    <!-- Columna Tipo con Colores (Azul/Verde) -->
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <span class="px-2 py-1 rounded-md text-[11px] font-bold uppercase backdrop-blur-sm border 
                            {{ $m->tipo == 'preventivo' 
                                ? 'bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/30' 
                                : 'bg-sky-500/20 text-sky-700 dark:text-sky-400 border-sky-500/30' }}">
                            {{ $m->tipo }}
                        </span>
                    </td>

                    <td class="p-3 capitalize border border-gray-300 dark:border-gray-500">{{ $m->reparacion }}</td>
                    <td class="p-3 font-bold text-green-600 border border-gray-300 dark:border-gray-500">${{ number_format($m->costo, 2) }}</td>
                    <td class="p-3 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-500">
                        {{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}
                    </td>
                    
                    <!-- Salida Centrada -->
                    <td class="p-3 text-center font-semibold border border-gray-300 dark:border-gray-500 {{ $m->fecha_salida ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 italic' }}">
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
                    <td class="p-3 border border-gray-300 dark:border-gray-500">Total: {{ $mantenimientos->count() }}</td>
                    <td colspan="5" class="p-3 border border-gray-300 dark:border-gray-500 text-right uppercase text-xs">Totales Filtrados:</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 text-green-600">${{ number_format($mantenimientos->sum('costo'), 2) }}</td>
                    <td colspan="2" class="p-3 border border-gray-300 dark:border-gray-500"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="mt-4 no-print">
        {{ $mantenimientos->appends(request()->query())->links() }}
    </div>
</div>

<style>
/* Solo al usar "Imprimir" (window.print): cuadrícula uniforme; en pantalla no cambia el aspecto Tailwind */
@media print {
    .no-print, nav, aside, header, footer, form, button { display: none !important; }
    a:not(.no-print-link) { display: none !important; }
    .no-print-link { color: #000 !important; text-decoration: none !important; cursor: default !important; }
    body { background: #fff !important; color: #000 !important; margin: 12mm !important; padding: 0 !important; }
    .shadow, .rounded-lg { box-shadow: none !important; border: none !important; }

    .reportes-tabla-imprimir {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        font-size: 8pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .reportes-tabla-imprimir thead { display: table-header-group !important; }
    .reportes-tabla-imprimir tfoot { display: table-footer-group !important; }

    .reportes-tabla-imprimir th,
    .reportes-tabla-imprimir td {
        border-style: solid !important;
        border-color: #000 !important;
        border-width: 0 1px 1px 0 !important;
        padding: 5px 4px !important;
        background: #fff !important;
        color: #000 !important;
        vertical-align: middle !important;
        overflow: visible !important;
    }

    /* Texto legible al imprimir (evita serial “invisible” por clases dark: en fondo blanco) */
    .reportes-tabla-imprimir th *,
    .reportes-tabla-imprimir td * {
        color: #000 !important;
        -webkit-text-fill-color: #000 !important;
    }

    /* Columna Equipo (3): permitir salto de línea para que salga marca/modelo y serial completo */
    .reportes-tabla-imprimir tbody td:nth-child(3),
    .reportes-tabla-imprimir tbody td:nth-child(3) div {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
    }

    /* Columna Tipo (5): evitar recorte del badge correctivo/preventivo */
    .reportes-tabla-imprimir tbody td:nth-child(5) {
        white-space: normal !important;
        vertical-align: middle !important;
    }
    .reportes-tabla-imprimir tbody td:nth-child(5) span {
        display: inline-block !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        padding: 2px 5px !important;
        margin: 0 !important;
        font-size: 7pt !important;
        line-height: 1.2 !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        border: 1px solid #000 !important;
        border-radius: 2px !important;
        background: #eee !important;
        backdrop-filter: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .reportes-tabla-imprimir th:first-child,
    .reportes-tabla-imprimir td:first-child {
        border-left-width: 1px !important;
    }

    .reportes-tabla-imprimir thead tr th {
        border-top-width: 1px !important;
        background: #e8e8e8 !important;
    }

    .reportes-tabla-imprimir tbody tr {
        page-break-inside: avoid !important;
    }

    h2 { text-align: center !important; font-size: 16pt !important; margin-bottom: 12px !important; color: #000 !important; }
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

    // Filtrado en tiempo real de la tabla (Cliente-side)
    document.getElementById('real_time_search').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.reportes-tabla-imprimir tbody tr');

        rows.forEach(row => {
            if (row.cells.length > 1) { // Evitar la fila de "No hay registros"
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        });
    });
</script>
@endsection
