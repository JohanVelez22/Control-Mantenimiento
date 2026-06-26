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
 <div class="flex flex-wrap gap-3">
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('inventario.compra.create') }}" class="btn-compra" style="padding: 9px 18px; font-size: 13px;">
 📦 Nueva Compra
 </a>
 <a href="{{ route('inventario.venta.create') }}" class="btn-venta" style="padding: 9px 18px; font-size: 13px;">
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
 <div class="flex items-center gap-2">
 <input type="date" name="fecha_desde" value="{{ request('fecha_desde', date('Y-m-01')) }}" class="glass-input w-44">
 <span class="text-gray-400 text-sm">a</span>
 <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta', date('Y-m-d')) }}" class="glass-input w-44">
 </div>
 <button type="submit" class="btn-primary py-2 px-4 text-sm">🌪️ Filtrar</button>
 <a href="{{ route('inventario.facturas') }}" class="btn-clean text-sm">🧹 Limpiar</a>
 </form>

 <div class="overflow-x-auto pb-2">
 <table class="ts-table">
 <thead>
 <tr>
 <th class="text-center">Número</th>
 <th class="text-center">Tipo</th>
 <th>Entidad (Cliente/Prov.)</th>
 <th class="text-center">Fecha</th>
 <th class="text-right">Total</th>
 <th class="text-right">Pagado</th>
 <th class="text-center">Estado</th>
 <th class="text-center">Acciones</th>
 </tr>
 </thead>
 <tbody>
 @forelse($facturas as $f)
  @php
    $dim = $f->estado === 'anulada' ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $dimLight = $f->estado === 'anulada' ? 'opacity-60' : '';
  @endphp
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
  <td class="text-center font-mono font-bold text-sm text-slate-700 dark:text-slate-300 {{ $dim }}">{{ $f->numero_factura }}</td>
  <td class="text-center {{ $dimLight }}">
  <span class="pill {{ $f->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done' }}">
  {{ $f->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta' }}
  </span>
  </td>
  <td class="{{ $dim }}">
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
  <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
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
  <td class="text-center font-medium {{ $dim }}">{{ $f->fecha->format('d/m/Y') }}</td>
  <td class="text-right font-black text-slate-800 dark:text-white text-base {{ $dim }}">
  ${{ number_format($f->total_documento, 0, ',', '.') }}
  </td>
  <td class="text-right {{ $dim }}">
  <span class="font-bold text-sm {{ $f->saldo_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
  ${{ number_format($f->total_pagado, 0, ',', '.') }}
  </span>
  @if($f->saldo_pendiente > 0 && $f->estado !== 'anulada')
  <div class="text-[10px] text-red-500 uppercase tracking-tight mt-0.5 font-bold">Saldo: ${{ number_format($f->saldo_pendiente, 0, ',', '.') }}</div>
  @endif
  </td>
  <td class="text-center">
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
  <td class="text-center {{ $dim }}">
  <div class="flex justify-center md:justify-end gap-1.5 flex-wrap">
  <a href="{{ route('inventario.facturas.show', $f->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-indigo-600" title="Ver Detalles">👁️</a>
  <a href="{{ route('inventario.facturas.print', $f->id) }}" target="_blank" class="btn-ghost px-2.5 py-1.5 text-xs" title="Imprimir">🖨️</a>
  
  @if(!auth()->user()->isInvitado())
  <a href="{{ route('inventario.facturas.edit', $f->id) }}" class="btn-ghost px-2.5 py-1.5 text-xs text-yellow-600 border-yellow-500/20 hover:bg-yellow-500/10" title="Editar">✏️</a>
  
  <button type="button" onclick="openAnularModal('{{ route('inventario.facturas.anular', $f->id) }}')" class="btn-ghost px-2.5 py-1.5 text-xs {{ $f->estado === 'anulada' ? 'text-emerald-600 border-emerald-500/20 hover:bg-emerald-500/10' : 'text-red-600 border-red-500/20 hover:bg-red-500/10' }}" title="{{ $f->estado === 'anulada' ? 'Reactivar Factura' : 'Anular Factura' }}">
  {{ $f->estado === 'anulada' ? '✅' : '🚫' }}
  </button>
  @endif
  </div>
  </td>
  </tr>
 @empty
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">🧾</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin facturas registradas</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">No se han realizado compras ni ventas de inventario.</p>
 @if(!auth()->user()->isInvitado())
 <div class="flex gap-3 justify-center">
 <a href="{{ route('inventario.compra.create') }}" class="btn-compra" style="padding: 9px 18px; font-size: 13px;">📦 Comprar</a>
 <a href="{{ route('inventario.venta.create') }}" class="btn-venta" style="padding: 9px 18px; font-size: 13px;">🛒 Vender</a>
 </div>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 
 <div class="mt-6 flex justify-end">
 {{ $facturas->appends(request()->query())->links() }}
 </div>
</div>


@endsection
