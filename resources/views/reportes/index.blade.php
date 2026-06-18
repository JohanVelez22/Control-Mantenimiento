@extends('layouts.app')

@section('title', 'Informes y Reportes')

@section('content')
<div class="flex gap-4 mb-6 no-print">
 <a href="{{ route('mantenimientos.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="{{ route('reportes.financiero.diario') }}" class="bg-amber-500 text-white px-4 py-2 rounded-xl font-bold shadow-sm">💵 Informes Financieros</a>
 <a href="{{ route('electronicas.reportes') }}" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Módulo Electrónica</a>
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

<div class="glass-card p-5 mb-4 no-print">
  <form action="{{ route('reportes.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
   <select name="mes" class="glass-input w-40 font-semibold">
   @for($i=1; $i<=12; $i++)
   <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
   @endfor
   </select>
   <select name="anio" class="glass-input w-28 font-semibold">
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
       <button type="submit" name="export" value="pdf" class="btn-pdf text-sm" title="Exportar a PDF">
       <span>📄</span> PDF
       </button>
       <button type="submit" name="export" value="excel" class="btn-excel text-sm" title="Exportar a Excel">
       <span>📊</span> Excel
       </button>
   </div>
  </form>
</div>

<div class="space-y-4">

 {{-- INFORME ACUMULADO --}}
 <div class="glass-card p-6">
 <h3 class="text-xl font-bold mb-4">📈 Informe Acumulado (Mes {{ $mes }}/{{ $anio }})</h3>
 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
  <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
  <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📈</span> Ingresos</p>
  <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['ingresos'], 0, ',', '.') }}</p>
  </div>
  <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
  <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
  <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📉</span> Egresos / Gastos</p>
  <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['egresos'], 0, ',', '.') }}</p>
  </div>
  <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
  <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
  <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💎</span> Facturación Total</p>
  <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['facturado_total'], 0, ',', '.') }}</p>
  </div>
  <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 {{ $acumulado['utilidad_neta'] >= 0 ? 'bg-teal-500/20 group-hover:bg-teal-500/30' : 'bg-orange-500/20 group-hover:bg-orange-500/30' }} rounded-full blur-2xl transition-all"></div>
 <p class="text-xs font-bold {{ $acumulado['utilidad_neta'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">⚖️</span> Utilidad / Saldo Neta</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($acumulado['utilidad_neta'], 0, ',', '.') }}</p>
 </div>
  </div>
  
  <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
  <span class="font-bold text-gray-600 dark:text-gray-300 text-sm">Valorización de Inventario (Costo)</span>
  <span class="font-black text-gray-800 dark:text-white">${{ number_format($acumulado['inventario_costo'], 0, ',', '.') }}</span>
  </div>
  <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
  <span class="font-bold text-gray-600 dark:text-gray-300 text-sm">Utilidad Esperada del Inventario</span>
  <span class="font-black text-blue-600 dark:text-blue-400">${{ number_format($acumulado['inventario_utilidad_esperada'], 0, ',', '.') }}</span>
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
  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
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
  <span class="font-black {{ $tx->tipo_movimiento === 'ingreso' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
  {{ $tx->tipo_movimiento === 'ingreso' ? '+' : '-' }}${{ number_format($tx->monto, 0, ',', '.') }}
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
 <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
 <div class="bg-gray-100 dark:bg-gray-800 p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
 <span class="font-bold">💵 Efectivo Global</span>
 <span class="font-black text-lg">${{ number_format($operaciones['efectivo'], 0, ',', '.') }}</span>
 </div>
 <div class="p-3 space-y-2 text-sm">
 <div class="flex justify-between items-center">
 <span class="text-green-600 font-semibold">Ingresos en Efectivo</span>
 <span class="font-bold text-green-700">+${{ number_format($operaciones['ingresos_efectivo'], 0, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center">
 <span class="text-red-600 font-semibold">Egresos en Efectivo</span>
 <span class="font-bold text-red-700">-${{ number_format($operaciones['egresos_efectivo'], 0, ',', '.') }}</span>
 </div>
 </div>
 </div>

 {{-- Consignacion --}}
 <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
 <div class="bg-gray-100 dark:bg-gray-800 p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
 <span class="font-bold">🏦 Consignación Global</span>
 <span class="font-black text-lg">${{ number_format($operaciones['consignacion'], 0, ',', '.') }}</span>
 </div>
 <div class="p-3 space-y-2 text-sm">
 <div class="flex justify-between items-center">
 <span class="text-green-600 font-semibold">Ingresos por Banco</span>
 <span class="font-bold text-green-700">+${{ number_format($operaciones['ingresos_consignacion'], 0, ',', '.') }}</span>
 </div>
 <div class="flex justify-between items-center">
 <span class="text-red-600 font-semibold">Egresos por Banco</span>
 <span class="font-bold text-red-700">-${{ number_format($operaciones['egresos_consignacion'], 0, ',', '.') }}</span>
 </div>
 </div>
 </div>
 </div>
 </div>



</div>
@endsection


