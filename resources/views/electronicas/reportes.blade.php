@extends('layouts.app')

@section('content')
<div class="flex gap-4 mb-6 no-print">
 <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('reportes.financiero.diario') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💵 Informes Financieros</a>
 <a href="{{ route('electronicas.reportes') }}" class="bg-purple-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">⚡ Módulo Electrónica</a>
</div>

<div class="glass-card p-6 mb-6">
 
 <!-- Encabezado y Botones -->
 <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
 <div>
 <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Electrónica</h2>
 </div>
 
 <div class="flex flex-wrap gap-2 no-print">
 <button type="button" onclick="window.print()" class="btn-print">
 <span>🖨️</span> Imprimir
 </button>
 <button type="submit" form="filtros-electronica" name="export" value="excel" class="btn-excel">
 <span>📊</span> Excel
 </button>
 <button type="submit" form="filtros-electronica" name="export" value="pdf" class="btn-pdf">
 <span>📄</span> PDF
 </button>
 </div>
 </div>

 <!-- Formulario de Filtros Independientes -->
 <form id="filtros-electronica" action="{{ route('electronicas.reportes') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end mb-8 p-5 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm no-print">
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
 <option value="{{ $e->id }}" {{ request('equipo_id') == $e->id ? 'selected' : '' }}>{{ $e->nombre }} ({{ $e->modelo}}) {{ $e->serie }}</option>
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
 <select name="tipo" class="glass-input">
 <option value="todos">Todos</option>
 <option value="preventivo" {{ request('tipo') == 'preventivo' ? 'selected' : '' }}>Preventivo</option>
 <option value="correctivo" {{ request('tipo') == 'correctivo' ? 'selected' : '' }}>Correctivo</option>
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Progreso</label>
 <select name="estado" class="glass-input">
 <option value="todos">Todos</option>
 <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
 <option value="terminado" {{ request('estado') == 'terminado' ? 'selected' : '' }}>Terminado</option>
 </select>
 </div>
 <div>
 <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Estado</label>
 <select name="anulado" class="glass-input">
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

 <div class="lg:col-span-1 flex justify-end gap-2 mt-2">
 <a href="{{ route('electronicas.reportes') }}" class="btn-clean">
 🧹 Limpiar
 </a>
 <button type="submit" class="btn-primary">
 🌪️ Filtrar Reporte
 </button>
 </div>
 </form>

 <!-- Encabezado solo visible al imprimir -->
 <div class="print-header">
  <h2>⚡ Reporte Detallado de Electrónica</h2>
  <p>Generado el: {{ date('d/m/Y h:i A') }} &nbsp;|&nbsp; Período: {{ request('fecha_inicio', date('Y-m-01')) }} al {{ request('fecha_fin', date('Y-m-d')) }}</p>
 </div>

 <!-- Tabla con Datos Independientes -->
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
  <a href="{{ route('electronicas.index', ['locate' => $m->id]) }}" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 hover:underline transition-colors no-print-link">
  {{ $m->id_orden }}
  </a>
  </td>
  
  <td class="{{ $dim }}">
  <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de clientes">
  <span class="text-gray-900 dark:text-gray-100 font-bold whitespace-nowrap group-hover:underline">
  {{ $m->equipo->cliente->nombre ?? 'N/A' }}
  </span>
  <span class="font-bold text-[14px] text-gray-400 italic">
  {{ $m->equipo->cliente->identificacion ?? '-' }}
  </span>
  </a>
  </td>

  <td class="{{ $dim }}">
  <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="flex flex-col items-center gap-0 hover:opacity-75 transition-opacity group no-print-link" title="Ver en tabla de equipos">
  <span class="text-gray-900 dark:text-gray-100 font-bold whitespace-nowrap group-hover:underline">{{ $m->equipo->nombre ?? 'N/A' }}</span>
  <span class="font-bold text-[14px] text-gray-400 italic">({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}) - 
  <span class="not-italic text-gray-900 dark:text-gray-100 font-medium text-[13.5px]">
  {{ $m->equipo->serie ?? '' }}
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

    <td class="text-center font-medium text-sm {{ $dim }}">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</td>
  <td class="text-center font-medium text-sm {{ $m->fecha_salida ? '' : 'text-gray-400 italic' }} {{ $dim }}">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</td>
  <td class="text-center font-black text-green-600 dark:text-green-400 {{ $dim }}">${{ number_format($m->costo, 2) }}</td>
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
  <td class="text-center font-bold">Total: {{ $totales['cantidad'] }}</td>
  <td colspan="8" class="text-right uppercase text-xs">Total Filtrados:</td>
  <td class="text-center font-black text-green-600 dark:text-green-400">${{ number_format($totales['costo'], 2) }}</td>
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
 a { color: inherit !important; text-decoration: none !important; }
 body { background: #fff !important; color: #000 !important; margin: 10mm !important; padding: 0 !important; font-size: 8pt !important; }
 .shadow, .rounded-lg { box-shadow: none !important; }
 .glass-card { background: #fff !important; border: none !important; box-shadow: none !important; backdrop-filter: none !important; }

 .print-header { display: block !important; text-align: center; margin-bottom: 8mm; border-bottom: 2px solid #553c9a; padding-bottom: 4mm; }
 .print-header h2 { font-size: 14pt !important; font-weight: 700; color: #553c9a; margin-bottom: 2mm; }
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
  background: #553c9a !important;
  color: #fff !important;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
  border: 1px solid #44337a !important;
  padding: 4px 5px !important;
  font-size: 7pt !important;
  text-transform: uppercase !important;
 }

 .reportes-tabla-imprimir td {
  border: 1px solid #aaa !important;
  padding: 3px 5px !important;
  background: #fff !important;
  color: #000 !important;
  vertical-align: middle !important;
 }

 .reportes-tabla-imprimir tbody tr:nth-child(even) td { background: #faf5ff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
 .reportes-tabla-imprimir tbody tr { page-break-inside: avoid !important; }

 .reportes-tabla-imprimir span.pill,
 .reportes-tabla-imprimir .badge {
  display: inline-block !important;
  border: 1px solid #555 !important;
  border-radius: 3px !important;
  padding: 1px 4px !important;
  font-size: 6.5pt !important;
  font-weight: 700 !important;
  background: #eee !important;
  color: #000 !important;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
 }

 .reportes-tabla-imprimir tbody td:nth-child(3) { white-space: normal !important; word-break: break-word !important; }
 .reportes-tabla-imprimir tr.opacity-60 { opacity: 0.55 !important; }
}
.print-header { display: none; }
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

 document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll("select.glass-input").forEach((el) => {
          if (el.tomselect) return;
          if (window.initGlassTomSelect) {
              window.initGlassTomSelect(el);
          }
      });
  });
</script>
@endsection
