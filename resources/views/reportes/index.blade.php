@extends('layouts.app')

@section('title', 'Informes y Reportes')

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
 <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Análisis financiero y de operaciones del mes.</p>
 </div>
</div>

<div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-2 no-print">
 <a href="{{ route('reportes.financiero.diario') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20">
 📅 Diario
 </a>
 <a href="{{ route('reportes.index') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500 text-white shadow-lg ">
 📈 Acumulado
 </a>
 <a href="{{ route('reportes.financiero.operaciones') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20">
 📋 Operaciones
 </a>
</div>

<div class="glass-card p-5 mb-4 no-print relative z-50">
  <form id="filtros-acumulado-general" action="{{ route('reportes.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
   <select name="mes" class="glass-input no-search w-40 font-semibold">
   @for($i=1; $i<=12; $i++)
   <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
   @endfor
   </select>
   <select name="anio" class="glass-input no-search w-28 font-semibold">
   @for($i=date('Y')-2; $i<=date('Y'); $i++)
   <option value="{{ $i }}" {{ $anio == $i ? 'selected' : '' }}>{{ $i }}</option>
   @endfor
   </select>
   <button type="submit" class="btn-primary py-2 px-3 text-sm" title="Filtrar">
   🔍 Filtrar
   </button>
   
   <div class="flex items-center gap-2 ml-auto">
       <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
       <span>🖨️</span> Imprimir
       </button>
       <button type="button" onclick="exportarAcumuladoGeneral('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
       <span>📊</span> Excel
       </button>
       <button type="button" onclick="exportarAcumuladoGeneral('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
       <span>📄</span> PDF
       </button>
   </div>
  </form>
</div>

<div class="space-y-4">

