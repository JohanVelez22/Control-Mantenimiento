@extends('layouts.app')

@section('content')
<div class="flex gap-4 mb-6 no-print">
    <a href="{{ route('reportes.financiero.diario') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💵 Informes Financieros</a>
    <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
    <a href="{{ route('electronicas.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Reporte de Electrónica</a>
    <a href="{{ route('stocks.reportes') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">📦 Informe Inventario</a>
</div>

<div class="glass-card p-6 mb-6">
    <!-- Encabezado y Botones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Inventario</h2>
        </div>
        
        <div class="flex flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-print">
                <span>🖨️</span> Imprimir
            </button>
            <button type="button" onclick="exportarReporte('excel', this)" class="btn-excel">
                <span>📊</span> Excel
            </button>
            <button type="button" onclick="exportarReporte('pdf', this)" class="btn-pdf">
                <span>📄</span> PDF
            </button>
        </div>
    </div>

    <!-- Formulario de Filtros -->
    <form id="filtros-stock" action="{{ route('stocks.reportes') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4 items-end mb-8 p-5 glass-card no-print">
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Proveedor</label>
            <select name="proveedor_id" class="glass-input">
                <option value="todos">Todos los proveedores</option>
                @foreach($proveedores as $prov)
                    <option value="{{ $prov->id }}" {{ request('proveedor_id') == $prov->id ? 'selected' : '' }}>{{ $prov->nombre_razon_social }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Categoría</label>
            <select name="categoria" class="glass-input">
                <option value="todos">Todas las categorías</option>
                @foreach($categorias as $cat)
                    <option value="{{ $cat }}" {{ request('categoria') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Subcategoría</label>
            <select name="subcategoria" class="glass-input">
                <option value="todos">Todas las subcategorías</option>
                @foreach($subcategorias as $sub)
                    <option value="{{ $sub }}" {{ request('subcategoria') == $sub ? 'selected' : '' }}>{{ $sub }}</option>
                @endforeach
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Desde</label>
            <input type="date" name="desde" value="{{ request('desde', date('Y-m-01')) }}" class="glass-input">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta', date('Y-m-d')) }}" class="glass-input">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Buscar Costo Por</label>
            <select name="price_type" class="glass-input no-search">
                <option value="precio_compra" {{ request('price_type') === 'precio_compra' ? 'selected' : '' }}>P. Compra</option>
                <option value="precio_venta" {{ request('price_type') === 'precio_venta' ? 'selected' : '' }}>P. Venta</option>
                <option value="precio_tecnico" {{ request('price_type') === 'precio_tecnico' ? 'selected' : '' }}>P. Técnico</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Costo Mínimo ($)</label>
            <input type="text" id="min_costo_visual" value="{{ request('min_costo') ? number_format(request('min_costo'), 0, '', '.') : '' }}" placeholder="0" class="glass-input">
            <input type="hidden" name="min_costo" id="min_costo" value="{{ request('min_costo') }}">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Costo Máximo ($)</label>
            <input type="text" id="max_costo_visual" value="{{ request('max_costo') ? number_format(request('max_costo'), 0, '', '.') : '' }}" placeholder="0" class="glass-input">
            <input type="hidden" name="max_costo" id="max_costo" value="{{ request('max_costo') }}">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Estado</label>
            <select name="estado" class="glass-input no-search">
                <option value="todos" {{ request('estado') === 'todos' ? 'selected' : '' }}>Todos</option>
                <option value="activo" {{ request('estado') === 'activo' || request('estado') === null ? 'selected' : '' }}>Activo</option>
                <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Búsqueda Rápida</label>
            <input type="text" name="search" id="real_time_search" class="glass-input" value="{{ request('search') }}" placeholder="Producto, código...">
        </div>

        <div class="md:col-span-4 lg:col-span-5 flex justify-end gap-2 mt-2">
            <a href="{{ route('stocks.reportes') }}" class="btn-clean">
                🧹 Limpiar
            </a>
            <button type="submit" class="btn-primary">
                🌪️ Filtrar Reporte
            </button>
        </div>
    </form>

    <!-- Encabezado solo visible al imprimir -->
    <div class="print-header">
        <h2>📦 Reporte Detallado de Inventario</h2>
        <p>Generado el: {{ date('d/m/Y h:i A') }}</p>
    </div>

    <!-- Tabla con Datos -->
    <div class="overflow-x-auto pb-2">
        <table class="ts-table responsive-table reportes-tabla-imprimir w-full">
            <thead>
                <tr>
                    <th class="text-left w-24">Código</th>
                    <th class="text-center">Producto</th>
                    <th class="text-center">Proveedor</th>
                    <th class="text-center w-20">Cant.</th>
                    <th class="text-center w-24">Estado</th>
                    <th class="text-right w-28">P. Compra</th>
                    <th class="text-center w-20">Utilidad</th>
                    <th class="text-right w-28">P. Venta</th>
                    <th class="text-right w-28">P. Técnico</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php
                    $isAnulado = !$stock->active;
                    $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
                    $dimLight = $isAnulado ? 'opacity-60' : '';
                @endphp
                <tr>
                    <td class="text-sm font-bold text-slate-500 dark:text-slate-400 {{ $dim }}">
                        {{ $stock->codigo ?? '-' }}
                    </td>
                    <td class="text-center {{ $dim }}">
                        <div class="flex flex-col items-center leading-tight">
                            <a href="{{ route('stocks.index', ['locate' => $stock->id]) }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                {{ $stock->producto }}
                            </a>
                        </div>
                        @if($stock->categoria || $stock->subcategoria)
                        <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-1">
                            {{ $stock->categoria ?? 'Sin Categoría' }} {{ $stock->subcategoria ? ' / ' . $stock->subcategoria : '' }}
                        </div>
                        @endif
                    </td>
                    <td class="text-sm font-medium text-center {{ $dim }}">
                        @if(!empty($stock->proveedor_id))
                            <a href="{{ route('proveedores.index', ['locate' => $stock->proveedor_id]) }}" class="flex flex-col items-center gap-0 group no-print-link transition-colors" title="Ver en tabla de proveedores">
                                <span class="text-slate-800 dark:text-white font-bold whitespace-nowrap group-hover:text-blue-600 transition-colors">
                                    {{ optional($stock->proveedor)->nombre_razon_social ?? 'Proveedor ' . $stock->proveedor_id }}
                                </span>
                                @if(optional($stock->proveedor)->identificacion)
                                <span class="font-bold text-[14px] text-gray-400 italic">
                                    {{ $stock->proveedor->identificacion }}
                                </span>
                                @endif
                            </a>
                        @else
                            {{ $stock->getRawOriginal('proveedor') ?: '-' }}
                        @endif
                    </td>
                    <td class="text-center {{ $dim }}">
                        <span class="pill {{ $stock->cantidad > 5 ? 'pill-done' : 'pill-anulado' }}">
                            {{ $stock->cantidad }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
                            {{ $isAnulado ? 'Inactivo' : 'Activo' }}
                        </span>
                    </td>
                    <td class="text-right font-medium {{ $dim }}">${{ number_format($stock->precio_compra, 0, '', '.') }}</td>
                    <td class="text-center font-bold text-green-600 dark:text-green-400 {{ $dim }}">
                        +{{ $stock->utilidad }}%
                    </td>
                    <td class="text-right font-black text-slate-800 dark:text-white {{ $dim }}">${{ number_format($stock->precio_venta, 0, '', '.') }}</td>
                    <td class="text-right font-black text-slate-800 dark:text-white {{ $dim }}">${{ number_format($stock->precio_tecnico, 0, '', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="p-12 text-center bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="text-5xl opacity-80">📦</div>
                            <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Intenta con otros filtros de búsqueda.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($stocks->count() > 0)
            <tfoot class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
                <tr>
                    <td colspan="2" class="text-left uppercase text-xs font-bold pt-2 pl-4">
                        <div class="flex items-center">
                            <span>Total: <span class="text-slate-800 dark:text-white">{{ $stocks->total() }}</span></span>
                        </div>
                    </td>
                    <td class="text-right uppercase text-xs pt-2">Totales:</td>
                    <td class="text-center font-bold pt-2">{{ $stocks->sum('cantidad') }}</td>
                    <td class="pt-2"></td>
                    <td class="text-center font-black text-slate-800 dark:text-slate-200 pt-2">${{ number_format($stocks->sum('precio_compra'), 0, '', '.') }}</td>
                    <td class="pt-2"></td>
                    <td class="text-center font-black text-slate-800 dark:text-slate-200 pt-2">${{ number_format($stocks->sum('precio_venta'), 0, '', '.') }}</td>
                    <td class="text-center font-black text-slate-800 dark:text-slate-200 pt-2">${{ number_format($stocks->sum('precio_tecnico'), 0, '', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    <div class="mt-4 no-print">
        {{ $stocks->appends(request()->query())->links() }}
    </div>
</div>

<style>
/* ── Bloque de estilos solo para impresión ── */
@media print {
    @page { size: A4 landscape; margin: 15mm; }
    .no-print, nav, aside, header, footer, form, button { display: none !important; }
    a { color: inherit !important; text-decoration: none !important; }
    .no-print-link { color: #000 !important; pointer-events: none !important; }

    body { background: #fff !important; color: #000 !important; margin: 0 !important; padding: 0 !important; font-size: 8pt !important; }
    .shadow, .rounded-lg { box-shadow: none !important; }
    .glass-card { background: #fff !important; border: none !important; box-shadow: none !important; backdrop-filter: none !important; margin: 0 !important; padding: 0 !important; }

    /* Encabezado visible al imprimir */
    .print-header { display: block !important; text-align: center; margin-bottom: 10mm; border-bottom: 2px solid #000; padding-bottom: 4mm; }
    .print-header h2 { font-size: 14pt !important; font-weight: 700; color: #000; margin-bottom: 2mm; }
    .print-header p  { font-size: 9pt; color: #444; }

    .reportes-tabla-imprimir {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 8pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .reportes-tabla-imprimir thead { display: table-header-group !important; }
    .reportes-tabla-imprimir tfoot { display: table-footer-group !important; }

    .reportes-tabla-imprimir th {
        background: #2d3748 !important;
        color: #fff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        border: 1px solid #000 !important;
        padding: 6px !important;
        font-size: 7.5pt !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
    }

    .reportes-tabla-imprimir td {
        border: 1px solid #aaa !important;
        padding: 5px !important;
        background: #fff !important;
        color: #000 !important;
        vertical-align: middle !important;
    }

    .reportes-tabla-imprimir tbody tr:nth-child(even) td { background: #f5f5f5 !important; }
    .reportes-tabla-imprimir tbody tr { page-break-inside: avoid !important; }

    /* Pills / badges al imprimir */
    .reportes-tabla-imprimir span.pill {
        display: inline-block !important;
        border: 1px solid #000 !important;
        border-radius: 3px !important;
        padding: 2px 4px !important;
        font-size: 7pt !important;
        font-weight: 700 !important;
        background: #eee !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .reportes-tabla-imprimir tr.opacity-60 { opacity: 0.6 !important; }
}

.print-header { display: none; }
</style>

<script>
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

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll("select.glass-input").forEach((el) => {
            if (el.tomselect) return;
            if (window.initGlassTomSelect) {
                window.initGlassTomSelect(el);
            }
        });
    });

    function formatInput(visualId, realId) {
        const inputVisual = document.getElementById(visualId);
        const inputReal = document.getElementById(realId);

        if (inputVisual && inputReal) {
            inputVisual.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value !== "") {
                    inputReal.value = value;
                    e.target.value = new Intl.NumberFormat('es-CO').format(value);
                } else {
                    inputReal.value = "";
                    e.target.value = "";
                }
            });
        }
    }

    formatInput('min_costo_visual', 'min_costo');
    formatInput('max_costo_visual', 'max_costo');

    function exportarReporte(tipo, btn) {
        const form = document.getElementById('filtros-stock');
        const params = new URLSearchParams(new FormData(form));
        params.set('export', tipo);
        const url = window.location.pathname + '?' + params.toString();
        const fallbackName = 'Reporte_Inventario_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
        
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span>...';
        
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Error al generar el reporte');
                let filename = fallbackName;
                const disposition = response.headers.get('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) { 
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                return response.blob().then(blob => ({ blob, filename }));
            })
            .then(({ blob, filename }) => {
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                a.remove();
            })
            .catch(error => {
                console.error(error);
                alert('Hubo un error al generar o descargar el reporte.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = origText;
            });
    }
</script>
@endsection
