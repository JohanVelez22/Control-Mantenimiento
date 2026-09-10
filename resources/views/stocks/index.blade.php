@extends('layouts.app')
@section('content')
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
  <th class="text-center w-28">Acciones</th>
  </tr>
  </thead>
 <tbody>
 @forelse($stocks as $stock)
 @php $dim = !$stock->active ? 'opacity-60 grayscale' : ''; @endphp
 <tr id="stock-{{ $stock->id }}" class="scroll-mt-[6.5rem]">
<td data-label="Código:" class="text-sm font-bold text-slate-500 dark:text-slate-400 {{ $dim }}">
  {{ $stock->codigo ?? '-' }}
  </td>
  <td data-label="Foto:" class="text-center {{ $dim }}">
  @if($stock->photo)
  <img src="{{ asset('storage/' . $stock->photo) }}" alt="{{ $stock->producto }}"
       onclick="openImageLightbox('{{ asset('storage/' . $stock->photo) }}', '{{ addslashes($stock->producto) }}', this)"
       onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');"
       class="w-11 h-11 rounded-lg object-cover cursor-pointer border border-white/40 shadow-sm mx-auto hover:opacity-80 transition">
  <span class="hidden text-[10px] text-gray-400 dark:text-gray-500">—</span>
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
 <td data-label="Acciones:" class="text-center w-36 {{ $dim }}">
   <div class="actions-grid">
   <a href="{{ route('stocks.show', $stock->id) }}" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-blue-600" title="Ver Detalles">👁️</a>
   <a href="{{ route('stocks.print', $stock->id) }}" target="_blank" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-gray-600" title="Imprimir">🖨️</a>
   @if(!auth()->user()->isInvitado())
   <a href="{{ route('stocks.edit', $stock->id) }}" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-yellow-600" title="Editar">✏️</a>
   @if($stock->cantidad > 0)
   <button type="button" onclick="openBajaStockModal('{{ route('stocks.dar-de-baja', $stock->id) }}', '{{ addslashes($stock->producto) }}', {{ $stock->cantidad }}, {{ $stock->precio_compra }})" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs text-amber-500 hover:text-amber-600" title="Dar de baja unidades por daño o merma">
   📉
   </button>
   @endif
   <button type="button" onclick="openAnularModal('{{ route('stocks.anular', $stock->id) }}', {{ !$stock->active ? 'true' : 'false' }})" class="btn-ghost w-8 h-8 flex items-center justify-center p-0 text-xs {{ $stock->active ? 'text-red-600' : 'text-emerald-600' }}" title="{{ $stock->active ? 'Anular Producto' : 'Reactivar Producto' }}">
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

<!-- MODAL DAR DE BAJA UNIDADES (Simétrico al Modal de Notificaciones) -->
<div id="baja-stock-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="baja-stock-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4" style="max-width: 550px;">
        
        {{-- Header simétrico a notificaciones --}}
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl shrink-0 select-none">📉</span>
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">Dar de Baja Unidades</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Merma de inventario por daño o defecto</p>
            </div>
        </div>

        <form id="baja-stock-form" method="POST" action="">
            @csrf
            {{-- Contenedor Central al mismo ancho que el modal de notificaciones --}}
            <div class="w-full max-w-[450px] mx-auto flex flex-col flex-1 pb-2">
                
                {{-- Bloque resumen del artículo con barra de acento --}}
                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 relative overflow-hidden mb-4">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500 rounded-l-xl"></div>
                    <div class="pl-2.5 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">Artículo de Inventario</span>
                            <span id="baja-stock-disponible" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-lg"></span>
                        </div>
                        <p id="baja-stock-producto" class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate"></p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cantidad a dar de baja:</label>
                        <input type="number" name="cantidad" id="baja-stock-cantidad" min="1" value="1" required class="glass-input text-center font-black text-base w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Motivo de la baja:</label>
                        <select name="motivo" id="baja-stock-motivo" required class="glass-input no-search text-sm w-full" data-placeholder="Seleccione el motivo de la baja...">
                            <option value="">Seleccione el motivo de la baja...</option>
                            <option value="defectuoso_fabrica" selected>🏭 Defectuoso de fábrica (Garantía proveedor)</option>
                            <option value="dano_taller">⚠️ Daño accidental en taller</option>
                            <option value="obsoleto">⏳ Obsoleto / Deterioro</option>
                            <option value="perdida_merma">📉 Pérdida / Merma de inventario</option>
                            <option value="otro">📝 Otro motivo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Observación o justificación:</label>
                        <textarea name="observacion" id="baja-stock-observacion" rows="2" placeholder="Detalla la falla o circunstancia..." class="glass-input text-xs w-full"></textarea>
                    </div>

                    @if(auth()->user()->isTecnico())
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Contraseña de Administrador:
                        </label>
                        <input type="password" name="password_confirm" required placeholder="••••••••" class="glass-input text-center tracking-widest text-sm w-full">
                    </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 w-full modal-divider">
                <button type="button" onclick="closeBajaStockModal()" class="flex-1 btn-ghost border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-500/10 justify-center py-2.5 rounded-xl font-bold text-sm">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 btn-ghost border-violet-500/30 text-violet-600 dark:text-violet-400 hover:bg-violet-500/10 justify-center py-2.5 rounded-xl font-bold text-sm">
                    📉 Confirmar Baja
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => filterTable('search-stocks', 'tabla-stocks'));

function openBajaStockModal(actionUrl, producto, maxCantidad, precioCompra) {
    const modal = document.getElementById('baja-stock-modal');
    const card  = document.getElementById('baja-stock-card');
    const form  = document.getElementById('baja-stock-form');
    const prodEl = document.getElementById('baja-stock-producto');
    const dispEl = document.getElementById('baja-stock-disponible');
    const cantInput = document.getElementById('baja-stock-cantidad');
    const motivoSel = document.getElementById('baja-stock-motivo');

    form.action = actionUrl;
    prodEl.textContent = producto;
    dispEl.textContent = maxCantidad + ' unidades';
    cantInput.max = maxCantidad;
    cantInput.value = 1;

    if (motivoSel && motivoSel.tomselect) {
        motivoSel.tomselect.setValue('defectuoso_fabrica');
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95', 'opacity-0');
        cantInput.focus();
    }, 10);
}

function closeBajaStockModal() {
    const modal = document.getElementById('baja-stock-modal');
    const card  = document.getElementById('baja-stock-card');
    const motivoSel = document.getElementById('baja-stock-motivo');
    if (motivoSel && motivoSel.tomselect && motivoSel.tomselect.isOpen) {
        motivoSel.tomselect.close();
    }
    modal.classList.add('opacity-0');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
@endsection
