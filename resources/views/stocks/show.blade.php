@extends('layouts.app')
@section('title', 'Detalles de Producto')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        
        {{-- Alerta de estado --}}
        @if(!$stock->active)
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-center shadow-[0_4px_20px_rgba(239,68,68,0.15)]">
            <span class="text-2xl drop-shadow-md">🚫</span>
            <h3 class="font-black text-red-600 dark:text-red-400 mt-1 uppercase tracking-widest">Producto Anulado / Inactivo</h3>
            <p class="text-sm font-medium text-red-500/80 mt-1">Este producto no está disponible para nuevas operaciones.</p>
        </div>
        @endif

        {{-- Encabezado unificado: Flecha Volver + Imagen + Título con Auto-Redimensión + Botones de Acción --}}
        <div class="flex items-center justify-between gap-3 sm:gap-4 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6 md:pb-8">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                <a href="{{ route('stocks.index') }}" class="btn-ghost px-3 py-2 text-xl shrink-0" title="Volver">⬅️</a>

                @if($stock->photo)
                    <img src="{{ asset('storage/' . $stock->photo) }}" alt="{{ $stock->producto }}"
                         onclick="openImageLightbox('{{ asset('storage/' . $stock->photo) }}', '{{ addslashes($stock->producto) }}', this)"
                         class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl object-cover cursor-pointer border border-white/40 shadow-sm shrink-0 hover:scale-105 transition-transform"
                         title="Ver foto del producto">
                @else
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-white/20 flex items-center justify-center text-2xl shadow-inner shrink-0">
                        📦
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <h2 id="stock-header-title" class="font-black text-slate-800 dark:text-white tracking-tight truncate leading-tight transition-all text-xl sm:text-2xl" title="{{ $stock->producto }}">
                            {{ $stock->producto }}
                        </h2>
                        <span class="pill {{ $stock->active ? 'pill-done' : 'pill-anulado' }} text-[10px] py-0.5 px-2 font-bold uppercase tracking-wider shrink-0">
                            {{ $stock->active ? 'ACTIVO' : 'INACTIVO' }}
                        </span>
                    </div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1 truncate">
                        <span>Código: <strong class="font-mono text-indigo-500 dark:text-indigo-400 font-bold">{{ $stock->codigo ?? 'N/A' }}</strong></span>
                        <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                        <span>Categoría: <strong class="text-slate-700 dark:text-slate-300 font-semibold">{{ $stock->categoria ?? 'General' }}{{ $stock->subcategoria ? ' / ' . $stock->subcategoria : '' }}</strong></span>
                    </p>
                </div>
            </div>

            {{-- Botones de Acción a la derecha --}}
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('stocks.print', $stock->id) }}" target="_blank" class="btn-ghost border-blue-500/20 text-blue-600 dark:text-blue-400">
                    🖨️ Imprimir
                </a>
                
                @if(!auth()->user()->isInvitado())
                <a href="{{ route('stocks.edit', $stock->id) }}" class="btn-ghost border-yellow-500/20 text-yellow-600 dark:text-yellow-400">
                    ✏️ Editar
                </a>
                @if($stock->cantidad > 0)
                <button type="button" onclick="openBajaStockModal('{{ route('stocks.dar-de-baja', $stock->id) }}', '{{ addslashes($stock->producto) }}', {{ $stock->cantidad }}, {{ $stock->precio_compra }})" class="btn-ghost border-amber-500/30 text-amber-600 dark:text-amber-400 hover:bg-amber-500/10 font-bold">
                    📉 Dar de Baja
                </button>
                @endif
                <button type="button" onclick="openAnularModal('{{ route('stocks.anular', $stock->id) }}', {{ !$stock->active ? 'true' : 'false' }})" class="btn-danger">
                    {{ $stock->active ? '🚫 Anular' : '✅ Reactivar' }}
                </button>
                @endif
            </div>
        </div>

        {{-- Proveedor y Cantidad --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-indigo p-5 flex items-center gap-4 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-xl shadow-lg shrink-0">
                    🏭
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-1">Proveedor Predeterminado</p>
                    <p class="font-medium text-xl text-slate-800 dark:text-slate-200 leading-tight break-words">
                        {{ $proveedor->nombre_razon_social ?? '—' }}
                    </p>
                    <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1">ID: {{ $proveedor->identificacion ?? 'N/A' }}</p>
                </div>
            </div>
            
            <div class="glass-card hover-glow glass-card-blue p-5 flex items-center justify-between min-w-0">
                <div class="min-w-0">
                    <p class="text-[11px] font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-1">Existencia Actual</p>
                    <p class="font-bold text-4xl text-slate-800 dark:text-white leading-tight">
                        {{ $stock->cantidad }} <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Unidades</span>
                    </p>
                </div>
                <div class="text-5xl opacity-80 drop-shadow-md">📦</div>
            </div>
        </div>

        {{-- Estructura de Precios --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Estructura de Precios</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-orange p-4 text-center">
                <p class="text-[10px] font-bold text-orange-500 dark:text-orange-400 uppercase tracking-widest mb-1">Costo de Compra</p>
                <p class="text-xl font-black text-orange-600 dark:text-orange-400">${{ number_format($stock->precio_compra, 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-purple p-4 text-center">
                <p class="text-[10px] font-bold text-purple-500 uppercase tracking-widest mb-1">Precio a Técnico</p>
                <p class="text-xl font-black text-purple-600 dark:text-purple-400">${{ number_format($stock->precio_tecnico, 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-emerald p-4 text-center">
                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Precio Público Venta</p>
                <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">${{ number_format($stock->precio_venta, 0, ',', '.') }}</p>
                @php
                    $utilidadPesos = $stock->precio_venta - $stock->precio_compra;
                    $utilidadPct = $stock->utilidad ?? 0;
                @endphp
                <p class="text-xs font-bold text-emerald-500 mt-1" title="Margen: {{ $utilidadPct }}%">+${{ number_format($utilidadPesos, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Historial de Operaciones --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Historial de Operaciones (Compras y Ventas)</h3>
        <div class="overflow-x-auto overflow-y-auto max-h-[400px] relative mb-6">
            <table class="ts-table mb-0">
                <thead class="sticky top-0 z-20 shadow-sm">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Factura</th>
                        <th>Entidad</th>
                        <th class="text-center w-20">Cant.</th>
                        <th class="text-right">Precio U.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historial as $item)
                    <tr class="{{ $item->factura->estado === 'anulada' ? 'opacity-50 grayscale' : '' }}">
                        <td class="text-sm font-medium">{{ \Carbon\Carbon::parse($item->factura->fecha ?? $item->created_at)->format('d/m/Y') }}</td>
                        <td>
                            <span class="pill {{ $item->factura->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done' }} text-xs">
                                {{ $item->factura->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta' }}
                            </span>
                        </td>
                        <td class="font-bold">
                            <a href="{{ route('inventario.facturas.show', $item->factura_id) }}" class="text-blue-600 hover:underline">
                                {{ $item->factura->numero_factura }}
                            </a>
                        </td>
                        <td class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            {{ $item->factura->facturable->nombre_razon_social ?? $item->factura->facturable->nombre ?? '—' }}
                        </td>
                        <td class="text-center font-bold">{{ $item->cantidad }}</td>
                        <td class="text-right font-bold text-slate-800 dark:text-white">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                        <td class="text-right font-black {{ $item->factura->tipo_movimiento === 'compra' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ $item->factura->tipo_movimiento === 'compra' ? '-' : '+' }}${{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center p-8 text-gray-500 font-medium">
                            No hay compras ni ventas registradas para este producto.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
            <span>Última actualización: {{ $stock->updated_at->format('d/m/Y H:i:s') }}</span>
            <span>Registro inicial: {{ $stock->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>

    {{-- Tarjeta de Historial de Bajas y Mermas --}}
    <div class="glass-card p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-lg">📉</span>
                <h3 class="font-bold text-slate-800 dark:text-white text-base">Historial de Bajas y Mermas</h3>
            </div>
            @if(isset($bajas) && $bajas->count() > 0)
            <div class="flex items-center gap-3 text-xs font-bold">
                <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    Total descartado: {{ $stock->total_unidades_baja }} unidades
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                    Pérdida: ${{ number_format($stock->total_perdida_bajas, 0, ',', '.') }}
                </span>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="glass-table w-full text-left">
                <thead>
                    <tr>
                        <th class="p-3">Fecha</th>
                        <th class="p-3 text-center">Cant.</th>
                        <th class="p-3 text-right">P. Compra</th>
                        <th class="p-3 text-right">Pérdida Total</th>
                        <th class="p-3">Motivo</th>
                        <th class="p-3">Observación</th>
                        <th class="p-3">Autorizado Por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/50 dark:divide-white/5">
                    @forelse($bajas ?? [] as $baja)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition text-sm">
                        <td class="p-3 font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">
                            {{ $baja->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="p-3 text-center font-black text-amber-600 dark:text-amber-400">
                            -{{ $baja->cantidad }}
                        </td>
                        <td class="p-3 text-right text-slate-600 dark:text-slate-400 font-mono text-xs">
                            ${{ number_format($baja->precio_compra_unitario, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right font-black text-red-600 dark:text-red-400 font-mono text-xs">
                            ${{ number_format($baja->costo_total_perdida, 0, ',', '.') }}
                        </td>
                        <td class="p-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                {{ $baja->motivo_label }}
                            </span>
                        </td>
                        <td class="p-3 text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate" title="{{ $baja->observacion }}">
                            {{ $baja->observacion ?: '—' }}
                        </td>
                        <td class="p-3 text-xs font-medium text-slate-700 dark:text-slate-300">
                            {{ $baja->user->name ?? 'Sistema' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center p-6 text-gray-500 font-medium text-xs">
                            No se registran bajas ni mermas para este artículo. Todas las unidades ingresadas están en existencias o fueron vendidas/consumidas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            @if(auth()->user()->isTecnico())
                                Contraseña de Administrador:
                            @else
                                Contraseña de Confirmación:
                            @endif
                        </label>
                        <input type="password" name="password_confirm" required placeholder="••••••••" class="glass-input text-center tracking-widest text-sm w-full">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 w-full modal-divider">
                <button type="button" onclick="closeBajaStockModal()" class="flex-1 btn-ghost border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-500/10 justify-center py-2.5 rounded-xl font-bold text-sm">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 btn-ghost border-blue-500/30 text-blue-600 dark:text-blue-400 hover:bg-blue-500/10 justify-center py-2.5 rounded-xl font-bold text-sm">
                    📉 Confirmar Baja
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    function autoResizeStockTitle() {
        var title = document.getElementById('stock-header-title');
        if (!title) return;
        
        var baseSize = window.innerWidth < 640 ? 1.2 : 1.5;
        title.style.fontSize = baseSize + 'rem';
        
        var current = baseSize;
        while (title.scrollWidth > title.clientWidth && current > 0.85) {
            current -= 0.05;
            title.style.fontSize = current.toFixed(2) + 'rem';
        }
    }

    window.addEventListener('resize', autoResizeStockTitle);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoResizeStockTitle);
    } else {
        autoResizeStockTitle();
    }
})();

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
