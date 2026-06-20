@extends('layouts.app')
@section('content')
<style>
 /* Fila resaltada al llegar por ancla (#electronica-id) */
 tr.active-target {
 background-color: rgba(168, 85, 247, 0.1) !important;
 outline: 2px solid rgba(168, 85, 247, 0.5);
 outline-offset: -2px;
 }
 .dark tr.active-target {
 background-color: rgba(168, 85, 247, 0.2) !important;
 }
</style>

<div class="glass-card p-6">

 <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">⚡</span> Registros de Electrónica
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona las reparaciones de electrónica de consumo</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-electronicas" placeholder="Buscar cliente, dispositivo..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('electronicas.create') }}" class="btn-primary text-sm">
 ➕ Nuevo Registro
 </a>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-electronicas" class="ts-table table-electronica responsive-table w-full">
 <thead>
 <tr>
 <th class="w-20 text-center">Orden</th>
 <th class="text-center">Equipo</th>
 <th class="text-center">Cliente</th>
 <th class="text-center">Técnico</th>
 <th class="text-center">Tipo</th>
 <th class="text-center">Observación</th>
 <th class="text-center">Costo</th>
 <th class="text-center">Progreso</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-24">Entrada</th>
 <th class="text-center w-24">Salida</th>
 <th class="text-center w-32">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($electronicas as $e)
 <tr id="electronica-{{ $e->id }}" class="{{ $e->anulado ? 'row-anulado' : '' }} hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
 <td data-label="Orden:" class="font-bold text-center whitespace-nowrap">
 <a href="#electronica-{{ $e->id }}" class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 hover:underline transition-colors">
 {{ $e->id_orden }}
 </a>
 </td>
 
 <td data-label="Equipo:">
 <a href="{{ route('equipos.index') }}#equipo-{{ $e->equipo_id }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
 <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
 {{ $e->equipo->nombre ?? '-' }}
 </div>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 {{ $e->equipo->marca ?? '' }} {{ $e->equipo->modelo ?? '' }}
 </div>
 <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase">
 SN: {{ $e->equipo->serie ?? 'N/A' }}
 </div>
 </a>
 </td>
 
 <td data-label="Cliente:">
 <a href="{{ route('clientes.index') }}#cliente-{{ $e->equipo->cliente_id ?? '' }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
 <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
 {{ $e->equipo->cliente->nombre ?? 'N/A' }}
 </div>
 <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 {{ $e->equipo->cliente->identificacion ?? 'N/A' }}
 </div>
 </a>
 </td>
 
 <td data-label="Técnico:" class="text-center">
 <span class="font-medium text-slate-700 dark:text-slate-300">{{ $e->tecnico->nombre ?? 'N/A' }}</span>
 </td>
 
 <td data-label="Tipo:" class="text-center">
 <span class="pill {{ $e->tipo === 'correctivo' ? 'pill-correctivo' : 'pill-preventivo' }}">
 {{ ucfirst($e->tipo) }}
 </span>
 </td>
 
 <td data-label="Observación:" class="max-w-[250px]">
 <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-normal break-words leading-relaxed font-medium">
 {{ $e->descripcion_problema }}
 </p>
 </td>

 <td data-label="Costo:" class="text-right font-black text-purple-600 dark:text-purple-400">
 ${{ number_format($e->costo, 0, ',', '.') }}
 </td>
 
 <td data-label="Estado:" class="text-center">
 @php
 $estadoIcon = '⏳';
 if($e->estado === 'terminado') $estadoIcon = '✅';
 @endphp
 <span class="pill {{ $e->estado === 'terminado' ? 'pill-done' : 'pill-pending' }}">
 {{ $estadoIcon }} {{ ucfirst($e->estado) }}
 </span>
 </td>
 
 <td data-label="Estado:" class="text-center">
 <span class="pill {{ $e->anulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $e->anulado ? 'ANULADO' : 'ACTIVO' }}
 </span>
 </td>
 
 <td data-label="Entrada:" class="text-center text-slate-800 dark:text-slate-200">
 {{ \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y') }}
 @php 
 $fechaEntrada = \Carbon\Carbon::parse($e->fecha_entrada)->startOfDay();
 $fechaFin = $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->startOfDay() : \Carbon\Carbon::now()->startOfDay();
 $dias = $fechaEntrada->diffInDays($fechaFin);
 @endphp
 <div class="mt-1 text-xs font-bold {{ $dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400') }}">
 ({{ $dias }} d)
 </div>
 </td>
 
 <td data-label="Salida:" class="text-center {{ $e->fecha_salida ? 'text-slate-800 dark:text-white' : 'text-gray-400 italic' }}">
 {{ $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : '-' }}
 </td>
 
 <td data-label="Acciones:" class="text-center">
 <div class="flex justify-end md:justify-center gap-1.5 flex-wrap">
 <a href="{{ route('electronicas.show', $e->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Ver detalle">👁️</a>

 @if($e->estado === 'terminado' && $e->fecha_salida)
 <a href="{{ route('electronicas.factura', $e->id) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 hover:text-green-700 hover:bg-green-50/50" title="Imprimir Factura">🖨️</a>
 @elseif($e->estado === 'terminado')
 <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed" title="Requiere fecha de salida para facturar">🖨️</span>
 @endif
 
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('electronicas.edit', $e->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Editar">✏️</a>
 
 @if(!$e->anulado)
 <button type="button" onclick="openAnularModal('{{ route('electronicas.anular', $e->id) }}')" class="btn-ghost px-2.5 py-1.5 text-xs text-orange-600 border-orange-500/20 hover:bg-orange-500/10" title="Anular orden">🚫</button>
 @endif
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="12" class="p-16 text-center">
 <div class="flex flex-col items-center justify-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">⚡</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin registros de electrónica</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra la primera reparación electrónica para iniciar el control.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('electronicas.create') }}" class="btn-primary ">
 ➕ Nuevo Registro
 </a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 <div class="mt-6 flex justify-end">
 {{ $electronicas->appends(request()->query())->links() }}
 </div>
</div>
<script>
 document.addEventListener('DOMContentLoaded', () => {
 if(typeof filterTable === 'function') {
 filterTable('search-electronicas', 'tabla-electronicas');
 }
 });
, 300);
 }
 
 document.addEventListener('keydown', e => { 
 if (e.key === 'Escape') closeAnularModal(); 
 });
</script>
@endsection