{{-- INFORME ACUMULADO --}}
  <div class="glass-card p-6">
  <h3 class="text-xl font-bold mb-4">📈 Informe Acumulado (Mes {{ $mes }}/{{ $anio }})</h3>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
   <div class="glass-card hover-glow glass-card-emerald p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
   <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">📈</span> Ingresos</div>
   <div class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['ingresos'], 0, ',', '.') }}</div>
   </div>
   <div class="glass-card hover-glow glass-card-red p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
   <div class="text-[11px] font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">📉</span> Egresos / Gastos</div>
   <div class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['egresos'], 0, ',', '.') }}</div>
   </div>
   <div class="glass-card hover-glow glass-card-blue p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
   <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">💎</span> Facturación Total</div>
   <div class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['facturado_total'], 0, ',', '.') }}</div>
   </div>
   <div class="glass-card hover-glow {{ $acumulado['utilidad_neta'] >= 0 ? 'glass-card-teal' : 'glass-card-orange' }} p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
   <div class="text-[11px] font-bold {{ $acumulado['utilidad_neta'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">⚖️</span> Utilidad Neta</div>
   <div class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['utilidad_neta'], 0, ',', '.') }}</div>
   </div>
   </div>
  
  <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
   <div class="glass-card hover-glow glass-card-blue p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
   <div class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">📦</span> Valorización Inventario (Costo)</div>
   <div class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['inventario_costo'], 0, ',', '.') }}</div>
   </div>
   <div class="glass-card hover-glow glass-card-emerald p-4 flex flex-col justify-center items-center text-center relative overflow-hidden group">
   <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">✨</span> Utilidad Esperada Inventario</div>
   <div class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['inventario_utilidad_esperada'], 0, ',', '.') }}</div>
   </div>
  </div>
  </div>

  {{-- INFORME DETALLADO (Transacciones) --}}
  <div class="glass-card p-6 mt-4">
  <h3 class="text-xl font-bold mb-4">📝 Informe Detallado (Transacciones del Mes)</h3>
  
  @if($transacciones->isEmpty())
  <div class="text-center py-12">
  <div class="text-5xl mb-4 opacity-50">📂</div>
  <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Sin Movimientos</h3>
  <p class="text-gray-500 mt-2">No se encontraron transacciones activas en este período.</p>
  </div>
  @else
  <div class="overflow-x-auto pb-2">
  <table class="ts-table responsive-table">
  <thead>
  <tr>
  <th class="text-center">Fecha</th>
  <th>Concepto</th>
  <th>Persona / Empresa</th>
  <th class="text-center">Tipo</th>
  <th class="text-center">Pago</th>
  <th class="text-center">Monto</th>
  <th class="text-center">Registrado por</th>
  </tr>
  </thead>
  <tbody>
  @foreach($transacciones as $tx)
  <tr>
  <td class="text-center font-mono text-sm text-gray-500">{{ \Carbon\Carbon::parse($tx->fecha)->format('d/m/Y') }}</td>
  <td>
  <span class="font-bold text-slate-800 dark:text-white">{{ $tx->concepto->nombre ?? 'N/A' }}</span>
  @if($tx->descripcion)
  <span class="block text-xs text-gray-500 italic mt-1">{{ $tx->descripcion }}</span>
  @endif
  </td>
  <td>
  <span class="font-bold text-slate-800 dark:text-white">{{ $tx->persona }}</span>
  @if($tx->empresa)
  <span class="block text-xs text-gray-500 italic mt-1">🏢 {{ $tx->empresa }}</span>
  @endif
  </td>
  <td class="text-center">
  <span class="pill {{ $tx->tipo_movimiento === 'ingreso' ? 'pill-ingreso' : 'pill-egreso' }}">
  {{ ucfirst($tx->tipo_movimiento) }}
  </span>
  </td>
  <td class="text-center capitalize font-semibold">{{ $tx->tipo_pago }}</td>
  <td class="text-center">
  <span class="font-black text-gray-900 dark:text-gray-100">
  ${{ number_format($tx->monto, 0, ',', '.') }}
  </span>
  </td>
  <td class="text-center text-xs text-gray-500">{{ $tx->user->name ?? 'Sistema' }}</td>
  </tr>
  @endforeach
  </tbody>
  </table>
  </div>
  <div class="mt-6 flex justify-end">
  {{ $transacciones->appends(request()->query())->links() }}
  </div>
  @endif
  </div>
 

 </div>

 {{-- INFORME POR OPERACIONES --}}
 <div class="glass-card p-6">
 <h3 class="text-xl font-bold mb-4">🧮 Informe por Operaciones (Tipos de Dinero)</h3>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  {{-- Efectivo --}}
  <div class="glass-card hover-glow glass-card-emerald p-5">
   <div class="flex flex-col items-center justify-center mb-4 text-center">
    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">💵</span> Efectivo Global</div>
    <div class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($operaciones['efectivo'], 0, ',', '.') }}</div>
   </div>
   <div class="space-y-3 pt-3 border-t border-gray-200/50 dark:border-gray-700/50 text-sm">
    <div class="flex justify-between items-center px-2">
     <span class="text-gray-600 dark:text-gray-400 font-semibold">Ingresos</span>
     <span class="font-bold text-emerald-600 dark:text-emerald-400">+${{ number_format($operaciones['ingresos_efectivo'], 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center px-2">
     <span class="text-gray-600 dark:text-gray-400 font-semibold">Egresos</span>
     <span class="font-bold text-red-500 dark:text-red-400">-${{ number_format($operaciones['egresos_efectivo'], 0, ',', '.') }}</span>
    </div>
   </div>
  </div>

  {{-- Consignacion --}}
  <div class="glass-card hover-glow glass-card-blue p-5">
   <div class="flex flex-col items-center justify-center mb-4 text-center">
    <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-lg">🏦</span> Consignación Global</div>
    <div class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($operaciones['consignacion'], 0, ',', '.') }}</div>
   </div>
   <div class="space-y-3 pt-3 border-t border-gray-200/50 dark:border-gray-700/50 text-sm">
    <div class="flex justify-between items-center px-2">
     <span class="text-gray-600 dark:text-gray-400 font-semibold">Ingresos</span>
     <span class="font-bold text-blue-600 dark:text-blue-400">+${{ number_format($operaciones['ingresos_consignacion'], 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center px-2">
     <span class="text-gray-600 dark:text-gray-400 font-semibold">Egresos</span>
     <span class="font-bold text-red-500 dark:text-red-400">-${{ number_format($operaciones['egresos_consignacion'], 0, ',', '.') }}</span>
    </div>
   </div>
  </div>
 </div>
 </div>



</div>
<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 8mm 15mm 8mm;
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
    
    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
        font-size: 9pt !important;
        width: 100% !important;
        height: auto !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    #main-wrapper {
        display: block !important;
        width: 100% !important;
        min-height: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    #ts-main, main {
        display: block !important;
        width: 100% !important;
        min-height: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 8mm 6mm !important; /* Force physical narrow margins */
        box-sizing: border-box !important;
    }
    
    .glass-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        margin-bottom: 20px !important;
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
        margin-top: 15px !important;
        margin-bottom: 15px !important;
        font-size: 8.5pt !important;
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
        padding: 7px 10px !important;
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
        font-size: 8pt !important;
    }
    
    table tbody tr:nth-child(even) td, .ts-table tbody tr:nth-child(even) td {
        background-color: #f8fafc !important;
    }
    
    table tfoot td, .ts-table tfoot td, table .tfoot td, .ts-table .tfoot td {
        background-color: #2d3748 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        font-size: 8pt !important;
        border-top: 2px solid #2d3748 !important;
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
    
    .grid {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 15px !important;
        margin-bottom: 20px !important;
    }
    
    .grid > div {
        flex: 1 1 20% !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        padding: 12px !important;
        background-color: #f8fafc !important;
        text-align: center !important;
        box-shadow: none !important;
    }
    
    .grid p {
        margin: 0 !important;
    }
    
    .grid p.text-xs {
        font-size: 7.5pt !important;
        color: #4a5568 !important;
        font-weight: bold !important;
    }
    
    .grid p.text-2xl, .grid p.text-3xl {
        font-size: 14pt !important;
        font-weight: 800 !important;
        color: #1a202c !important;
        margin-top: 5px !important;
    }
    
    span.pill, .badge, table td span, .ts-table td span, .reportes-tabla-imprimir td span {
        display: inline !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        font-size: 8pt !important;
        font-weight: normal !important;
        background: transparent !important;
        background-color: transparent !important;
        color: #000000 !important;
        text-transform: uppercase !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    
    .no-print-emoji, table td span.no-print-emoji, .grid p span, span.text-lg {
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
}
</style>

<script>
function exportarAcumuladoGeneral(tipo, btn) {
    const form = document.getElementById('filtros-acumulado-general');
    const params = new URLSearchParams(new FormData(form));
    params.set('export', tipo);
    const url = window.location.pathname + '?' + params.toString();
    const fallbackName = 'Reporte_General_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
    
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


