@extends('layouts.app')

@section('content')
<div class="space-y-4">
 {{-- Tarjetas de totales Glassmorphism --}}
 <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📈</span> Ingresos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['ingresos'], 0, ',', '.') }}</p>
 </div>
 
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
 <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📉</span> Egresos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['egresos'], 0, ',', '.') }}</p>
 </div>
 
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💵</span> Efectivo</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['efectivo'], 0, ',', '.') }}</p>
 </div>
 
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
 <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">🏦</span> Banco/Consig.</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['consignacion'], 0, ',', '.') }}</p>
 </div>
 
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group col-span-2 lg:col-span-1 text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 {{ $totales['saldo'] >= 0 ? 'bg-teal-500/20 group-hover:bg-teal-500/30' : 'bg-orange-500/20 group-hover:bg-orange-500/30' }} rounded-full blur-2xl transition-all"></div>
 <p class="text-xs font-bold {{ $totales['saldo'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">⚖️</span> Saldo General</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['saldo'], 0, ',', '.') }}</p>
 </div>
 </div>

 {{-- Panel principal --}}
 <div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">💰</span> Movimientos de Caja
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestión de ingresos, egresos y flujo de efectivo</p>
 </div>
 <div class="flex flex-wrap items-center gap-2">
 @if(!auth()->user()->isInvitado())
 <div class="flex gap-3">
 <a href="{{ route('conceptos.index') }}" class="btn-concepts" style="padding: 9px 18px; font-size: 13px;">
 🏷️ <span class="hidden sm:inline">Gestionar Conceptos</span>
 </a>
 <a href="{{ route('caja.create') }}" class="btn-primary flex items-center gap-2 shadow-lg shadow-indigo-500/30" style="padding: 9px 18px; font-size: 13px;">
 <span>➕</span> <span class="hidden sm:inline">Nuevo Movimiento</span>
 </a>
 </div>
 @endif
 </div>
 </div>

 {{-- Filtros --}}
  <form action="{{ route('caja.index') }}" method="GET" class="flex flex-wrap items-center gap-3 mb-6 p-5 glass-card no-print relative z-50">
 <div class="relative">
 <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
 <input type="text" name="search" value="{{ request('search') }}" placeholder="Persona o empresa..." class="glass-input pl-9 w-48 sm:w-64 text-sm h-[42px]">
 </div>
 <select name="tipo_movimiento" class="glass-input w-48 text-sm font-semibold no-search h-[42px]">
 <option value="todos" {{ request('tipo_movimiento') === 'todos' || !request('tipo_movimiento') ? 'selected' : '' }}>Todos los tipos</option>
 <option value="ingreso" {{ request('tipo_movimiento') === 'ingreso' ? 'selected' : '' }}>📈 Ingreso</option>
 <option value="egreso" {{ request('tipo_movimiento') === 'egreso' ? 'selected' : '' }}>📉 Egreso</option>
 </select>
 <select name="tipo_pago" class="glass-input w-48 text-sm font-semibold no-search h-[42px]">
 <option value="todos" {{ request('tipo_pago') === 'todos' || !request('tipo_pago') ? 'selected' : '' }}>Todos los pagos</option>
 <option value="efectivo" {{ request('tipo_pago') === 'efectivo' ? 'selected' : '' }}>💵 Efectivo</option>
 <option value="consignacion" {{ request('tipo_pago') === 'consignacion' ? 'selected' : '' }}>🏦 Consignación</option>
 </select>
 <div class="flex items-center gap-2">
 <input type="date" name="fecha_desde" value="{{ request('fecha_desde', date('Y-m-01')) }}" class="glass-input w-36 sm:w-44 text-sm h-[42px]">
 <span class="text-gray-400 text-sm">a</span>
 <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta', date('Y-m-d')) }}" class="glass-input w-36 sm:w-44 text-sm h-[42px]">
 </div>
 <button type="submit" class="btn-primary py-2 px-4 text-sm h-[42px]">🌪️ Filtrar</button>
 <a href="{{ route('caja.index') }}" class="btn-clean text-sm h-[42px] flex items-center">🧹 Limpiar</a>
 </form>
{{-- Tabla adaptable --}}
<div class="overflow-x-auto pb-2">
<table class="ts-table responsive-table w-full">
<thead>
<tr>
<th class="text-center">Fecha</th>
<th>Persona / Empresa</th>
<th>Concepto</th>
<th class="text-center">Tipo</th>
<th class="text-center">Pago</th>
<th class="text-center">Estado</th>
<th class="text-right">Monto</th>
<th class="text-center">Acciones</th>
</tr>
</thead>
<tbody>
@forelse($movimientos as $m)
@php
$dim = $m->anulado ? 'opacity-60 grayscale' : '';
$dimLight = $m->anulado ? 'opacity-60' : '';
@endphp
<tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
<td data-label="Fecha:" class="text-center font-medium {{ $dim }}">{{ $m->fecha->format('d/m/Y') }}</td>
 <td data-label="Entidad:" class="{{ $dim }}">
 @if($m->persona)
 <a href="{{ route('clientes.index', ['search' => $m->persona]) }}" class="group block hover:opacity-75 transition-opacity" title="Buscar en Clientes">
 <div class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">
 👤 {{ $m->persona }}
 </div>
 <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 Persona / Cliente
 </div>
 </a>
 @elseif($m->empresa)
 <a href="{{ route('proveedores.index', ['search' => $m->empresa]) }}" class="group block hover:opacity-75 transition-opacity" title="Buscar en Proveedores">
 <div class="font-bold text-slate-800 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors leading-tight">
 🏢 {{ $m->empresa }}
 </div>
 <div class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
 Empresa / Proveedor
 </div>
 </a>
 @else
 <span class="text-gray-400 font-bold">—</span>
 @endif
 </td>
 <td data-label="Concepto:" class="font-medium {{ $dim }}">
 {{ $m->concepto->nombre }}
 </td>
 <td data-label="Tipo:" class="text-center {{ $dimLight }}">
 <span class="pill {{ $m->tipo_movimiento === 'ingreso' ? 'pill-done' : 'pill-egreso' }}">
 {{ $m->tipo_movimiento === 'ingreso' ? '📈 Ingreso' : '📉 Egreso' }}
 </span>
 </td>
 <td data-label="Pago:" class="text-center {{ $dimLight }}">
 <span class="pill {{ $m->tipo_pago === 'efectivo' ? 'pill-efectivo' : 'pill-banco' }}">
 {{ $m->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}
 </span>
 </td>
 <td data-label="Estado:" class="text-center">
 <span class="pill {{ $m->anulado ? 'pill-anulado' : 'pill-done' }}">
 {{ $m->anulado ? 'ANULADO' : 'ACTIVO' }}
 </span>
 </td>
 <td data-label="Monto:" class="text-right font-black text-lg {{ $m->tipo_movimiento === 'ingreso' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} {{ $dim }}">
 {{ $m->tipo_movimiento === 'ingreso' ? '+' : '-' }}${{ number_format($m->monto, 0, ',', '.') }}
 @if($m->saldo_pendiente > 0)
 <div class="text-[10px] text-orange-500 font-bold uppercase mt-1">Saldo: ${{ number_format($m->saldo_pendiente, 0, ',', '.') }}</div>
 @endif
 </td>
 <td data-label="Acciones:" class="text-center">
 <div class="flex justify-end md:justify-center gap-2">
 <a href="{{ route('caja.print', $m->id) }}" target="_blank" class="btn-ghost px-3 py-1.5 text-xs" title="Imprimir">🖨️</a>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('caja.edit', $m->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Editar">✏️</a>

                        <button type="button" onclick="openAnularModal('{{ route('caja.anular', $m->id) }}', {{ $m->anulado ? 'true' : 'false' }})" class="btn-ghost px-3 py-1.5 text-xs {{ $m->anulado ? 'grayscale opacity-60 hover:bg-gray-500/10' : 'text-red-600 border-red-500/20 hover:bg-red-500/10' }}" title="{{ $m->anulado ? 'Reactivar movimiento' : 'Anular movimiento' }}">
 {{ $m->anulado ? '✅' : '🚫' }}
 </button>
 @endif </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="8" class="p-16 text-center">
 <div class="flex flex-col items-center justify-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">💸</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Caja vacía</h3>
 <p class="text-gray-500 font-medium max-w-sm">No hay movimientos registrados en este período de tiempo.</p>
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 <div class="mt-6 flex justify-end">
 {{ $movimientos->appends(request()->query())->links() }}
 </div>
 </div>
</div>

<script>
 document.addEventListener('keydown', e => { 
     if (e.key === 'Escape') {
         closeAnularModal();
     }
 });
</script>
@endsection
