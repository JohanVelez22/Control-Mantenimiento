@extends('layouts.app')
@section('title', 'Informes y Reportes')

@section('content')
<div class="flex gap-4 mb-6 no-print overflow-x-auto pb-2">
 <a href="{{ route('reportes.financiero.diario') }}" class="px-4 py-2 rounded-xl font-bold shadow-lg transition-all bg-amber-500 text-white whitespace-nowrap">💵 Informes Financieros</a>
 <a href="{{ route('mantenimientos.reportes') }}" class="px-4 py-2 rounded-xl font-bold shadow-sm transition-all bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20 whitespace-nowrap">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('electronicas.reportes') }}" class="px-4 py-2 rounded-xl font-bold shadow-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20 whitespace-nowrap">⚡ Reporte de Electrónica</a>
 <a href="{{ route('stocks.reportes') }}" class="px-4 py-2 rounded-xl font-bold shadow-sm transition-all bg-orange-500/10 text-orange-700 dark:text-orange-300 hover:bg-orange-500/20 whitespace-nowrap">📦 Informe Inventario</a>
</div>

<div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-4 no-print">
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
      <button type="button" onclick="exportarAcumulado('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
      <span>📊</span> Excel
      </button>
      <button type="button" onclick="exportarAcumulado('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
      <span>📄</span> PDF
      </button>
  </div>
 </form>
</div>

