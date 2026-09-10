@extends('layouts.app')

@section('content')



<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">🧾</span> Facturas de Inventario
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Historial de compras y ventas de artículos</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
   <div class="relative">
    <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
    <input type="text" id="search-facturas" placeholder="Buscar factura..." class="glass-input pl-9 w-48 sm:w-64 font-semibold" onkeydown="if(event.key === 'Enter'){ event.preventDefault(); }">
   </div>
   @if(!auth()->user()->isInvitado())
    <a href="{{ route('inventario.compra.create') }}" class="btn-compra">
    📦 Nueva Compra
    </a>
    <a href="{{ route('inventario.venta.create') }}" class="btn-venta">
    🛒 Nueva Venta
    </a>
   @endif
 </div>
 </div>

 {{-- Filtros --}}
  <form method="GET" class="flex flex-wrap items-center gap-3 mb-6 p-5 glass-card no-print relative z-50">
 <select name="tipo" class="glass-input no-search w-48 font-semibold">
 <option value="todos" {{ request('tipo') === 'todos' || !request('tipo') ? 'selected' : '' }}>Todos los tipos</option>
 <option value="compra" {{ request('tipo') === 'compra' ? 'selected' : '' }}>📦 Compras</option>
 <option value="venta" {{ request('tipo') === 'venta' ? 'selected' : '' }}>🛒 Ventas</option>
 </select>
 <select name="estado" class="glass-input no-search w-48 font-semibold">
 <option value="todos" {{ request('estado') === 'todos' || !request('estado') ? 'selected' : '' }}>Todos los estados</option>
 <option value="emitida" {{ request('estado') === 'emitida' ? 'selected' : '' }}>✅ Emitida</option>
 <option value="pendiente_pago" {{ request('estado') === 'pendiente_pago' ? 'selected' : '' }}>⏳ Pendiente</option>
 <option value="anulada" {{ request('estado') === 'anulada' ? 'selected' : '' }}>🚫 Anulada</option>
 </select>
 <input type="text" name="valor_total" value="{{ request('valor_total') }}" placeholder="Valor Total" class="glass-input w-40 font-semibold" oninput="let val = this.value.replace(/\D/g, ''); this.value = val === '' ? '' : parseInt(val, 10).toLocaleString('es-CO');">
 <div class="flex items-center gap-2">
 <input type="date" name="fecha_desde" value="{{ request('fecha_desde', date('Y-m-01')) }}" class="glass-input w-44">
 <span class="text-gray-400 text-sm">a</span>
 <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta', date('Y-m-d')) }}" class="glass-input w-44">
 </div>
 <button type="submit" class="btn-primary py-2 px-4 text-sm">🌪️ Filtrar</button>
 <a href="{{ route('inventario.facturas') }}" class="btn-clean text-sm">🧹 Limpiar</a>
 </form>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-facturas" class="ts-table responsive-table w-full">
 <thead>
 <tr>
 <th class="text-center">Número</th>
 <th class="text-center">Tipo</th>
 <th>Entidad (Cliente/Prov.)</th>
 <th class="text-center">Fecha</th>
 <th class="text-right">Total</th>
 <th class="text-right">Pagado</th>
 <th class="text-center">Util / Pérd</th>
 <th class="text-center">Estado</th>
 <th class="text-center w-28">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($facturas as $f)
  @php
    $dim = $f->estado === 'anulada' ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $dimLight = $f->estado === 'anulada' ? 'opacity-60' : '';
  @endphp
   <tr id="factura-{{ $f->id }}" class="scroll-mt-[6.5rem]">
  <td data-label="Número:" class="text-center font-mono font-bold text-sm text-slate-700 dark:text-slate-300 {{ $dim }}">{{ $f->numero_factura }}</td>
  <td data-label="Tipo:" class="text-center {{ $dimLight }}">
  <span class="pill {{ $f->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done' }}">
  {{ $f->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta' }}
  </span>
  </td>
  <td data-label="Entidad:" class="{{ $dim }}">
  @if($f->facturable)
  @if(class_basename($f->facturable) === 'Cliente')
  <a href="{{ route('clientes.index') }}#cliente-{{ $f->facturable->id }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de clientes">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  👤 {{ $f->facturable->nombre }}
  </div>
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $f->facturable->identificacion ?? 'Cliente' }}
  </div>
  </a>
  @else
  <a href="{{ route('proveedores.index') }}#proveedor-{{ $f->facturable->id }}" class="group block hover:opacity-75 transition-opacity" title="Ver en tabla de proveedores">
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
  🏢 {{ $f->facturable->nombre_razon_social }}
  </div>
  <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
  {{ $f->facturable->identificacion ?? 'Proveedor' }}
  </div>
  </a>
  @endif
  @else
  <span class="text-gray-400 font-bold">—</span>
  @endif
  </td>
  <td data-label="Fecha:" class="text-center font-medium {{ $dim }}">{{ $f->fecha->format('d/m/Y') }}</td>
  <td data-label="Total:" class="text-right font-black text-slate-800 dark:text-white text-base {{ $dim }}">
  ${{ number_format($f->total_documento, 0, ',', '.') }}
  </td>
  <td data-label="Pagado:" class="text-right {{ $dim }}">
  <span class="font-bold text-sm {{ $f->saldo_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
  ${{ number_format($f->total_pagado, 0, ',', '.') }}
  </span>
  @if($f->saldo_pendiente > 0 && $f->estado !== 'anulada')
  <div class="text-[10px] text-red-500 uppercase tracking-tight mt-0.5 font-bold">Saldo: ${{ number_format($f->saldo_pendiente, 0, ',', '.') }}</div>
  @endif
  </td>
  <td data-label="Util / Pérd:" class="text-center {{ $dim }}">
  @if($f->estado === 'anulada')
      <span class="text-gray-400 text-xs font-semibold">—</span>
  @elseif($f->tipo_movimiento === 'venta')
      @if($f->utilidad > 0)
          <span class="inline-flex items-center gap-1 font-bold text-xs px-2.5 py-1 rounded-lg whitespace-nowrap shadow-sm" style="background: rgba(16, 185, 129, 0.15) !important; color: #10b981 !important; border: 1px solid rgba(16, 185, 129, 0.35) !important;" title="Utilidad estimada: +${{ number_format($f->utilidad, 0, ',', '.') }}">
              + ${{ number_format($f->utilidad, 0, ',', '.') }}
          </span>
      @elseif($f->utilidad < 0)
          <span class="inline-flex items-center gap-1 font-bold text-xs px-2.5 py-1 rounded-lg whitespace-nowrap shadow-sm" style="background: rgba(239, 68, 68, 0.18) !important; color: #f87171 !important; border: 1px solid rgba(239, 68, 68, 0.40) !important;" title="Pérdida en la venta (Margen negativo): -${{ number_format(abs($f->utilidad), 0, ',', '.') }}">
            - ${{ number_format(abs($f->utilidad), 0, ',', '.') }}
          </span>
      @else
          <span class="text-xs text-gray-400 font-semibold">$0</span>
      @endif
  @else
      <span class="text-gray-400 text-xs font-semibold" title="No aplica para compras de inventario">—</span>
  @endif
  </td>
  <td data-label="Estado:" class="text-center">
  @php
  $stClass = 'pill-pending';
  if($f->estado === 'emitida') $stClass = 'pill-done';
  if($f->estado === 'anulada') $stClass = 'pill-anulado';
  
  $label = ucfirst(str_replace('_', ' ', $f->estado));
  if($f->estado === 'pendiente_pago') $label = 'Pendiente';
  @endphp
  <span class="pill {{ $stClass }}">
  {{ $label }}
  </span>
  </td>
