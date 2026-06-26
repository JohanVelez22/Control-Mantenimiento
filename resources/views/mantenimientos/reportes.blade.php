@extends('layouts.app')

@section('content')
<div class="flex gap-4 mb-6 no-print">
 <a href="{{ route('reportes.financiero.diario') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💵 Informes Financieros</a>
 <a href="{{ route('mantenimientos.reportes') }}" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('electronicas.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Reporte de Electrónica</a>
 <a href="{{ route('stocks.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">📦 Informe Inventario</a>
</div>

<div class="glass-card p-6 mb-6">
 
 <!-- Encabezado y Botones -->
 <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
 <div>
 <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Mantenimientos</h2>
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
 <form id="filtros-mantenimiento" action="{{ route('mantenimientos.reportes') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end mb-8 p-5 glass-card no-print">
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
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Desde</label>
 <input type="date" name="fecha_desde" class="glass-input" value="{{ request('fecha_desde', date('Y-m-01')) }}">
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
 <input type="date" name="fecha_hasta" class="glass-input" value="{{ request('fecha_hasta', date('Y-m-d')) }}">
 </div>
   <div>
  <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Tipo/Rep</label>
  <select name="tipo_rep" class="glass-input no-search">
  <option value="todos">Todos</option>
  <option value="preventivo" {{ request('tipo_rep') == 'preventivo' ? 'selected' : '' }}>Preventivo</option>
  <option value="correctivo" {{ request('tipo_rep') == 'correctivo' ? 'selected' : '' }}>Correctivo</option>
  <option value="software" {{ request('tipo_rep') == 'software' ? 'selected' : '' }}>Software</option>
  <option value="hardware" {{ request('tipo_rep') == 'hardware' ? 'selected' : '' }}>Hardware</option>
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
 <a href="{{ route('mantenimientos.reportes') }}" class="btn-clean">
 🧹 Limpiar
 </a>
 <button type="submit" class="btn-primary">
 🌪️ Filtrar Reporte
 </button>
 </div>
 </form>

 <!-- Encabezado solo visible al imprimir -->
 <div class="print-header">
  <h2>⚙️ Reporte Detallado de Mantenimientos</h2>
  <p>Generado el: {{ date('d/m/Y h:i A') }} &nbsp;|&nbsp; Período: {{ request('fecha_desde', date('Y-m-01')) }} al {{ request('fecha_hasta', date('Y-m-d')) }}</p>
 </div>

 <!-- Tabla con Datos Independientes (vista en pantalla: como antes; impresión: clase reportes-tabla-imprimir) -->
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table reportes-tabla-imprimir w-full">
 <thead>
 <tr>
 <th class="w-20 text-center">Orden</th>
 <th class="text-center">Cliente</th>
 <th class="text-center">Equipo</th>
 <th class="text-center">Técnico</th>
 <th class="text-center">Tipo/Rep</th>
 <th class="text-center">Progreso</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-24">Entrada</th>
 <th class="text-center w-24">Salida</th>
 <th class="text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 @forelse($mantenimientos as $m)
 @php
    $isAnulado = !empty($m->anulado);
    $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $dimLight = $isAnulado ? 'opacity-60' : '';
 @endphp
 <tr>
 <td class="text-center font-bold whitespace-nowrap {{ $dim }}">
 <a href="{{ route('mantenimientos.index', ['locate' => $m->id]) }}" class="text-blue-500 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors no-print-link">
 {{ $m->id_orden }}
 </a>
 </td>
 <td class="{{ $dim }}">
 <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de clientes">
 <span class="text-slate-800 dark:text-white font-bold whitespace-nowrap group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">
 {{ $m->equipo->cliente->nombre ?? 'N/A' }}
 </span>
 <span class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 {{ $m->equipo->cliente->identificacion ?? '-' }}
 </span>
 </a>
 </td>
 
 <!-- Columna Equipo: Nombre arriba, Marca/Modelo abajo -->
 <td class="{{ $dim }}">
 <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de equipos">
 <span class="text-slate-800 dark:text-white font-bold whitespace-nowrap group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">{{ $m->equipo->nombre ?? 'N/A' }}</span>
 <span class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}) - 
 <span class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 {{ strtoupper($m->equipo->serie ?? '') }}
 </span></span>
 </a>
 </td>

 <td class="text-center font-medium text-sm {{ $dim }}">{{ $m->tecnico->nombre ?? 'N/A' }}</td>
 
 <!-- Columna Tipo con Colores (Azul/Verde) -->
 <td class="text-center {{ $dimLight }}">
  <span class="pill {{ $m->tipo == 'preventivo' ? 'pill-preventivo' : 'pill-correctivo' }}">
  {{ ucfirst($m->tipo) }}
  </span>
  <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mt-1 {{ $dim }}">{{ $m->reparacion }}</div>
  </td>
 
 <td class="text-center {{ $dimLight }}">
 @php
     $progreso = strtolower($m->estado ?? '');
     $pillClass = 'pill-pending';
     if(in_array($progreso, ['terminado', 'entregado'])) $pillClass = 'pill-done';
     elseif($progreso === 'preventivo') $pillClass = 'pill-preventivo';
     elseif($progreso === 'especialidad') $pillClass = 'pill-especialidad';
     elseif(in_array($progreso, ['en_proceso', 'reparado'])) $pillClass = 'pill-efectivo';
 @endphp
 <span class="pill {{ $pillClass }}">{{ ucfirst($m->estado) ?: '—' }}</span>
 </td>
 
 <td class="text-center">
 <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $isAnulado ? 'Anulado' : 'Activo' }}
 </span>
 </td>

 <td class="text-center font-medium text-sm {{ $dim }}">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</td>
 
 <!-- Salida Centrada -->
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
 @if($mantenimientos->count() > 0)
 <tfoot class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
 <tr>
 <td class="text-center font-bold">Total: {{ $mantenimientos->count() }}</td>
 <td colspan="8" class="text-right uppercase text-xs">Totales Filtrados:</td>
 <td class="text-center font-black text-green-600 dark:text-green-400">${{ number_format($mantenimientos->sum('costo'), 0, '', '.') }}</td>
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
/* ── Bloque de estilos solo para impresión ── */
@media print {
 @page { size: A4 portrait; margin: 15mm; }
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
  font-size: 7.5pt !important;
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
  padding: 4px 5px !important;
  font-size: 7pt !important;
  text-transform: uppercase !important;
  letter-spacing: 0.3px !important;
 }

 .reportes-tabla-imprimir td {
  border: 1px solid #aaa !important;
  padding: 3px 5px !important;
  background: #fff !important;
  color: #000 !important;
  vertical-align: middle !important;
 }

 .reportes-tabla-imprimir tbody tr:nth-child(even) td { background: #f5f5f5 !important; }
 .reportes-tabla-imprimir tbody tr { page-break-inside: avoid !important; }

 /* Pills / badges al imprimir */
 .reportes-tabla-imprimir span.pill,
 .reportes-tabla-imprimir .badge {
  display: inline-block !important;
  border: 1px solid #000 !important;
  border-radius: 3px !important;
  padding: 1px 4px !important;
  font-size: 6.5pt !important;
  font-weight: 700 !important;
  background: #eee !important;
  color: #000 !important;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
 }

 /* Columna Equipo: permitir wrap */
 .reportes-tabla-imprimir tbody td:nth-child(3) { white-space: normal !important; word-break: break-word !important; }

 /* Anulados */
 .reportes-tabla-imprimir tr.opacity-60 { opacity: 0.55 !important; }

 h2.print-title { text-align: center !important; font-size: 16pt !important; margin-bottom: 8px !important; color: #000 !important; }
}

/* Ocultar el encabezado de impresión en pantalla */
.print-header { display: none; }
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
        e.target.value = "";
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

  document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll("select.glass-input").forEach((el) => {
          if (el.tomselect) return;
          if (window.initGlassTomSelect) {
              window.initGlassTomSelect(el);
          }
      });
  });

  function exportarReporte(tipo, btn) {
      const form = document.getElementById('filtros-mantenimiento');
      const params = new URLSearchParams(new FormData(form));
      params.set('export', tipo);
      const url = window.location.pathname + '?' + params.toString();
      const fallbackName = 'Reporte_Mantenimientos_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
      
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
    @page { size: A4 portrait; margin: 15mm; }
    html, body { width: 100% !important; margin: 0 !important; padding: 0 !important; background: #fff !important; color: #000 !important; font-size: 9pt !important; }
    #ts-sidebar, #ts-topbar, .no-print, form, button, .pagination { display: none !important; }
    #main-wrapper, #ts-main { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .glass-card, .glass-panel, .container, .max-w-7xl, .mx-auto, .p-4, .p-6, .p-8, .shadow-lg, .shadow-xl { background: transparent !important; box-shadow: none !important; border: none !important; margin: 0 !important; padding: 0 !important; max-width: 100% !important; width: 100% !important; border-radius: 0 !important; }
    table { width: 100% !important; border-collapse: collapse !important; display: table !important; }
    tr { display: table-row !important; page-break-inside: avoid !important; }
    th, td { display: table-cell !important; border: 1px solid #ddd !important; padding: 6px !important; }
    .responsive-table td::before { display: none !important; }
    thead { display: table-header-group !important; }
    -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
}
</style>
@endsection
