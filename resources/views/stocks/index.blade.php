@extends('layouts.app')
@section('content')
<style>
  tr.active-target td {
    background-color: rgba(59, 130, 246, 0.08) !important;
    border-top: 1.5px solid rgba(59, 130, 246, 0.3) !important;
    border-bottom: 1.5px solid rgba(59, 130, 246, 0.3) !important;
  }
  tr.active-target td:first-child {
    border-left: 4px solid #3b82f6 !important;
  }
  .dark tr.active-target td {
    background-color: rgba(59, 130, 246, 0.15) !important;
    border-top: 1.5px solid rgba(96, 165, 250, 0.4) !important;
    border-bottom: 1.5px solid rgba(96, 165, 250, 0.4) !important;
  }
  .dark tr.active-target td:first-child {
    border-left: 4px solid #60a5fa !important;
  }
</style>
<div class="glass-card p-6">
 <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
 <span class="text-3xl">📦</span> Inventario (Stock)
 </h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Control de repuestos y productos</p>
 </div>
 <div class="flex flex-wrap items-center gap-3">
  <div class="relative">
  <span class="absolute z-10 left-3 top-1/2 transform -translate-y-1/2 text-sm select-none pointer-events-none">🔍</span>
  <input type="text" id="search-stocks" placeholder="Buscar producto, cod..." class="glass-input pl-9 w-48 sm:w-64">
  </div>
 @if(!auth()->user()->isInvitado())
 <div class="flex gap-3 ml-2">
 <a href="{{ route('stocks.categorias.index') }}" class="btn-concepts flex items-center gap-2" style="padding: 9px 18px; font-size: 13px;">
 🏷️ <span class="hidden sm:inline">Gestionar Categorías</span>
 </a>
 <a href="{{ route('stocks.create') }}" class="btn-primary flex items-center gap-2 shadow-lg shadow-indigo-500/30" style="padding: 9px 18px; font-size: 13px;">
 <span>➕</span> <span class="hidden sm:inline">Nuevo Producto</span>
 </a>
 </div>
 @endif
 </div>
 </div>

 <div class="overflow-x-auto pb-2">
 <table id="tabla-stocks" class="ts-table responsive-table w-full">
<thead>
  <tr>
  <th>Cód.</th>
  <th>Foto</th>
  <th>Producto</th>
  <th class="text-center">Cant.</th>
  <th class="text-right">P. Compra</th>
  <th class="text-center">Utilidad</th>
  <th class="text-right">P. Venta</th>
  <th class="text-right">P. Técnico</th>
  <th class="text-center">Estado</th>
  <th class="text-center">Acciones</th>
  </tr>
  </thead>
 <tbody>
 @forelse($stocks as $stock)
 @php $dim = !$stock->active ? 'opacity-60 grayscale' : ''; @endphp
 <tr id="stock-{{ $stock->id }}">
