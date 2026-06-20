@extends('layouts.app')
@section('title', 'Informes y Reportes - Acumulado')

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

<div class="glass-card p-5 mb-4 no-print">
 <form method="GET" class="flex flex-wrap items-center gap-3">
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
      <button type="submit" name="export" value="pdf" class="btn-pdf text-sm" title="Exportar a PDF">
      <span>📄</span> PDF
      </button>
      <button type="submit" name="export" value="excel" class="btn-excel text-sm" title="Exportar a Excel">
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

 {{-- Tabla de movimientos del período --}}
 <div class="glass-card p-6 md:p-8 mt-4">
 <div class="flex justify-between items-center mb-4">
 <h3 class="text-lg font-bold">Detalle de Movimientos del Período ({{ $movimientos->count() }})</h3>
 </div>

 @if($movimientos->isEmpty())
 <div class="flex flex-col items-center justify-center space-y-3 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm p-12 rounded-2xl border border-white/20 my-4">
     <div class="text-5xl opacity-80">📭</div>
     <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
     <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No hubo movimientos en este período.</p>
 </div>
 @else
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm">
 <thead>
 <tr>
 <th class="p-3 text-center">Fecha</th>
 <th class="p-3 text-center">Tipo</th>
 <th class="p-3 text-left">Descripción</th>
 <th class="p-3 text-center">Progreso</th>
 <th class="p-3 text-center">Estado</th>
 <th class="p-3 text-center">Monto</th>
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
 <td class="p-3 text-xs text-gray-500 {{ $dim }}">{{ \Carbon\Carbon::parse($mov['fecha'])->format('d/m/Y') }}</td>
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
 <td class="p-3 font-bold {{ in_array($mov['tipo'], ['ingreso','venta','mantenimiento','electronica']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} {{ $dim }}">
 ${{ number_format($mov['monto'] ?? 0, 0, ',', '.') }}
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @endif
 </div>

</div>
@endsection