<td data-label="Acciones:" class="text-center w-28 {{ $dim }}">
  <div class="actions-grid">
  <a href="{{ route('inventario.facturas.show', $f->id) }}" class="btn-ghost btn-action-view w-8 h-8 flex items-center justify-center p-0 text-xs text-blue-600 dark:text-blue-400" title="Ver Detalles">👁️</a>
  <a href="{{ route('inventario.facturas.print', $f->id) }}" target="_blank" class="btn-ghost btn-action-print w-8 h-8 flex items-center justify-center p-0 text-xs text-emerald-600 dark:text-emerald-400" title="Imprimir">🖨️</a>
  
  @if(!auth()->user()->isInvitado())
  <a href="{{ route('inventario.facturas.edit', $f->id) }}" class="btn-ghost btn-action-edit w-8 h-8 flex items-center justify-center p-0 text-xs text-yellow-600 dark:text-yellow-400" title="Editar">✏️</a>
  
  <button type="button" onclick="openAnularModal('{{ route('inventario.facturas.anular', $f->id) }}', {{ $f->estado === 'anulada' ? 'true' : 'false' }})" class="btn-ghost {{ $f->estado === 'anulada' ? 'btn-action-reactivar text-emerald-600 dark:text-emerald-400' : 'btn-action-anular text-red-600 dark:text-red-400' }} w-8 h-8 flex items-center justify-center p-0 text-xs" title="{{ $f->estado === 'anulada' ? 'Reactivar Factura' : 'Anular Factura' }}">
  {{ $f->estado === 'anulada' ? '✅' : '🚫' }}
  </button>
  @else
  <span class="text-xs text-gray-400 font-medium">👁️ Lectura</span>
  @endif
  </div>
  </td>
  </tr>
 @empty
 <tr>
 <td colspan="9" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🧾</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin facturas registradas</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">No se han realizado compras ni ventas de inventario.</p>
 @if(!auth()->user()->isInvitado())
 <div class="flex gap-3 justify-center">
  <a href="{{ route('inventario.compra.create') }}" class="btn-compra">📦 Comprar</a>
  <a href="{{ route('inventario.venta.create') }}" class="btn-venta">🛒 Vender</a>
 </div>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 
  @if($facturas->hasPages())
  <div class="mt-5 flex justify-end">
  {{ $facturas->appends(request()->query())->links() }}
  </div>
  @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof filterTable === 'function') {
            filterTable('search-facturas', 'tabla-facturas');
        }
    });
</script>
@endsection
