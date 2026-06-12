@extends('layouts.app')

@section('content')
<div class="flex gap-4 mb-6 no-print">
    <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
    <a href="{{ route('reportes.index') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💰 Informes Financieros</a>
    <a href="{{ route('electronicas.reportes') }}" class="bg-purple-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">⚡ Módulo Electrónica</a>
</div>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6 mb-6">
    
    <!-- Encabezado y Botones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Electrónica</h2>
        </div>
        
        <div class="flex flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-gray-500/20">
                <span>🖨️</span> Imprimir
            </button>
        </div>
    </div>

    <!-- Formulario de Filtros Independientes -->
    <form action="{{ route('electronicas.reportes') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end mb-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg no-print">
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Técnico</label>
            <select name="tecnico_id" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">Todos los técnicos</option>
                @foreach($tecnicos as $t)
                    <option value="{{ $t->id }}" {{ request('tecnico_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Desde (Entrada)</label>
            <input type="date" name="fecha_inicio" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('fecha_inicio') }}">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Hasta (Entrada)</label>
            <input type="date" name="fecha_fin" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" value="{{ request('fecha_fin') }}">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Tipo</label>
            <select name="tipo" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">Todos</option>
                <option value="preventivo" {{ request('tipo') == 'preventivo' ? 'selected' : '' }}>Preventivo</option>
                <option value="correctivo" {{ request('tipo') == 'correctivo' ? 'selected' : '' }}>Correctivo</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Estado</label>
            <select name="estado" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                <option value="">Todos</option>
                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="terminado" {{ request('estado') == 'terminado' ? 'selected' : '' }}>Terminado</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Búsqueda Rápida (Tabla)</label>
            <input type="text" id="real_time_search" class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" placeholder="Orden, Cliente, Dispositivo...">
        </div>

        <div class="lg:col-span-2 flex justify-end gap-2 mt-2">
            <a href="{{ route('electronicas.reportes') }}" class="inline-flex items-center justify-center gap-2 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-yellow-500/20">
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
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Dispositivo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Técnico</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Tipo</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Estado</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Entrada</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500 text-center">Salida</th>
                    <th class="p-3 border border-gray-300 dark:border-gray-500">Costo</th>
                </tr>
            </thead>
            <tbody class="text-center text-sm">
                @forelse($registros as $m)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition border-b border-gray-300 dark:border-gray-500">
                    <td class="p-3 font-bold whitespace-nowrap border border-gray-300 dark:border-gray-500">
                        <span class="text-purple-600">{{ $m->id_orden }}</span>
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 font-bold">
                        {{ $m->cliente }}
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        {{ $m->dispositivo }} {{ $m->marca ? '('.$m->marca.')' : '' }}
                    </td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">{{ $m->tecnico->nombre ?? 'N/A' }}</td>
                    
                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <span class="px-2 py-1 rounded-md text-[11px] font-bold uppercase backdrop-blur-sm border 
                            {{ $m->tipo == 'preventivo' 
                                ? 'bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/30' 
                                : 'bg-sky-500/20 text-sky-700 dark:text-sky-400 border-sky-500/30' }}">
                            {{ $m->tipo }}
                        </span>
                    </td>

                    <td class="p-3 border border-gray-300 dark:border-gray-500">
                        <span class="px-2 py-1 rounded-md text-[11px] font-bold uppercase backdrop-blur-sm border 
                            {{ $m->estado == 'terminado' 
                                ? 'bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/30' 
                                : 'bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border-yellow-500/30' }}">
                            {{ $m->estado }}
                        </span>
                    </td>

                    <td class="p-3 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-500">
                        {{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}
                    </td>
                    <td class="p-3 text-center font-semibold border border-gray-300 dark:border-gray-500 {{ $m->fecha_salida ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 italic' }}">
                        {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}
                    </td>
                    <td class="p-3 font-bold text-green-600 border border-gray-300 dark:border-gray-500">${{ number_format($m->costo, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="p-8 text-center text-gray-500 italic bg-gray-50 dark:bg-gray-800">No hay registros con los filtros actuales.</td></tr>
                @endforelse
            </tbody>
            @if($registros->count() > 0)
            <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold text-center">
                <tr>
                    <td class="p-3 border border-gray-300 dark:border-gray-500">Total: {{ $totales['cantidad'] }}</td>
                    <td colspan="7" class="p-3 border border-gray-300 dark:border-gray-500 text-right uppercase text-xs">Total Filtrados:</td>
                    <td class="p-3 border border-gray-300 dark:border-gray-500 text-green-600">${{ number_format($totales['costo'], 2) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="mt-4 no-print">
        {{ $registros->appends(request()->query())->links() }}
    </div>
</div>

<style>
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

    .reportes-tabla-imprimir th *,
    .reportes-tabla-imprimir td * {
        color: #000 !important;
        -webkit-text-fill-color: #000 !important;
    }

    .reportes-tabla-imprimir tbody td:nth-child(5) span,
    .reportes-tabla-imprimir tbody td:nth-child(6) span {
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
    .reportes-tabla-imprimir td:first-child { border-left-width: 1px !important; }
    .reportes-tabla-imprimir thead tr th { border-top-width: 1px !important; background: #e8e8e8 !important; }
    .reportes-tabla-imprimir tbody tr { page-break-inside: avoid !important; }
    h2 { text-align: center !important; font-size: 16pt !important; margin-bottom: 12px !important; color: #000 !important; }
}
</style>
<script>
    document.getElementById('real_time_search').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.reportes-tabla-imprimir tbody tr');

        rows.forEach(row => {
            if (row.cells.length > 1) {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        });
    });
</script>
@endsection
