@extends('layouts.app')

@section('content')
<div class="flex gap-4 mb-6 no-print">
 <a href="{{ route('reportes.financiero.diario') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💵 Informes Financieros</a>
 <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('electronicas.reportes') }}" class="bg-purple-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">⚡ Reporte de Electrónica</a>
 <a href="{{ route('stocks.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">📦 Informe Inventario</a>
</div>

<div class="glass-card p-6 mb-6">
 
 <!-- Encabezado y Botones -->
 <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 no-print">
 <div>
 <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Electrónica</h2>
 </div>
 
 <div class="flex flex-wrap gap-2 no-print">
 <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
 <span>🖨️</span> Imprimir
 </button>
 <button type="button" onclick="exportarReporte('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
 <span>📊</span> Excel
 </button>
 <button type="button" onclick="exportarReporte('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
 <span>📄</span> PDF
 </button>
 </div>
 </div>

 <!-- Formulario de Filtros Independientes -->
 <form id="filtros-electronica" action="{{ route('electronicas.reportes') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end mb-8 p-5 glass-card no-print relative z-50">
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
 <select name="cliente_id" class="glass-input">
 <option value="todos">Todos los clientes</option>
 @foreach($clientes as $c)
 <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }} ({{ $c->identificacion }})</option>
 @endforeach
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Equipo</label>
 <select name="equipo_id" class="glass-input">
 <option value="todos">Todos los equipos</option>
 @foreach($equipos as $e)
 <option value="{{ $e->id }}" {{ request('equipo_id') == $e->id ? 'selected' : '' }}>{{ $e->nombre }} ({{ $e->modelo}}) {{ strtoupper($e->serie) }}</option>
 @endforeach
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Técnico</label>
 <select name="tecnico_id" class="glass-input">
 <option value="todos">Todos los técnicos</option>
 @foreach($tecnicos as $t)
 <option value="{{ $t->id }}" {{ request('tecnico_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
 @endforeach
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Registrado por</label>
 <select name="user_id" class="glass-input">
 <option value="todos">Cualquier usuario</option>
 @foreach($usuarios as $u)
 <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
 @endforeach
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Desde (Entrada)</label>
 <input type="date" name="fecha_inicio" class="glass-input" value="{{ request('fecha_inicio', date('Y-m-01')) }}">
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Hasta (Entrada)</label>
 <input type="date" name="fecha_fin" class="glass-input" value="{{ request('fecha_fin', date('Y-m-d')) }}">
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
 <select name="tipo" class="glass-input no-search">
 <option value="todos">Todos</option>
 <option value="preventivo" {{ request('tipo') == 'preventivo' ? 'selected' : '' }}>Preventivo</option>
 <option value="correctivo" {{ request('tipo') == 'correctivo' ? 'selected' : '' }}>Correctivo</option>
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Progreso</label>
 <select name="estado" class="glass-input no-search">
 <option value="todos">Todos</option>
 <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
 <option value="terminado" {{ request('estado') == 'terminado' ? 'selected' : '' }}>Terminado</option>
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Estado</label>
 <select name="anulado" class="glass-input no-search">
 <option value="todos" {{ request('anulado') === null || request('anulado') == 'todos' ? 'selected' : '' }}>Todos</option>
 <option value="activo" {{ request('anulado') == 'activo' ? 'selected' : '' }}>Activo</option>
 <option value="anulado" {{ request('anulado') == 'anulado' ? 'selected' : '' }}>Anulado</option>
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Costo Mínimo</label>
 <input type="text" id="min_cost_visual" class="glass-input" value="{{ request('min_cost') ? number_format(request('min_cost'), 0, ',', '.') : '' }}" placeholder="0">
 <input type="hidden" name="min_cost" id="min_cost" value="{{ request('min_cost') }}">
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Costo Máximo</label>
 <input type="text" id="max_cost_visual" class="glass-input" value="{{ request('max_cost') ? number_format(request('max_cost'), 0, ',', '.') : '' }}" placeholder="0">
 <input type="hidden" name="max_cost" id="max_cost" value="{{ request('max_cost') }}">
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Búsqueda Rápida</label>
 <input type="text" name="search" id="real_time_search" class="glass-input" value="{{ request('search') }}" placeholder="Orden, Cliente, Equipo...">
 </div>

 <div class="lg:col-span-4 flex justify-end gap-2 mt-2">
 <a href="{{ route('electronicas.reportes') }}" class="btn-clean">
 🧹 Limpiar
 </a>
 <button type="submit" class="btn-primary">
 🌪️ Filtrar Reporte
 </button>
 </div>
 </form>

  <!-- Encabezado solo visible al imprimir -->
  <div class="print-date hidden-screen">
   <p style="text-align: center; margin-top: 0; margin-bottom: 0; font-size: 10px; color: #4a5568;">Generado el: {{ date('d/m/Y h:i A') }} &nbsp;|&nbsp; Período: {{ request('fecha_inicio', date('Y-m-01')) }} al {{ request('fecha_fin', date('Y-m-d')) }}</p>
  </div>

 <!-- Tabla con Datos Independientes -->
 <div class="overflow-x-auto pb-2">
 <table class="ts-table table-electronica responsive-table reportes-tabla-imprimir w-full">
  <thead>
  <tr>
  <th class="w-20 text-center">Orden</th>
  <th class="text-center">Cliente</th>
  <th class="text-center">Equipo</th>
  <th class="text-center">Técnico</th>
  <th class="text-center">Tipo</th>
  <th class="text-center">Progreso</th>
  <th class="text-center">Estado</th>
  <th class="text-center w-24">Entrada</th>
  <th class="text-center w-24">Salida</th>
  <th class="text-center">Costo</th>
  </tr>
  </thead>
  <tbody>
  @forelse($registros as $m)
  @php
    $isAnulado = !empty($m->anulado);
    $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $dimLight = $isAnulado ? 'opacity-60' : '';
  @endphp
  <tr>
  <td class="text-center font-bold whitespace-nowrap {{ $dim }}">
  <a href="{{ route('electronicas.index', ['locate' => $m->id]) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors no-print-link">
  {{ $m->id_orden }}
  </a>
  </td>
  
  <td class="{{ $dim }}">
  <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de clientes">
  <span class="text-slate-800 dark:text-white font-bold whitespace-nowrap group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
  {{ $m->equipo->cliente->nombre ?? 'N/A' }}
  </span>
  <span class="text-[10px] text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $m->equipo->cliente->identificacion ?? '-' }}
  </span>
  </a>
  </td>

  <td class="{{ $dim }}">
  <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de equipos">
  <span class="text-slate-800 dark:text-white font-bold whitespace-nowrap group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $m->equipo->nombre ?? 'N/A' }}</span>
  <span class="text-[10px] text-gray-500 tracking-wider uppercase mt-0.5">({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}) - 
  <span class="text-[10px] text-gray-500 tracking-wider uppercase mt-0.5">
  {{ strtoupper($m->equipo->serie ?? '') }}
  </span></span>
  </a>
  </td>

  <td class="text-center font-medium text-sm {{ $dim }}">{{ $m->tecnico->nombre ?? 'N/A' }}</td>
  
  <td class="text-center {{ $dimLight }}">
  <span class="pill {{ $m->tipo == 'preventivo' ? 'pill-preventivo' : 'pill-correctivo' }}">
  {{ ucfirst($m->tipo) }}
  </span>
  </td>

  <td class="text-center {{ $dimLight }}">
  <span class="pill {{ $m->estado == 'terminado' ? 'pill-done' : 'pill-pending' }}">
  {{ ucfirst($m->estado) }}
  </span>
  </td>

  <td class="text-center">
  <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
  {{ $isAnulado ? 'Anulado' : 'Activo' }}
  </span>
  </td>

   <td class="text-center font-medium text-sm {{ $dim }}">
   {{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}
   @php 
   $fechaEntrada = \Carbon\Carbon::parse($m->fecha_entrada)->startOfDay();
   $fechaFin = $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->startOfDay() : \Carbon\Carbon::now()->startOfDay();
   $dias = $fechaEntrada->diffInDays($fechaFin);
   @endphp
   <div class="mt-1 text-xs font-bold {{ $dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400') }}">
   ({{ $dias }} d)
   </div>
   </td>
  <td class="text-center font-medium text-sm {{ $m->fecha_salida ? '' : 'text-gray-400 italic' }} {{ $dim }}">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</td>
  <td class="text-center font-black text-green-600 dark:text-green-400 {{ $dim }}">${{ number_format($m->costo, 0, '', '.') }}</td>
  </tr>
  @empty
  <tr>
      <td colspan="10" class="p-12 text-center bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm">
          <div class="flex flex-col items-center justify-center space-y-3">
              <div class="text-5xl opacity-80">📭</div>
              <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
              <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Intenta con otros filtros de búsqueda.</p>
          </div>
      </td>
  </tr>
  @endforelse
  </tbody>
  @if($registros->count() > 0)
  <tfoot class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
  <tr>
  <td colspan="2" class="text-center font-bold text-xs whitespace-nowrap">TOTAL: {{ $totales['cantidad'] }}</td>
  <td colspan="7" class="text-right uppercase text-xs">TOTAL FILTRADOS:</td>
  <td class="text-center font-bold text-xs text-green-600 dark:text-green-400">${{ number_format($totales['costo'], 0, '', '.') }}</td>
  </tr>
  </tfoot>
  @endif
 </table>
 </div>
 <div class="mt-4 no-print">
 {{ $registros->appends(request()->query())->links() }}
 </div>
</div>


<script>
  function formatInput(visualId, realId) {
    const inputVisual = document.getElementById(visualId);
    const inputReal = document.getElementById(realId);
    if (!inputVisual || !inputReal) return;

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

  formatInput('min_cost_visual', 'min_cost');
  formatInput('max_cost_visual', 'max_cost');

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

 document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll("select.glass-input").forEach((el) => {
          if (el.tomselect) return;
          if (window.initGlassTomSelect) {
              window.initGlassTomSelect(el);
          }
      });
  });

   function exportarReporte(tipo, btn) {
       const form = document.getElementById('filtros-electronica');
       const params = new URLSearchParams(new FormData(form));
       params.set('export', tipo);
       const url = window.location.pathname + '?' + params.toString();
       const fallbackName = 'Reporte_Electronica_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
       
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
<style>
@media print {
    @page {
        size: A4 landscape;
        margin: 6mm 6mm 8mm 6mm;
    }
    
    .no-print,
    #ts-sidebar,
    #ts-topbar,
    header,
    aside,
    nav,
    form,
    button,
    .btn,
    .pagination,
    a[href*="export"],
    .flex.gap-4.mb-6.no-print {
        display: none !important;
    }
    
    .print-date {
        display: block !important;
        text-align: center;
        margin-top: 2px !important;
        margin-bottom: 6px !important;
    }
    
    /* Disable flexbox layouts during print to prevent desktop viewport scaling and right-side clipping */
    .flex.min-h-screen,
    #main-wrapper {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        min-height: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    #ts-main,
    main {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        min-height: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 6mm 4mm !important; /* Force physical margins */
        box-sizing: border-box !important;
        box-shadow: none !important;
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
        font-size: 8pt !important;
        width: 100% !important;
        height: auto !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        counter-reset: page 0;
    }
    
    .glass-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        margin-bottom: 10px !important;
        padding: 0 !important;
    }
    
    table, .ts-table,
    th, td, tfoot td,
    thead th:first-child, thead th:last-child,
    tbody tr:last-child td:first-child, tbody tr:last-child td:last-child,
    tfoot tr:last-child td:first-child, tfoot tr:last-child td:last-child {
        border-radius: 0 !important;
    }

    table, .ts-table {
        display: table !important;
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 4px !important;
        margin-bottom: 10px !important;
        font-size: 8pt !important;
        background-color: #ffffff !important;
        background: #ffffff !important;
        box-shadow: none !important;
        filter: none !important;
    }
    
    thead {
        display: table-header-group !important;
    }
    
    tbody {
        display: table-row-group !important;
    }
    
    tfoot, .tfoot {
        display: table-footer-group !important;
        font-weight: bold !important;
    }
    
    tr {
        display: table-row !important;
        page-break-inside: avoid !important;
    }
    
    table th, .ts-table th, table td, .ts-table td, tfoot td, .tfoot td {
        display: table-cell !important;
        border: none !important;
        padding: 5px 6px !important;
        vertical-align: middle !important;
    }
    
    table tbody td, .ts-table tbody td {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    
    table th, .ts-table th, table thead th {
        background-color: #2d3748 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        text-transform: uppercase !important;
        font-size: 7.5pt !important;
    }
    
    table tbody tr:nth-child(even) td, .ts-table tbody tr:nth-child(even) td {
        background-color: #f7fafc !important;
    }
    
    table tfoot td, .ts-table tfoot td, table .tfoot td, .ts-table .tfoot td {
        background-color: #2d3748 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        font-size: 8pt !important;
    }
    
    tfoot td *, .tfoot td *, tfoot td span, .tfoot td span, tfoot td div, .tfoot td div, tfoot td strong, .tfoot td strong {
        display: inline !important;
        border: none !important;
        background: transparent !important;
        background-color: transparent !important;
        color: #ffffff !important;
        font-size: inherit !important;
        box-shadow: none !important;
    }
    
     span.pill, .badge, .pill, .pill-pending, .pill-done, .pill-preventivo, .pill-especialidad, .pill-efectivo, .pill-anulado, table td span, .ts-table td span, .reportes-tabla-imprimir td span {
        display: inline !important;
        border: none !important;
        background: none !important;
        background-color: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #000000 !important;
        font-weight: normal !important;
        text-transform: uppercase !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .no-print-emoji, table td span.no-print-emoji {
        display: none !important;
    }
    
    .responsive-table td::before {
        display: none !important;
    }
    
    h1, h2, h3 {
        color: #1a202c !important;
        font-weight: bold !important;
    }
    
    h1 {
        font-size: 16pt !important;
        margin-bottom: 5px !important;
    }
    
    h3 {
        font-size: 11pt !important;
        margin-bottom: 10px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding-bottom: 5px !important;
    }

    .print-footer {
        bottom: 2mm !important;
        left: 6mm !important;
        right: 6mm !important;
    }
}
</style>
@endsection
