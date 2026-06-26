@extends('layouts.app')
@section('title', 'Informes y Reportes - Acumulado')

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
 <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Período: <strong>{{ $desde->format('d/m/Y') }}</strong> al <strong>{{ $hasta->format('d/m/Y') }}</strong>.</p>
 </div>
</div>

<div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-2 no-print">
 <a href="{{ route('reportes.financiero.diario') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20">
 📅 Diario
 </a>
 <a href="{{ route('reportes.financiero.acumulado') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500 text-white shadow-lg ">
 📈 Acumulado
 </a>
 <a href="{{ route('reportes.financiero.operaciones') }}"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20">
 📋 Operaciones
 </a>
</div>

<div class="glass-card p-5 mb-4 no-print relative z-50">
 <form id="filtros-acumulado" method="GET" class="flex flex-wrap items-center gap-3">
  <label class="font-semibold text-sm">Desde:</label>
  <input type="date" name="desde" value="{{ $desde->toDateString() }}" class="glass-input w-44">
  <label class="font-semibold text-sm">Hasta:</label>
  <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="glass-input w-44">
  <button class="btn-primary py-2 px-5 text-sm">
  🔍 Ver Período
  </button>
  
  <div class="flex items-center gap-2 ml-auto">
      <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
      <span>🖨️</span> Imprimir
      </button>
      <button type="button" onclick="exportarAcumulado('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
      <span>📄</span> PDF
      </button>
      <button type="button" onclick="exportarAcumulado('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
      <span>📊</span> Excel
      </button>
  </div>
 </form>
</div>

