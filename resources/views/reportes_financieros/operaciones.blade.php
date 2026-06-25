@extends('layouts.app')
@section('title', 'Informes y Reportes - Operaciones')

@section('content')
<div class="flex gap-4 mb-6 no-print">
 <a href="{{ route('reportes.financiero.diario') }}" class="bg-amber-500 text-white px-4 py-2 rounded-xl font-bold shadow-sm">💵 Informes Financieros</a>
 <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('electronicas.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Reporte de Electrónica</a>
 <a href="{{ route('stocks.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">📦 Informe Inventario</a>
</div>

<div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-4">
 <div>
 <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-2">
 📊 Informes y Reportes
 </h1>
 <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Análisis financiero y de operaciones.</p>
 </div>
</div>

<div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-2 no-print">
 <a href="{{ route('reportes.financiero.diario') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20">
 📅 Diario
 </a>
 <a href="{{ route('reportes.financiero.acumulado') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20">
 📈 Acumulado
 </a>
 <a href="{{ route('reportes.financiero.operaciones') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500 text-white shadow-lg ">
 📋 Operaciones
 </a>
</div>

<div class="glass-card p-5 mb-4 no-print">
 <form id="filtros-operaciones" method="GET" class="flex flex-wrap items-center gap-3">
   <select name="tipo" class="glass-input no-search w-48 font-semibold">
   @foreach($tipoLabels as $val => $label)
   <option value="{{ $val }}" {{ $tipo === $val ? 'selected' : '' }}>{{ $label }}</option>
   @endforeach
   </select>
   <label class="font-semibold text-sm">Desde:</label>
   <input type="date" name="desde" value="{{ $desde->toDateString() }}" class="glass-input w-36">
   <label class="font-semibold text-sm">Hasta:</label>
   <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="glass-input w-36">
   <button class="btn-primary py-2 px-4 text-sm" title="Filtrar">🔍 Filtrar</button>
   <div class="flex items-center gap-2 ml-auto">
        <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
        <span>🖨️</span> Imprimir
        </button>
        <button type="button" onclick="exportarOperaciones('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
        <span>📄</span> PDF
        </button>
        <button type="button" onclick="exportarOperaciones('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
        <span>📊</span> Excel
        </button>
    </div>
 </form>
</div>

{{-- Resultados --}}
<div class="glass-card p-6">
 @if($registros->isEmpty())
 <div class="flex flex-col items-center justify-center space-y-3 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm p-12 rounded-2xl border border-white/20 my-4">
     <div class="text-5xl opacity-80">📭</div>
     <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
     <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No se encontraron operaciones en este período.</p>
 </div>
 @else
 <div class="flex justify-between items-center mb-4">
 <h3 class="text-lg font-bold">{{ $tipoLabels[$tipo] }} <span class="text-sm font-normal text-gray-500">({{ $registros->total() }} registros)</span></h3>
 </div>

 {{-- Tabla Mantenimientos --}}
 @if($tipo === 'solo_mantenimientos')
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm text-center">
 <thead>
 <tr>
 <th class="p-2 text-center">Orden</th><th class="p-2 text-left">Equipo / Cliente</th>
 <th class="p-2 text-center">Técnico</th><th class="p-2 text-center">Entrada</th>
 <th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th><th class="p-2 text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 @foreach($registros as $m)
 @php
   $isAnulado = !empty($m->anulado);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 @endphp
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="p-2 font-mono font-bold whitespace-nowrap {{ $dim }}">
 <a href="{{ route('mantenimientos.index', ['locate' => $m->id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
 {{ $m->id_orden }}
 </a>
 </td>
 <td class="p-2 text-left {{ $dim }}">
 <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
 <span class="font-bold group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $m->equipo->nombre ?? '—' }}</span>
 </a> 
 <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
 <span class="text-xs text-gray-500 font-semibold group-hover:text-blue-600 dark:group-hover:text-blue-400">({{ $m->equipo->cliente->nombre ?? '—' }})</span>
 </a>
 </td>
 <td class="p-2 {{ $dim }}">{{ $m->tecnico->nombre ?? '—' }}</td>
 <td class="p-2 {{ $dim }}">{{ $m->fecha_entrada->format('d/m/Y') }}</td>
 <td class="p-2 {{ $dimLight }}"><span class="pill pill-efectivo {{ $isAnulado ? 'opacity-70' : '' }}">{{ ucfirst($m->estado) }}</span></td>
 <td class="p-2">
 <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $isAnulado ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 <td class="p-2 text-center font-bold text-blue-600 {{ $dim }}" {!! $isAnulado ? 'style="color: #dd6b20 !important;"' : '' !!}>${{ number_format($m->costo, 0, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
  <tfoot>
    <tr class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
        <td class="text-center font-bold text-xs">Total: {{ $registros->count() }}</td>
        <td colspan="5" class="text-right uppercase text-xs">Total Costos Mantenimientos:</td>
        <td class="text-center font-black text-lg text-blue-700 dark:text-blue-400">${{ number_format($registros->where('anulado', 0)->sum('costo'), 0, ',', '.') }}</td>
    </tr>
 </tfoot>
 </table>
 </div>
 
 {{-- Tabla Electrónica --}}
 @elseif($tipo === 'solo_electronica')
 <div class="overflow-x-auto pb-2">
 <table class="ts-table table-electronica responsive-table w-full text-sm text-center">
 <thead>
 <tr>
 <th class="p-2 text-center">Orden</th><th class="p-2 text-left">Dispositivo / Cliente</th>
 <th class="p-2 text-center">Técnico</th><th class="p-2 text-center">Entrada</th>
 <th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th><th class="p-2 text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 @foreach($registros as $e)
 @php
   $isAnulado = !empty($e->anulado);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 @endphp
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="p-2 font-mono font-bold whitespace-nowrap {{ $dim }}">
 <a href="{{ route('electronicas.index', ['locate' => $e->id]) }}" class="text-purple-600 dark:text-purple-400 hover:underline">
 {{ $e->id_orden }}
 </a>
 </td>
 <td class="p-2 text-left {{ $dim }}">
 <a href="{{ route('equipos.index') }}#equipo-{{ $e->equipo_id }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
 <span class="font-bold group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ $e->equipo->nombre ?? '—' }}</span>
 </a> 
 <a href="{{ route('clientes.index') }}#cliente-{{ $e->equipo->cliente_id ?? '' }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
 <span class="text-xs text-gray-500 font-semibold group-hover:text-purple-600 dark:group-hover:text-purple-400">({{ $e->equipo->cliente->nombre ?? '—' }})</span>
 </a>
 </td>
 <td class="p-2 {{ $dim }}">{{ $e->tecnico->nombre ?? '—' }}</td>
 <td class="p-2 {{ $dim }}">{{ $e->fecha_entrada->format('d/m/Y') }}</td>
 <td class="p-2 {{ $dimLight }}"><span class="pill pill-pending {{ $isAnulado ? 'opacity-70' : '' }}">{{ ucfirst($e->estado) }}</span></td>
 <td class="p-2">
 <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $isAnulado ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 <td class="p-2 text-center font-bold text-purple-600 {{ $dim }}" {!! $isAnulado ? 'style="color: #dd6b20 !important;"' : '' !!}>${{ number_format($e->costo, 0, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
  <tfoot>
    <tr class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
        <td class="text-center font-bold text-xs">Total: {{ $registros->count() }}</td>
        <td colspan="5" class="text-right uppercase text-xs">Total Costos Electrónica:</td>
        <td class="text-center font-black text-lg text-purple-700 dark:text-purple-400">${{ number_format($registros->where('anulado', 0)->sum('costo'), 0, ',', '.') }}</td>
    </tr>
 </tfoot>
 </table>
 </div>
 
 {{-- Tabla Ingresos / Egresos --}}
 @elseif(in_array($tipo, ['solo_ingresos', 'solo_egresos']))
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm text-center">
 <thead>
 <tr>
 <th class="p-2 text-center">Fecha</th><th class="p-2 text-left">Persona / Empresa</th>
 <th class="p-2 text-center">Concepto</th><th class="p-2 text-center">Tipo Pago</th>
 <th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th><th class="p-2 text-center">Monto</th>
 </tr>
 </thead>
 <tbody>
 @foreach($registros as $c)
 @php
   $isAnulado = !empty($c->anulado);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 @endphp
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="p-2 {{ $dim }}">{{ $c->fecha->format('d/m/Y') }}</td>
 <td class="p-2 text-left {{ $dim }}">{{ $c->persona ?? $c->empresa ?? '—' }}</td>
 <td class="p-2 text-xs {{ $dim }}">{{ $c->concepto->nombre ?? '—' }}</td>
 <td class="p-2 text-xs capitalize {{ $dim }}">{{ $c->tipo_pago }}</td>
 <td class="p-2 {{ $dimLight }}"><span class="pill pill-especialidad {{ $isAnulado ? 'opacity-70' : '' }}">Procesado</span></td>
 <td class="p-2">
 <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $isAnulado ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 <td class="p-2 text-center font-bold {{ $tipo === 'solo_ingresos' ? 'text-green-600' : 'text-red-600' }} {{ $dim }}" {!! $isAnulado ? 'style="color: #dd6b20 !important;"' : '' !!}>${{ number_format($c->monto, 0, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 <tfoot>
    <tr class="bg-gray-100 dark:bg-gray-800">
        <td colspan="6" class="p-3 text-right font-bold uppercase tracking-wider text-sm">Total Monto:</td>
        <td class="text-center font-black text-lg {{ $tipo === 'solo_ingresos' ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">${{ number_format($registros->where('anulado', 0)->sum('monto'), 0, ',', '.') }}</td>
    </tr>
 </tfoot>
 </table>
 </div>
 
 {{-- Tabla Compras / Ventas --}}
 @else
  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
  <table class="ts-table responsive-table w-full text-sm text-center">
  <thead>
  <tr>
  <th class="p-2 text-center">Fecha</th>
  <th class="p-2 text-center">Factura Nº</th>
  <th class="p-2 text-left">Persona / Empresa</th>
  <th class="p-2 text-center">Pagado</th>
 <th class="p-2 text-center">Estado</th>
 <th class="p-2 text-center">Total</th>
  </tr>
  </thead>
  <tbody>
  @foreach($registros as $f)
  @php
    $isAnulado = $f->estado === 'anulada';
    $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $stClass = 'pill-pending';
    if($f->estado === 'emitida') $stClass = 'pill-done';
    if($f->estado === 'anulada') $stClass = 'pill-anulado';
    
    $label = ucfirst(str_replace('_', ' ', $f->estado));
    if($f->estado === 'pendiente_pago') $label = 'Pendiente';
  @endphp
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
  <td class="p-2 {{ $dim }}">{{ $f->fecha->format('d/m/Y') }}</td>
  <td class="p-2 font-mono font-bold {{ $dim }}">{{ $f->numero_factura }}</td>
  <td class="p-2 text-left {{ $dim }}">{{ $f->facturable->nombre ?? $f->facturable->nombre_razon_social ?? '—' }}</td>
  <td class="p-2 font-semibold text-emerald-600 {{ $dim }}">${{ number_format($f->total_pagado, 0, ',', '.') }}</td>
  <td class="p-2">
  <span class="pill {{ $stClass }}">
  {{ $label }}
  </span>
  </td>
 <td class="p-2 text-center font-bold text-blue-600 {{ $dim }}" {!! $isAnulado ? 'style="color: #dd6b20 !important;"' : '' !!}>${{ number_format($f->total_documento, 0, ',', '.') }}</td>
  </tr>
  @endforeach
  </tbody>
 <tfoot>
    <tr class="bg-gray-100 dark:bg-gray-800">
        <td colspan="5" class="p-3 text-right font-bold uppercase tracking-wider text-sm">Total Documentos:</td>
        <td class="text-center font-black text-lg text-blue-700 dark:text-blue-400">${{ number_format($registros->filter(function($i) { return $i->estado !== 'anulada'; })->sum('total_documento'), 0, ',', '.') }}</td>
    </tr>
 </tfoot>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
  {{ $registros->appends(request()->query())->links() }}
  </div>
  @endif
  @endif
</div>

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

<script>
function exportarOperaciones(tipo, btn) {
    const form = document.getElementById('filtros-operaciones');
    const params = new URLSearchParams(new FormData(form));
    params.set('export', tipo);
    const url = window.location.pathname + '?' + params.toString();
    const fallbackName = 'Reporte_Operaciones_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
    
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

