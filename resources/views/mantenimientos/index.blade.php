@extends('layouts.app')
@section('content')
<style>
  /* Fila resaltada al llegar por ancla (#mantenimiento-id) */
  tr.active-target {
  background-color: rgba(59, 130, 246, 0.1) !important;
  outline: 2px solid rgba(59, 130, 246, 0.5);
  outline-offset: -2px;
  }
  .dark tr.active-target {
  background-color: rgba(59, 130, 246, 0.2) !important;
  }
</style>

<div class="glass-card p-6">
  <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
  <div>
  <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
  <span class="text-3xl">🔧</span> Órdenes de Mantenimiento
  </h2>
  <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestiona los mantenimientos de equipos de los clientes</p>
  </div>
  <div class="flex flex-wrap items-center gap-3">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-mantenimientos" placeholder="Buscar orden..." class="glass-input pl-9 w-48 sm:w-64 text-sm">
  </div>
  @if(!auth()->user()->isInvitado())
  <a href="{{ route('mantenimientos.create') }}" class="btn-primary text-sm">
  ➕ Nueva Orden
  </a>
  @endif
  </div>
  </div>

  <div class="overflow-x-auto pb-2">
  <table id="tabla-mantenimientos" class="ts-table responsive-table w-full">
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
  @forelse($mantenimientos as $m)
  @php 
    $dim = $m->anulado ? 'opacity-60 grayscale line-through text-gray-400 dark:text-gray-500' : '';
    $dimLight = $m->anulado ? 'opacity-60' : '';
  @endphp
  <tr id="mantenimiento-{{ $m->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
  <td data-label="Orden:" class="font-bold text-center whitespace-nowrap {{ $dim }}">
  <a href="#mantenimiento-{{ $m->id }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors">
  {{ $m->id_orden }}
  </a>
  </td>
  
  <td class="{{ $dim }}">
  <a href="{{ route('equipos.index') }}#equipo-{{ $m->equipo_id }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de equipos">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  {{ $m->equipo->nombre ?? '-' }}
  </div>
  <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}
  </div>
  <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase">
  SN: {{ $m->equipo->serie ?? 'N/A' }}
  </div>
  </a>
  </td>
  
  <td class="{{ $dim }}">
  <a href="{{ route('clientes.index') }}#cliente-{{ $m->equipo->cliente_id ?? '' }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  {{ $m->equipo->cliente->nombre ?? '-' }}
  </div>
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $m->equipo->cliente->identificacion ?? '-' }}
  </div>
  </a>
  </td>
  
  <td class="text-center font-medium text-sm {{ $dim }}">{{ $m->tecnico->nombre ?? '-' }}</td>
  
  <td class="text-center {{ $dimLight }}">
  <span class="pill {{ $m->tipo === 'correctivo' ? 'pill-correctivo' : 'pill-preventivo' }} {{ $m->anulado ? 'line-through opacity-70' : '' }}">
  {{ ucfirst($m->tipo) }}
  </span>
  <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mt-1">{{ $m->reparacion }}</div>
  </td>
  
  <td class="max-w-[250px] {{ $dim }}">
  <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-normal break-words leading-relaxed font-medium">
  {{ $m->descripcion ?? '-' }}
  </p>
  </td>
  
  <td class="text-right font-black text-blue-600 dark:text-cyan-400 {{ $dim }}">
  ${{ number_format($m->costo, 0, ',', '.') }}
  </td>
  
  <td class="text-center {{ $dimLight }}">
  @php
  $estadoIcon = '⏳';
  if(in_array($m->estado, ['terminado', 'entregado'])) $estadoIcon = '✅';
  elseif($m->estado === 'en_proceso') $estadoIcon = '⚙️';
  elseif($m->estado === 'reparado') $estadoIcon = '🔧';
  @endphp
  <span class="pill {{ in_array($m->estado, ['terminado', 'entregado']) ? 'pill-done' : 'pill-pending' }} {{ $m->anulado ? 'line-through opacity-70' : '' }}">
  {{ $estadoIcon }} {{ ucfirst($m->estado) }}
  </span>
  </td>
  
  <td class="text-center">
  <span class="pill {{ $m->anulado ? 'pill-anulado' : 'pill-done' }}">
  {{ $m->anulado ? 'ANULADO' : 'ACTIVO' }}
  </span>
  </td>
  
  <td class="text-center text-slate-800 dark:text-slate-200 {{ $dim }}">
  {{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}
  @php 
  $fechaEntrada = \Carbon\Carbon::parse($m->fecha_entrada)->startOfDay();
  $fechaFin = $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->startOfDay() : \Carbon\Carbon::now()->startOfDay();
  $dias = $fechaEntrada->diffInDays($fechaFin);
  @endphp
  <div class="mt-1 text-xs font-bold {{ $dias > 14 ? 'text-red-600 dark:text-red-400' : ($dias > 7 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-500 dark:text-gray-400') }}">
  ({{ $dias }} d)
  </div>
  </td>
  
  <td class="text-center {{ $m->fecha_salida ? 'text-slate-800 dark:text-white' : 'text-gray-400 italic' }} {{ $dim }}">
  {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '-' }}
  </td>
  
  <td class="text-center {{ $dim }}">
  <div class="flex justify-center gap-1.5 flex-wrap">
  <a href="{{ route('mantenimientos.show', $m->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Ver detalle">👁️</a>
  
  @if($m->estado === 'terminado' && $m->fecha_salida)
  <a href="{{ route('mantenimientos.factura', $m->id) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs text-green-600 hover:text-green-700 hover:bg-green-50/50" title="Factura POS">🖨️</a>
  @elseif($m->estado === 'terminado')
  <span class="btn-ghost px-2.5 py-1.5 text-xs opacity-50 cursor-not-allowed" title="Requiere fecha de salida para facturar">🖨️</span>
  @endif

  @if(!auth()->user()->isInvitado())
  <a href="{{ route('mantenimientos.edit', $m->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs" title="Editar">✏️</a>

  @if(!$m->anulado)
  <button type="button" onclick="openAnularModal('{{ route('mantenimientos.anular', $m->id) }}')" class="btn-ghost px-2.5 py-1.5 text-xs text-orange-600 border-orange-500/20 hover:bg-orange-500/10" title="Anular orden">🚫</button>
  @endif
  @endif
  </div>
  </td>
  </tr>
  @empty
  <tr>
  <td colspan="12" class="p-16 text-center">
  <div class="flex flex-col items-center justify-center gap-3">
  <div class="text-6xl drop-shadow-md mb-2">🔧</div>
  <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin mantenimientos registrados</h3>
  <p class="text-gray-500 font-medium max-w-sm mb-4">Registra la primera orden de mantenimiento de un equipo.</p>
  @if(!auth()->user()->isInvitado())
  <a href="{{ route('mantenimientos.create') }}" class="btn-primary">
  ➕ Crear Primera Orden
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
  {{ $mantenimientos->appends(request()->query())->links() }}
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
  if(typeof filterTable === 'function') {
  filterTable('search-mantenimientos', 'tabla-mantenimientos');
  }
  });
</script>
@endsection