<div class="space-y-5">

 {{-- Resumen Consolidado (1 sola fila horizontal permanente homogénea) --}}
  <div class="print-grid-7" style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.75rem; width: 100%;">
  <div class="glass-card hover-glow glass-card-emerald p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">📈</span> Ingresos (Caja)</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['ingresos_caja'], 0, ',', '.') }}</p>
  </div>

  <div class="glass-card hover-glow glass-card-red p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">📉</span> Egresos (Caja)</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['egresos_caja'], 0, ',', '.') }}</p>
  </div>

  <div class="glass-card hover-glow glass-card-blue p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">🔧</span> Mantenimiento</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['facturado_mant'], 0, ',', '.') }}</p>
  </div>

  <div class="glass-card hover-glow glass-card-purple p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">⚡</span> Electrónica</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['facturado_elec'], 0, ',', '.') }}</p>
  </div>

  <div class="glass-card hover-glow glass-card-teal p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">🛒</span> Ventas</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['ventas_inventario'], 0, ',', '.') }}</p>
  </div>

  <div class="glass-card hover-glow glass-card-orange p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">📦</span> Compras</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['compras_inventario'], 0, ',', '.') }}</p>
  </div>

  <div class="glass-card hover-glow {{ $acumulado['balance_neto'] >= 0 ? 'glass-card-teal' : 'glass-card-orange' }} p-4 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
  <p class="text-xs xl:text-sm font-bold {{ $acumulado['balance_neto'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-wider mb-1 z-10 flex items-center gap-1.5 justify-center truncate w-full"><span class="text-sm sm:text-base no-print-emoji">⚖️</span> Balance Neto</p>
  <p class="text-base sm:text-xl xl:text-2xl font-black text-slate-800 dark:text-white z-10 whitespace-nowrap">${{ number_format($acumulado['balance_neto'], 0, ',', '.') }}</p>
  </div>
  </div>

  {{-- Saldos pendientes del período --}}
  @if(($acumulado['total_por_cobrar'] ?? 0) > 0 || ($acumulado['total_por_pagar'] ?? 0) > 0)
  <div class="p-5 md:p-6 relative overflow-hidden saldos-box-ghost">
      <style>
          .saldos-box-ghost {
              background: rgba(245, 158, 11, 0.05);
              border: 1px solid rgba(245, 158, 11, 0.18);
              border-radius: 20px;
              backdrop-filter: blur(20px);
              -webkit-backdrop-filter: blur(20px);
          }
          html.dark .saldos-box-ghost {
              background: rgba(245, 158, 11, 0.06) !important;
              border-color: rgba(245, 158, 11, 0.16) !important;
          }
          .card-por-cobrar {
              background: rgba(255, 255, 255, 0.70);
              border: 1px solid rgba(16, 185, 129, 0.18);
              border-radius: 16px;
              box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
          }
          html.dark .card-por-cobrar {
              background: rgba(15, 23, 42, 0.55) !important;
              border: 1px solid rgba(16, 185, 129, 0.15) !important;
              box-shadow: 0 4px 16px rgba(0, 0, 0, 0.20) !important;
          }
          .card-por-pagar {
              background: rgba(255, 255, 255, 0.70);
              border: 1px solid rgba(239, 68, 68, 0.18);
              border-radius: 16px;
              box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
          }
          html.dark .card-por-pagar {
              background: rgba(15, 23, 42, 0.55) !important;
              border: 1px solid rgba(239, 68, 68, 0.15) !important;
              box-shadow: 0 4px 16px rgba(0, 0, 0, 0.20) !important;
          }
          .saldos-divider {
              border-top: 1px solid rgba(0, 0, 0, 0.06);
          }
          html.dark .saldos-divider {
              border-top: 1px solid rgba(255, 255, 255, 0.06) !important;
          }
      </style>
      <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
          <div class="flex items-center gap-2.5">
              <span class="w-8 h-8 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base font-bold shadow-inner">
                  ⚠️
              </span>
              <div>
                  <h3 class="font-bold text-slate-800 dark:text-amber-300 text-base leading-tight">
                      Saldos Pendientes del Período
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Obligaciones y recaudos acumulados del período</p>
              </div>
          </div>
          <span class="text-xs font-semibold px-3 py-1 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
              Cuentas por cobrar y pagar
          </span>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          {{-- Tarjeta: Por Cobrar --}}
          @if(($acumulado['total_por_cobrar'] ?? 0) > 0)
          <div class="card-por-cobrar p-4 md:p-5 flex flex-col transition-all">
              <div class="flex items-center gap-3">
                  <div class="w-11 h-11 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 border border-emerald-500/20 flex items-center justify-center text-xl flex-shrink-0">
                      📥
                  </div>
                  <div>
                      <p class="text-xs xl:text-sm font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total por cobrar</p>
                      <p class="font-black text-emerald-600 dark:text-emerald-400 text-base sm:text-xl xl:text-2xl">${{ number_format($acumulado['total_por_cobrar'], 0, ',', '.') }}</p>
                  </div>
              </div>

              {{-- Desglose por cobrar --}}
              <div class="mt-4 pt-3 saldos-divider flex flex-wrap gap-2 text-xs">
                  @if(($acumulado['saldo_pendiente_venta'] ?? 0) > 0)
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-teal-500/10 text-teal-800 dark:text-teal-300 border border-teal-500/20 font-medium">
                          🛒 <strong>Ventas:</strong> ${{ number_format($acumulado['saldo_pendiente_venta'], 0, ',', '.') }}
                      </span>
                  @endif
                  @if(($acumulado['saldo_pendiente_caja_ingreso'] ?? 0) > 0)
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-800 dark:text-emerald-300 border border-emerald-500/20 font-medium">
                          📈 <strong>Ingresos Caja:</strong> ${{ number_format($acumulado['saldo_pendiente_caja_ingreso'], 0, ',', '.') }}
                      </span>
                  @endif
                  @if(($acumulado['saldo_pendiente_mant'] ?? 0) > 0)
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-800 dark:text-blue-300 border border-blue-500/20 font-medium">
                          🔧 <strong>Mantenimientos:</strong> ${{ number_format($acumulado['saldo_pendiente_mant'], 0, ',', '.') }}
                      </span>
                  @endif
                  @if(($acumulado['saldo_pendiente_elec'] ?? 0) > 0)
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-purple-500/10 text-purple-800 dark:text-purple-300 border border-purple-500/20 font-medium">
                          ⚡ <strong>Electrónica:</strong> ${{ number_format($acumulado['saldo_pendiente_elec'], 0, ',', '.') }}
                      </span>
                  @endif
              </div>
          </div>
          @endif

          {{-- Tarjeta: Por Pagar --}}
          @if(($acumulado['total_por_pagar'] ?? 0) > 0)
          <div class="card-por-pagar p-4 md:p-5 flex flex-col transition-all">
              <div class="flex items-center gap-3">
                  <div class="w-11 h-11 rounded-xl bg-red-500/10 dark:bg-red-500/20 border border-red-500/20 flex items-center justify-center text-xl flex-shrink-0">
                      📤
                  </div>
                  <div>
                      <p class="text-xs xl:text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-wider">Total por pagar</p>
                      <p class="font-black text-red-600 dark:text-red-400 text-base sm:text-xl xl:text-2xl">${{ number_format($acumulado['total_por_pagar'], 0, ',', '.') }}</p>
                  </div>
              </div>

              {{-- Desglose por pagar --}}
              <div class="mt-4 pt-3 saldos-divider flex flex-wrap gap-2 text-xs">
                  @if(($acumulado['saldo_pendiente_compra'] ?? 0) > 0)
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-800 dark:text-amber-300 border border-amber-500/20 font-medium">
                          📦 <strong>Compras:</strong> ${{ number_format($acumulado['saldo_pendiente_compra'], 0, ',', '.') }}
                      </span>
                  @endif
                  @if(($acumulado['saldo_pendiente_caja_egreso'] ?? 0) > 0)
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-800 dark:text-rose-300 border border-rose-500/20 font-medium">
                          📉 <strong>Egresos Caja:</strong> ${{ number_format($acumulado['saldo_pendiente_caja_egreso'], 0, ',', '.') }}
                      </span>
                  @endif
              </div>
          </div>
          @endif
      </div>
  </div>
  @endif

  {{-- Tabla consolidada del período --}}
  <div class="glass-card p-6 md:p-8">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-bold">Resumen Consolidado del Período</h3>
            <div class="print-date hidden-screen text-xs text-gray-500 font-semibold mt-0.5"><strong>Fecha Impresión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }}</div>
        </div>
    </div>
    
    <div class="overflow-x-auto pb-2">
        <table class="ts-table responsive-table w-full text-sm">
            <thead>
                <tr>
                    <th class="p-3 text-left whitespace-nowrap">Categoría</th>
                    <th class="p-3 text-center whitespace-nowrap">Cantidad</th>
                    <th class="p-3 text-center whitespace-nowrap">Costo Total</th>
                </tr>
            </thead>
            <tbody>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">🔧</span>Mantenimientos</td>
                    <td class="p-3 text-center">{{ $acumulado['total_mantenimientos'] }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">${{ number_format($acumulado['facturado_mant'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">⚡</span>Electrónica</td>
                    <td class="p-3 text-center">{{ $acumulado['total_electronicas'] }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">${{ number_format($acumulado['facturado_elec'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">📦</span>Compras de Inventario</td>
                    <td class="p-3 text-center">{{ $acumulado['total_compras'] }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">${{ number_format($acumulado['compras_inventario'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">🛒</span>Ventas de Inventario</td>
                    <td class="p-3 text-center">{{ $acumulado['total_ventas'] }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">${{ number_format($acumulado['ventas_inventario'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">📈</span>Ingresos (Caja)</td>
                    <td class="p-3 text-center">{{ $acumulado['total_ingresos'] ?? 0 }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">${{ number_format($acumulado['ingresos_caja'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">📉</span>Egresos (Caja)</td>
                    <td class="p-3 text-center">{{ $acumulado['total_egresos'] ?? 0 }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">${{ number_format($acumulado['egresos_caja'], 0, ',', '.') }}</td>
                </tr>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <td class="p-3 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap"><span class="mr-2">🚫</span>Movimientos Anulados</td>
                    <td class="p-3 text-center">{{ $acumulado['total_anulados'] ?? 0 }}</td>
                    <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100" title="Este valor no suma al balance">${{ number_format($acumulado['total_costo_anulados'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
                                    <tfoot class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
                @php
                    $totalRegistros = ($acumulado['total_mantenimientos'] ?? 0)
                                    + ($acumulado['total_electronicas'] ?? 0)
                                    + ($acumulado['total_compras'] ?? 0)
                                    + ($acumulado['total_ventas'] ?? 0)
                                    + ($acumulado['total_ingresos'] ?? 0)
                                    + ($acumulado['total_egresos'] ?? 0)
                                    + ($acumulado['total_anulados'] ?? 0);
                @endphp
                <tr>
                    <td class="text-center font-bold text-xs uppercase" style="color: #ffffff !important;">TOTAL: 7</td>
                    <td class="text-center font-bold text-xs uppercase whitespace-nowrap" style="color: #ffffff !important;"><span style="position: relative; left: -70px; color: #ffffff !important;">TOTAL REGISTROS:</span> <span style="position: relative; left: -58.5px; color: #ffffff !important;">{{ $totalRegistros }}</span></td>
                    <td class="text-center font-bold text-xs uppercase whitespace-nowrap" style="color: #ffffff !important;"><span style="position: relative; left: -60px; color: #ffffff !important;">BALANCE NETO:</span> <span style="position: relative; left: -48px; color: #ffffff !important;">${{ number_format($acumulado['balance_neto'], 0, ',', '.') }}</span></td>
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
    @page {
        size: A4 portrait;
        margin: 10mm 8mm;
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
    
    /* Desactiva diseños flexbox durante la impresión para evitar escalado de vista de escritorio y recorte lateral derecho */
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
        padding: 8mm 6mm !important; /* Force physical narrow margins */
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
        font-size: 9pt !important;
        width: 100% !important;
        height: auto !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .glass-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        margin-bottom: 20px !important;
        padding: 0 !important;
    }
    
    table, .ts-table {
        display: table !important;
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 4px !important;
        margin-bottom: 15px !important;
        font-size: 8.5pt !important;
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
    
    .print-grid-7 {
        display: grid !important;
        grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
        gap: 0.25rem !important;
        width: 100% !important;
        margin-bottom: 8px !important;
    }
    
    .print-grid-7 > div,
    .print-grid-7 .glass-card {
        border: none !important;
        border-radius: 0 !important;
        padding: 2px 0 !important;
        background: transparent !important;
        background-color: transparent !important;
        text-align: center !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .print-grid-7 > div::before,
    .print-grid-7 > div::after,
    .print-grid-7 .glass-card::before,
    .print-grid-7 .glass-card::after {
        display: none !important;
        content: none !important;
        border: none !important;
    }
    
    .print-grid-7 p {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .print-grid-7 p:first-child,
    .print-grid-7 p.text-xs,
    .print-grid-7 p span {
        font-size: 7.5pt !important;
        color: #4b5563 !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        opacity: 1 !important;
    }
    
    .print-grid-7 p:last-child,
    .print-grid-7 p.text-base,
    .print-grid-7 p.text-xl,
    .print-grid-7 p.text-2xl {
        font-size: 9.5pt !important;
        font-weight: 600 !important;
        color: #111827 !important;
        margin-top: 1px !important;
        opacity: 1 !important;
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
    
    .no-print-emoji, table td span.no-print-emoji, .grid p span, .ts-table td span.mr-2, span.text-lg {
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
        margin-bottom: 4px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding-bottom: 3px !important;
    }

    .flex, .flex-col, .flex-wrap, .items-center, .justify-between {
        display: block !important;
    }
    .mb-4 {
        margin-bottom: 4px !important;
    }
    .overflow-x-auto {
        overflow: visible !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .no-print {
        display: none !important;
    }
    .print-date {
        display: block !important;
        font-size: 8pt !important;
        color: #4a5568 !important;
        margin-top: 2px !important;
        margin-bottom: 6px !important;
        font-weight: bold !important;
    }
    .glass-card {
        margin-top: 4px !important;
        margin-bottom: 4px !important;
    }
    .space-y-5 > * + *,
    .space-y-5 > :not([hidden]) ~ :not([hidden]) {
        margin-top: 4px !important;
    }
    .space-y-5, .space-y-5 > div, .space-y-5 > .grid, .space-y-5 > .glass-card, .glass-card.mt-4 {
        margin-top: 4px !important;
        margin-bottom: 4px !important;
    }
}
</style>
@endsection