<td data-label="Código:" class="text-sm font-bold text-slate-500 dark:text-slate-400 {{ $dim }}">
  {{ $stock->codigo ?? '-' }}
  </td>
  <td data-label="Foto:" class="text-center {{ $dim }}">
  @if($stock->photo)
  <img src="{{ asset('storage/' . $stock->photo) }}" alt="{{ $stock->producto }}"
       onclick="openImageLightbox('{{ asset('storage/' . $stock->photo) }}', '{{ addslashes($stock->producto) }}', this)"
       class="w-11 h-11 rounded-lg object-cover cursor-pointer border border-white/40 shadow-sm mx-auto hover:opacity-80 transition">
  @else
  <span class="text-[10px] text-gray-400 dark:text-gray-500">—</span>
  @endif
  </td>
  <td data-label="Producto:" class="{{ $dim }}">
  <div class="font-bold text-slate-800 dark:text-white leading-tight">
  {{ $stock->producto }}
  </div>
  @if($stock->categoria || $stock->subcategoria)
  <div class="text-[10px] font-semibold text-gray-500 tracking-wider uppercase mt-1">
  {{ $stock->categoria ?? 'Sin Categoría' }} {{ $stock->subcategoria ? ' / ' . $stock->subcategoria : '' }}
  </div>
  @endif
  </td>
 <td data-label="Cantidad:" class="text-center {{ $dim }}">
 <span class="pill {{ $stock->cantidad > 5 ? 'pill-done' : 'pill-anulado' }}">
 {{ $stock->cantidad }}
 </span>
 </td>
 <td data-label="P. Compra:" class="text-right font-medium {{ $dim }}">
 ${{ number_format($stock->precio_compra, 0, ',', '.') }}
 </td>
 @php
 $utilidadPesos = $stock->precio_venta - $stock->precio_compra;
 $utilidadPct = $stock->utilidad ?? 0;
 @endphp
 <td data-label="Utilidad:" class="text-center {{ $dim }}">
 <div class="flex flex-col items-center gap-0.5 justify-end md:justify-center w-full" title="Margen sobre precio de compra">
 <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
 💹 +{{ number_format($utilidadPct, 0) }}%
 </span>
 <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
 +${{ number_format($utilidadPesos, 0, ',', '.') }}
 </span>
 </div>
 </td>
 <td data-label="P. Venta:" class="text-right font-black text-blue-600 dark:text-cyan-400 text-base {{ $dim }}">
 ${{ number_format($stock->precio_venta, 0, ',', '.') }}
 </td>
 <td data-label="P. Técnico:" class="text-right font-bold text-purple-600 dark:text-purple-400 {{ $dim }}">
 ${{ number_format($stock->precio_tecnico, 0, ',', '.') }}
 </td>
 <td data-label="Estado:" class="text-center">
 <span class="pill {{ $stock->active ? 'pill-done' : 'pill-anulado' }}">
 {{ $stock->active ? 'Activo' : 'Inactivo' }}
 </span>
 </td>
<td data-label="Acciones:" class="text-center {{ $dim }}">
  <div class="actions-grid">
  <a href="{{ route('stocks.show', $stock->id) }}" class="btn-ghost px-3 py-1.5 text-xs text-blue-600" title="Ver Detalles">👁️</a>
  <a href="{{ route('stocks.print', $stock->id) }}" target="_blank" class="btn-ghost px-3 py-1.5 text-xs text-gray-600" title="Imprimir">🖨️</a>
  @if(!auth()->user()->isInvitado())
  <a href="{{ route('stocks.edit', $stock->id) }}" class="btn-ghost px-3 py-1.5 text-xs text-yellow-600" title="Editar">✏️</a>
                            <button type="button" onclick="openAnularModal('{{ route('stocks.anular', $stock->id) }}', {{ !$stock->active ? 'true' : 'false' }})" class="btn-ghost px-3 py-1.5 text-xs {{ $stock->active ? 'text-red-600' : 'text-emerald-600' }}" title="{{ $stock->active ? 'Anular Producto' : 'Reactivar Producto' }}">
  {{ $stock->active ? '🚫' : '✅' }}
  </button>
  @else
  <span class="text-gray-400 text-sm">👁️ Lectura</span>
  @endif
  </div>
  </td>
 </tr>
@empty
  <tr>
  <td colspan="10" class="p-16 text-center">
 <div class="flex flex-col items-center gap-3">
 <div class="text-6xl drop-shadow-md mb-2">📦</div>
 <h3 class="text-xl font-black text-slate-800 dark:text-white">Inventario Vacío</h3>
 <p class="text-gray-500 font-medium max-w-sm mb-4">Registra tu primer repuesto o producto en el stock.</p>
 @if(!auth()->user()->isInvitado())
 <a href="{{ route('stocks.create') }}" class="btn-primary">➕ Agregar Producto</a>
 @endif
 </div>
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 <div class="mt-6 flex justify-end">
 {{ $stocks->appends(request()->query())->links() }}
 </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => filterTable('search-stocks', 'tabla-stocks'));</script>
@endsection
