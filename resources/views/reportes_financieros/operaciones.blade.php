@extends('layouts.app')
@section('title', 'Informes y Reportes - Operaciones')

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
 <form method="GET" class="space-y-3">
   <div class="flex flex-wrap items-center gap-3">
   <select name="tipo" class="glass-input w-56 font-semibold">
   @foreach($tipoLabels as $val => $label)
   <option value="{{ $val }}" {{ $tipo === $val ? 'selected' : '' }}>{{ $label }}</option>
   @endforeach
   </select>
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
   </div>
   <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-200/50 dark:border-white/10">
   <label class="font-semibold text-sm">Desde:</label>
   <input type="date" name="desde" value="{{ $desde->toDateString() }}" class="glass-input w-44">
   <label class="font-semibold text-sm">Hasta:</label>
   <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="glass-input w-44">
   <button class="btn-primary py-2 px-5 text-sm" title="Filtrar">🔍 Filtrar</button>
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
 <th class="p-2 text-center">Costo</th><th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th>
 </tr>
 </thead>
 <tbody>
 @foreach($registros as $m)
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($m->anulado) ? 'row-anulado' : '' }}">
 <td class="p-2 font-mono font-bold whitespace-nowrap">
 <a href="{{ route('mantenimientos.index', ['locate' => $m->id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
 {{ $m->id_orden }}
 </a>
 </td>
 <td class="p-2 text-left">
 <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
 <span class="font-bold group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $m->equipo->nombre ?? '—' }}</span>
 </a> 
 <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
 <span class="text-xs text-gray-500 font-semibold group-hover:text-blue-600 dark:group-hover:text-blue-400">({{ $m->equipo->cliente->nombre ?? '—' }})</span>
 </a>
 </td>
 <td class="p-2">{{ $m->tecnico->nombre ?? '—' }}</td>
 <td class="p-2">{{ $m->fecha_entrada->format('d/m/Y') }}</td>
 <td class="p-2 font-bold text-blue-600">${{ number_format($m->costo, 0, ',', '.') }}</td>
 <td class="p-2"><span class="pill pill-efectivo {{ !empty($m->anulado) ? 'line-through opacity-70' : '' }}">{{ ucfirst($m->estado) }}</span></td>
 <td class="p-2">
 <span class="pill {{ !empty($m->anulado) ? 'pill-anulado' : 'pill-done' }}">
 {{ !empty($m->anulado) ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 </tr>
 @endforeach
 </tbody>
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
 <th class="p-2 text-center">Costo</th><th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th>
 </tr>
 </thead>
 <tbody>
 @foreach($registros as $e)
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($m->anulado) ? 'row-anulado' : '' }}">
 <td class="p-2 font-mono font-bold whitespace-nowrap">
 <a href="{{ route('electronicas.index', ['locate' => $e->id]) }}" class="text-purple-600 dark:text-purple-400 hover:underline">
 {{ $e->id_orden }}
 </a>
 </td>
 <td class="p-2 text-left">
 <a href="{{ route('equipos.index') }}#equipo-{{ $e->equipo_id }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
 <span class="font-bold group-hover:text-purple-600 dark:group-hover:text-purple-400">{{ $e->equipo->nombre ?? '—' }}</span>
 </a> 
 <a href="{{ route('clientes.index') }}#cliente-{{ $e->equipo->cliente_id ?? '' }}" class="group hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
 <span class="text-xs text-gray-500 font-semibold group-hover:text-purple-600 dark:group-hover:text-purple-400">({{ $e->equipo->cliente->nombre ?? '—' }})</span>
 </a>
 </td>
 <td class="p-2">{{ $e->tecnico->nombre ?? '—' }}</td>
 <td class="p-2">{{ $e->fecha_entrada->format('d/m/Y') }}</td>
 <td class="p-2 font-bold text-purple-600">${{ number_format($e->costo, 0, ',', '.') }}</td>
 <td class="p-2"><span class="pill pill-pending {{ !empty($e->anulado) ? 'line-through opacity-70' : '' }}">{{ ucfirst($e->estado) }}</span></td>
 <td class="p-2">
 <span class="pill {{ !empty($e->anulado) ? 'pill-anulado' : 'pill-done' }}">
 {{ !empty($e->anulado) ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 </tr>
 @endforeach
 </tbody>
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
 <th class="p-2 text-center">Monto</th><th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th>
 </tr>
 </thead>
 <tbody>
 @foreach($registros as $c)
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($m->anulado) ? 'row-anulado' : '' }}">
 <td class="p-2">{{ $c->fecha->format('d/m/Y') }}</td>
 <td class="p-2 text-left">{{ $c->persona ?? $c->empresa ?? '—' }}</td>
 <td class="p-2 text-xs">{{ $c->concepto->nombre ?? '—' }}</td>
 <td class="p-2 text-xs capitalize">{{ $c->tipo_pago }}</td>
 <td class="p-2 font-bold {{ $tipo === 'solo_ingresos' ? 'text-green-600' : 'text-red-600' }}">${{ number_format($c->monto, 0, ',', '.') }}</td>
 <td class="p-2"><span class="pill pill-especialidad {{ !empty($c->anulado) ? 'line-through opacity-70' : '' }}">Procesado</span></td>
 <td class="p-2">
 <span class="pill {{ !empty($c->anulado) ? 'pill-anulado' : 'pill-done' }}">
 {{ !empty($c->anulado) ? 'Anulado' : 'Activo' }}
 </span>
 </td>
 </tr>
 @endforeach
 </tbody>
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
  <th class="p-2 text-center">Total</th>
  <th class="p-2 text-center">Pagado</th>
  <th class="p-2 text-center">Estado</th>
  </tr>
  </thead>
  <tbody>
  @foreach($registros as $f)
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ !empty($m->anulado) ? 'row-anulado' : '' }}">
  <td class="p-2">{{ $f->fecha->format('d/m/Y') }}</td>
  <td class="p-2 font-mono font-bold">{{ $f->numero_factura }}</td>
  <td class="p-2 text-left">{{ $f->facturable->nombre ?? $f->facturable->nombre_razon_social ?? '—' }}</td>
  <td class="p-2 font-bold text-blue-600">${{ number_format($f->total_documento, 0, ',', '.') }}</td>
  <td class="p-2 font-semibold text-emerald-600">${{ number_format($f->total_pagado, 0, ',', '.') }}</td>
  <td class="p-2"><span class="pill pill-preventivo {{ $f->estado === 'anulada' ? 'line-through opacity-70' : '' }}">Emitida</span></td>
  </tr>
  @endforeach
  </tbody>
  </table>
  </div>
  <div class="mt-6 flex justify-end">
  {{ $registros->appends(request()->query())->links() }}
  </div>
  @endif
  @endif
 </div>
@endsection