<div class="space-y-5">

 {{-- KPIs principales --}}
 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">🔧</span> Mantenimientos</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['facturado_mant'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
 <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">⚡</span> Electrónica</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['facturado_elec'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-orange-500/20 rounded-full blur-2xl group-hover:bg-orange-500/30 transition-all"></div>
 <p class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📦</span> Compras</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['compras_inventario'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-green-500/20 rounded-full blur-2xl group-hover:bg-green-500/30 transition-all"></div>
 <p class="text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">🛒</span> Ventas Inv.</p>
 <p class="text-3xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['ventas_inventario'], 0, ',', '.') }}</p>
 </div>
 </div>

 {{-- Balance consolidado --}}
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💵</span> Total Ingresos Reales (Caja)</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['ingresos_caja'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
 <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💸</span> Total Egresos Reales (Caja)</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['egresos_caja'], 0, ',', '.') }}</p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 {{ $acumulado['balance_neto'] >= 0 ? 'bg-teal-500/20 group-hover:bg-teal-500/30' : 'bg-orange-500/20 group-hover:bg-orange-500/30' }} rounded-full blur-2xl transition-all"></div>
 <p class="text-xs font-bold {{ $acumulado['balance_neto'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">⚖️</span> Balance Neto</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['balance_neto'], 0, ',', '.') }}</p>
 </div>
 </div>

 {{-- Saldos pendientes --}}
 @if($acumulado['saldo_pendiente_venta'] > 0 || $acumulado['saldo_pendiente_compra'] > 0)
 <div class="bg-yellow-500/10 border border-yellow-400/40 rounded-2xl p-5">
 <h3 class="font-bold text-yellow-700 dark:text-yellow-300 mb-3">⚠️ Saldos Pendientes</h3>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 @if($acumulado['saldo_pendiente_venta'] > 0)
 <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-yellow-300">
 <span class="text-2xl">🛒</span>
 <div>
 <p class="text-xs text-gray-500">Por cobrar (Ventas)</p>
 <p class="font-black text-yellow-700 dark:text-yellow-300 text-lg">${{ number_format($acumulado['saldo_pendiente_venta'], 0, ',', '.') }}</p>
 </div>
 </div>
 @endif
 @if($acumulado['saldo_pendiente_compra'] > 0)
 <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-yellow-300">
 <span class="text-2xl">📦</span>
 <div>
 <p class="text-xs text-gray-500">Por pagar (Compras)</p>
 <p class="font-black text-yellow-700 dark:text-yellow-300 text-lg">${{ number_format($acumulado['saldo_pendiente_compra'], 0, ',', '.') }}</p>
 </div>
 </div>
 @endif
 </div>
 </div>
 @endif

  {{-- Tabla consolidada del período --}}
 <div class="glass-card p-6 md:p-8 mt-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold">Resumen Consolidado del Período</h3>
    </div>
    
    <div class="overflow-x-auto pb-2">
        <table class="ts-table responsive-table w-full text-sm">
            <thead>
                <tr>
                    <th class="p-3 text-left">Categoría</th>
                    <th class="p-3 text-center">Cantidad</th>
                    <th class="p-3 text-center">Costo Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-blue-600 dark:text-blue-400"><span class="mr-2">🔧</span>Mantenimientos</td>
                    <td class="p-3 text-center">{{ $acumulado['total_mantenimientos'] }}</td>
                    <td class="p-3 text-center font-bold text-blue-600 dark:text-blue-400">${{ number_format($acumulado['facturado_mant'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-purple-600 dark:text-purple-400"><span class="mr-2">⚡</span>Electrónica</td>
                    <td class="p-3 text-center">{{ $acumulado['total_electronicas'] }}</td>
                    <td class="p-3 text-center font-bold text-purple-600 dark:text-purple-400">${{ number_format($acumulado['facturado_elec'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-orange-600 dark:text-orange-400"><span class="mr-2">📦</span>Compras de Inventario</td>
                    <td class="p-3 text-center">{{ $acumulado['total_compras'] }}</td>
                    <td class="p-3 text-center font-bold text-orange-600 dark:text-orange-400">${{ number_format($acumulado['compras_inventario'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-green-600 dark:text-green-400"><span class="mr-2">🛒</span>Ventas de Inventario</td>
                    <td class="p-3 text-center">{{ $acumulado['total_ventas'] }}</td>
                    <td class="p-3 text-center font-bold text-green-600 dark:text-green-400">${{ number_format($acumulado['ventas_inventario'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-emerald-600 dark:text-emerald-400"><span class="mr-2">📈</span>Ingresos Reales (Caja)</td>
                    <td class="p-3 text-center">{{ $acumulado['total_ingresos'] ?? 0 }}</td>
                    <td class="p-3 text-center font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($acumulado['ingresos_caja'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-red-600 dark:text-red-400"><span class="mr-2">📉</span>Egresos Reales (Caja)</td>
                    <td class="p-3 text-center">{{ $acumulado['total_egresos'] ?? 0 }}</td>
                    <td class="p-3 text-center font-bold text-red-600 dark:text-red-400">${{ number_format($acumulado['egresos_caja'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 opacity-70">
                    <td class="p-3 font-semibold text-gray-500"><span class="mr-2">🚫</span>Movimientos Anulados</td>
                    <td class="p-3 text-center">{{ $acumulado['total_anulados'] ?? 0 }}</td>
                    <td class="p-3 text-center font-bold" style="color: #dd6b20 !important;"" title="Este valor no suma al balance">${{ number_format($acumulado['total_costo_anulados'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
                                    <tfoot class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
                <tr>
                    <td class="text-center font-bold text-xs">Total: 7</td>
                    <td class="text-center">
                        <div class="relative inline-block">
                            <span class="absolute right-full mr-2 font-bold text-xs whitespace-nowrap">Total Registros:</span>
                            <span class="font-bold text-xs">{{ ($acumulado['total_mantenimientos'] ?? 0) + ($acumulado['total_electronicas'] ?? 0) + ($acumulado['total_compras'] ?? 0) + ($acumulado['total_ventas'] ?? 0) + ($acumulado['total_ingresos'] ?? 0) + ($acumulado['total_egresos'] ?? 0) + ($acumulado['total_anulados'] ?? 0) }}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="relative inline-block">
                            <span class="absolute right-full mr-3 top-1/2 -translate-y-1/2 uppercase text-xs whitespace-nowrap">Balance Neto:</span>
                            <span class="font-black text-lg {{ ($acumulado['balance_neto'] >= 0) ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }}">
                                ${{ number_format($acumulado['balance_neto'], 0, ',', '.') }}
                            </span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
 </div>

</div>

<script>
function exportarAcumulado(tipo, btn) {
    const form = document.getElementById('filtros-acumulado');
    const params = new URLSearchParams(new FormData(form));
    params.set('export', tipo);
    const url = window.location.pathname + '?' + params.toString();
    const fallbackName = 'Reporte_Acumulado_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
    
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
