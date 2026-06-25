@extends('layouts.app')
@section('title', 'Informes y Reportes - Diario')

@section('content')
<div class="flex gap-4 mb-6 no-print">
 <a href="{{ route('reportes.financiero.diario') }}" class="bg-amber-500 text-white px-4 py-2 rounded-xl font-bold shadow-sm">💵 Informes Financieros</a>
 <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('electronicas.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Módulo Electrónica</a>
</div>

<div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-4">
 <div>
 <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-2">
 📊 Informes y Reportes
 </h1>
 <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Mostrando todos los movimientos del <strong>{{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM [de] YYYY') }}</strong>.</p>
 </div>
</div>

<div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-2 no-print">
 <a href="{{ route('reportes.financiero.diario') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500 text-white shadow-lg ">
 📅 Diario
 </a>
 <a href="{{ route('reportes.financiero.acumulado') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20">
 📈 Acumulado
 </a>
 <a href="{{ route('reportes.financiero.operaciones') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20">
 📋 Operaciones
 </a>
</div>

<div class="glass-card p-5 mb-4 no-print">
 <form id="filtros-diario" method="GET" class="flex flex-wrap items-center gap-3">
   <label class="font-semibold text-sm">📅 Fecha:</label>
   <input type="date" name="fecha" value="{{ $fecha }}" class="glass-input w-44">
   <button class="btn-primary py-2 px-5 text-sm" title="Filtrar">Ver Día</button>
   <a href="{{ route('reportes.financiero.diario') }}" class="btn-clean text-sm">Hoy</a>
  
   <div class="flex items-center gap-2 ml-auto">
       <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
       <span>🖨️</span> Imprimir
       </button>
       <button type="button" onclick="exportarDiario('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
       <span>📄</span> PDF
       </button>
       <button type="button" onclick="exportarDiario('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
       <span>📊</span> Excel
       </button>
   </div>
 </form>
</div>

 {{-- Tarjetas de resumen --}}
 <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📈</span> Ingresos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($resumen['total_ingresos'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
 <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📉</span> Egresos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($resumen['total_egresos'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">🔧</span> Mantenimientos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($resumen['total_mantenimientos'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-gray-500/20 rounded-full blur-2xl group-hover:bg-gray-500/30 transition-all"></div>
 <p class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">🚫</span> Anulados</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">{{ $resumen['total_anulados'] }}</p>
 </div>
 </div>

 {{-- Tabla de movimientos del día --}}
 <div class="glass-card p-6 md:p-8 mt-4">
 <div class="flex justify-between items-center mb-4">
 <h3 class="text-lg font-bold">Movimientos del Día ({{ $movimientos->count() }})</h3>
 </div>

 @if($movimientos->isEmpty())
 <div class="flex flex-col items-center justify-center space-y-3 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm p-12 rounded-2xl border border-white/20 my-4">
     <div class="text-5xl opacity-80">📭</div>
     <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
     <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No hubo movimientos en esta fecha.</p>
 </div>
 @else
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm">
 <thead>
 <tr>
 <th class="p-3 text-center">Tipo</th>
 <th class="p-3 text-left">Descripción</th>
 <th class="p-3 text-center">Progreso</th>
 <th class="p-3 text-center">Estado</th>
 <th class="p-3 text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 @foreach($movimientos as $mov)
 @php
   $isAnulado = !empty($mov['anulado']);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 @endphp
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-center">
 <td class="p-3 {{ $dimLight }}">
 <span class="px-2 py-0.5 rounded-lg text-xs font-bold
 bg-{{ $mov['color'] }}-100 text-{{ $mov['color'] }}-800
 dark:bg-{{ $mov['color'] }}-900/40 dark:text-{{ $mov['color'] }}-300">
 {{ $mov['icono'] }} {{ ucfirst($mov['tipo']) }}
 </span>
 </td>
 <td class="p-3 text-left text-gray-700 dark:text-gray-300 {{ $dim }}">{{ $mov['descripcion'] }}</td>
   <td class="p-3 {{ $dimLight }}">
   @php
       $progreso = strtolower($mov['estado'] ?? '');
       
       // Clarificar el "progreso" para transacciones que no son mantenimientos
       if(in_array($mov['tipo'], ['ingreso', 'egreso'])) $progreso = 'procesado';
       if(in_array($mov['tipo'], ['venta', 'compra'])) $progreso = 'emitida';

       $pillClass = 'pill-pending';
       if(in_array($progreso, ['terminado', 'entregado'])) $pillClass = 'pill-done';
       elseif($progreso === 'emitida') $pillClass = 'pill-preventivo';
       elseif($progreso === 'procesado') $pillClass = 'pill-especialidad';
       elseif(in_array($progreso, ['en_proceso', 'reparado'])) $pillClass = 'pill-efectivo';
   @endphp
   <span class="pill {{ $pillClass }} {{ $isAnulado ? 'opacity-70' : '' }}">{{ ucfirst($progreso) ?: '—' }}</span>
   </td>
 <td class="p-3">
 <span class="pill {{ $isAnulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $isAnulado ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 <td class="p-3 text-center font-bold {{ in_array($mov['tipo'], ['ingreso','venta','mantenimiento','electronica']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} {{ $dim }}">
 ${{ number_format($mov['monto'] ?? 0, 0, ',', '.') }}
 </td>
 </tr>
 @endforeach
 </tbody>
 <tfoot>
    <tr class="bg-gray-100 dark:bg-gray-800">
        @php
            $neto = $movimientos->where('anulado', false)->whereIn('tipo', ['ingreso','venta','mantenimiento','electronica'])->sum('monto') 
                  - $movimientos->where('anulado', false)->whereIn('tipo', ['egreso','compra'])->sum('monto');
            $color = $neto >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400';
        @endphp
        <td colspan="4" class="p-3 text-right font-bold uppercase tracking-wider text-sm">Balance Neto del Día:</td>
        <td class="p-3 text-center font-black text-lg {{ $color }}">${{ number_format($neto, 0, ',', '.') }}</td>
    </tr>
 </tfoot>
 </table>
 </div>
 @endif
 </div>

</div>

<script>
function exportarDiario(tipo, btn) {
    const form = document.getElementById('filtros-diario');
    const params = new URLSearchParams(new FormData(form));
    params.set('export', tipo);
    const url = window.location.pathname + '?' + params.toString();
    const fallbackName = 'Reporte_Diario_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
    
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
