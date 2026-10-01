@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.facturas') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    ✏️ Editar Factura {{ $factura->numero_factura }}
                </h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Modifica los detalles, agrega artículos o cambia el estado</p>
            </div>
        </div>

        <form action="{{ route('inventario.facturas.update', $factura->id) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')

        @php
            $isCompra = $factura->tipo_movimiento === 'compra';
            $nroFacturaText = $isCompra ? 'text-orange-600 dark:text-orange-400' : 'text-emerald-600 dark:text-emerald-400';
            $ringColor = $isCompra ? 'focus:ring-orange-500' : 'focus:ring-emerald-500';
            $totalTextColor = $isCompra ? 'text-orange-600 dark:text-orange-400' : 'text-emerald-600 dark:text-emerald-400';
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Encabezado: Factura, Cliente/Proveedor y Fecha en una sola fila --}}
            <div class="md:col-span-2">
                <div class="flex flex-col md:flex-row gap-5 p-5 glass-card">
                    <div class="col-factura-header">
                        <label class="field-label whitespace-nowrap">N° Factura</label>
                        <input type="text" value="{{ $factura->numero_factura }}" readonly class="glass-input font-mono font-bold bg-white/40 dark:bg-black/20 {{ $nroFacturaText }} text-left pl-3 cursor-not-allowed w-full" style="width: 7rem !important;">
                    </div>
                    <div class="w-full flex-1 min-w-0">
                        <label class="field-label">Cliente / Proveedor *</label>
                        <select name="facturable_global" required class="glass-input font-bold {{ $ringColor }}" data-placeholder="Buscar cliente o proveedor...">
                            <option value="">Buscar cliente o proveedor...</option>
                            @foreach($proveedores as $p)
                                <option value="Proveedor:{{ $p->id }}" {{ ($factura->facturable_type === 'App\Models\Proveedor' && $factura->facturable_id == $p->id) ? 'selected' : '' }}>
                                    🏢 Proveedor: {{ $p->nombre_razon_social }} ({{ $p->identificacion }})
                                </option>
                            @endforeach
                            @foreach($clientes as $c)
                                <option value="Cliente:{{ $c->id }}" data-tipo="{{ $c->tipo_cliente }}" {{ ($factura->facturable_type === 'App\Models\Cliente' && $factura->facturable_id == $c->id) ? 'selected' : '' }}>
                                    👤 Cliente: {{ $c->nombre }} ({{ $c->identificacion }}){{ $c->tipo_cliente === 'tecnico' ? ' 🛠️ Técnico' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('facturable_global') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-fecha-header">
                        <label class="field-label whitespace-nowrap">Fecha Factura *</label>
                        <input type="date" name="fecha" required value="{{ old('fecha', $factura->fecha->format('Y-m-d')) }}" class="glass-input reportes-date-input {{ $ringColor }}" style="width: 8.5rem !important;">
                        @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>


            {{-- Artículos de la Factura --}}
            <div class="md:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                        <span>📦</span> Artículos de la Factura
                    </h3>
                    <button type="button" onclick="agregarFila()" class="btn-add">
                        ➕ Agregar artículo
                    </button>
                </div>
<style>
.table-factura-edit {
    width: 100% !important;
    table-layout: fixed !important;
}
.table-factura-edit th,
.table-factura-edit td {
    vertical-align: top !important;
}
.table-factura-edit tfoot td {
    vertical-align: middle !important;
}
.table-factura-edit th.col-art,
.table-factura-edit td.col-art {
    width: auto !important;
    min-width: 250px !important;
    padding-left: 16px !important;
    padding-right: 8px !important;
    text-align: left !important;
    vertical-align: top !important;
}
.table-factura-edit th.col-cant,
.table-factura-edit td.col-cant {
    width: 100px !important;
    min-width: 100px !important;
    max-width: 100px !important;
    padding-left: 4px !important;
    padding-right: 4px !important;
    text-align: center !important;
    vertical-align: top !important;
}
.table-factura-edit input.quantity-input {
    width: 100% !important;
    padding-left: 10px !important;
    padding-right: 4px !important;
    text-align: center !important;
}
.table-factura-edit th.col-precio,
.table-factura-edit td.col-precio {
    width: 190px !important;
    min-width: 190px !important;
    max-width: 190px !important;
    padding-left: 6px !important;
    padding-right: 6px !important;
    text-align: right !important;
    vertical-align: top !important;
}
.table-factura-edit th.col-subtotal,
.table-factura-edit td.col-subtotal {
    width: 175px !important;
    min-width: 175px !important;
    max-width: 175px !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
    text-align: center !important;
    vertical-align: top !important;
}
.table-factura-edit th.col-accion,
.table-factura-edit td.col-accion {
    width: 58px !important;
    min-width: 58px !important;
    max-width: 58px !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
    text-align: center !important;
    vertical-align: top !important;
}
.table-factura-edit td.col-accion .btn-danger {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 34px !important;
    height: 34px !important;
    min-width: 34px !important;
    max-width: 34px !important;
    padding: 0 !important;
    margin: 0 !important;
}
.table-factura-edit .alerta-costo-badge,
.table-factura-edit .alerta-costo-badge span,
.table-factura-edit .alerta-costo-badge .costo-ref {
    font-size: 10px !important;
    text-transform: uppercase !important;
}
.table-factura-edit .alerta-costo-badge {
    position: absolute !important;
    right: 4px !important;
    top: 100% !important;
    margin-top: 2px !important;
    margin-bottom: 0 !important;
    padding: 0 !important;
    font-size: 10px !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    max-width: 100% !important;
    z-index: 10 !important;
    pointer-events: none !important;
    line-height: normal !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}
/* Evita que descripciones largas rompan altura o se corten verticalmente */
.table-factura-edit .ts-wrapper .ts-control {
    white-space: nowrap !important;
    overflow: hidden !important;
    min-height: 38px !important;
    height: 38px !important;
}
.table-factura-edit .ts-control .ts-item-display,
.table-factura-edit .ts-control>.item {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    word-break: normal !important;
    line-height: normal !important;
    max-width: 100% !important;
    display: inline-block !important;
}
.table-factura-edit select.stock-select {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    min-height: 38px !important;
    height: 38px !important;
}
</style>
                <div class="overflow-x-auto pb-2">
                    <table class="ts-table w-full table-fixed table-factura-edit" id="factura-items-table">
                        <thead>
                            <tr>
                                <th class="col-art py-3 text-left">Artículo</th>
                                <th class="col-cant text-center py-3">Cantidad</th>
                                <th class="col-precio text-right py-3 whitespace-nowrap">Precio Unitario ($)</th>
                                <th class="col-subtotal text-center py-3">Subtotal</th>
                                <th class="col-accion text-center py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                                @foreach($factura->items as $index => $item)
                                <tr class="existing-row">
                                    <td class="col-art py-2.5" style="vertical-align: top !important;">
                                        <input type="hidden" name="existing_items[{{ $index }}][id]" value="{{ $item->id }}">
                                        @if($item->stock_id)
                                            <div class="flex flex-col gap-1">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">📦 Producto / Stock</span>
                                                <select name="existing_items[{{ $index }}][stock_id]" required class="stock-select glass-input py-1.5 {{ $ringColor }}" data-placeholder="Seleccionar producto...">
                                                    <option value="">Seleccionar producto...</option>
                                                    @foreach($stocks as $s)
                                                        <option value="{{ $s->id }}" data-precio="{{ $factura->tipo_movimiento === 'compra' ? $s->precio_compra : $s->precio_venta }}" {{ $item->stock_id == $s->id ? 'selected' : '' }}>
                                                            {{ $s->producto }} (Stock: {{ $s->cantidad }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <input type="hidden" name="existing_items[{{ $index }}][stock_id]" value="">
                                            <div class="flex flex-col gap-1">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-cyan-400">🛠️ Servicio / Ítem Libre</span>
                                                <input type="text" name="existing_items[{{ $index }}][descripcion]" value="{{ $item->descripcion }}" required class="glass-input py-1.5 {{ $ringColor }} font-bold" placeholder="Descripción del artículo/servicio">
                                            </div>
                                        @endif
                                    </td>
                                    <td class="col-cant py-2.5" style="vertical-align: top !important;">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                                            <input type="number" name="existing_items[{{ $index }}][cantidad]" min="1" value="{{ (int)$item->cantidad }}" required class="glass-input text-center py-1.5 {{ $ringColor }} quantity-input font-bold" oninput="recalcularTotalesEdicion()">
                                        </div>
                                    </td>
                                    <td class="col-precio py-2.5" style="vertical-align: top !important;">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                                            <div class="relative">
                                                <input type="text" name="existing_items[{{ $index }}][precio_unitario]" value="{{ number_format((float)$item->precio_unitario, 0, ',', '.') }}" required class="glass-input text-right py-1.5 {{ $ringColor }} font-bold text-slate-800 dark:text-white price-input transition-all" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()">
                                                @if($factura->tipo_movimiento === 'venta')
                                                     <div class="alerta-costo-badge hidden text-[10px] font-bold text-red-500 dark:text-red-400 text-right items-center justify-end gap-1 whitespace-nowrap overflow-hidden text-ellipsis absolute right-1 top-full pointer-events-none uppercase tracking-wider">
                                                         <span>⚠️ MENOR AL COSTO (<span class="costo-ref font-black">$0</span>)</span>
                                                     </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-subtotal py-2.5 text-center" style="vertical-align: top !important;">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                                            <div class="subtotal-display h-[38px] flex items-center justify-center font-bold text-slate-800 dark:text-white text-center whitespace-nowrap overflow-hidden text-ellipsis px-2">
                                                ${{ number_format($item->cantidad * $item->precio_unitario, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-accion py-2.5 text-center" style="vertical-align: top !important;">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                                            <div class="h-[38px] flex items-center justify-center">
                                                <button type="button" onclick="eliminarFila(this)" class="btn-danger btn-icon shadow-sm hover:scale-105 transition-all inline-flex items-center justify-center relative z-10" title="Eliminar ítem">
                                                    🗑️
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-0 bg-gray-50/50 dark:bg-gray-800/50">
                                <td colspan="3" class="py-4 text-right pr-2 align-middle border-0">
                                    <span class="font-bold text-gray-500 uppercase tracking-widest text-xs whitespace-nowrap">Total Documento:</span>
                                </td>
                                <td class="py-4 text-right pr-3 align-middle border-0">
                                    <span class="font-black text-2xl {{ $totalTextColor }} whitespace-nowrap" id="total_documento_display">${{ number_format($factura->total_documento, 0, ',', '.') }}</span>
                                </td>
                                <td class="py-4 align-middle border-0"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Total Pagado --}}
            <div class="md:col-span-2 p-4 glass-card">
                <label class="field-label text-center block text-sm">Total Pagado <span class="text-red-500 font-bold">*</span></label>
                <input type="text" name="total_pagado" id="total_pagado" required value="{{ old('total_pagado', number_format($factura->total_pagado, 0, ',', '.')) }}" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()" class="glass-input font-black text-2xl text-emerald-600 text-center py-3">
                <p class="text-[11px] text-gray-400 mt-2 text-center" id="total_pagado_help">El monto total del documento es ${{ number_format($factura->total_documento, 0, ',', '.') }}. Modificar el pago ajustará el saldo y el estado automáticamente.</p>
                @error('total_pagado') <p class="text-red-500 text-xs mt-1 font-bold text-center">{{ $message }}</p> @enderror
            </div>

            {{-- Observaciones --}}
            <div class="md:col-span-2">
                <label class="field-label">Observaciones</label>
                <textarea name="observaciones" rows="3" class="glass-input" placeholder="Notas sobre la venta o autorización de descuentos...">{{ old('observaciones', $factura->observaciones) }}</textarea>
                @error('observaciones') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>
        </div>

        @if($factura->tipo_movimiento === 'venta')
            {{-- Banner preventivo si se detecta venta bajo costo --}}
            <div id="banner-alerta-bajo-costo" class="hidden mb-5 p-4 md:p-5 rounded-2xl bg-amber-500/5 dark:bg-amber-500/[0.06] border border-amber-500/20 dark:border-amber-500/20 backdrop-blur-xl shadow-sm transition-all flex-col">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-lg bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-base font-bold shadow-inner shrink-0">
                            <span class="inline-flex items-center justify-center leading-none select-none" style="transform: translateY(-1.5px);">⚠️</span>
                        </span>
                        <h4 class="font-bold text-slate-800 dark:text-amber-300 text-sm sm:text-base leading-tight">
                            Advertencia de Margen Negativo
                        </h4>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                        Venta por debajo del costo
                    </span>
                </div>
                <div class="mt-4">
                    <p class="text-xs sm:text-[13px] text-slate-600 dark:text-slate-300 font-medium leading-relaxed">
                        Hay uno o más productos con precio de venta inferior a su costo de adquisición. Puedes procesar la factura si es un descuento autorizado o liquidación, pero generará margen negativo en la rentabilidad.
                    </p>
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
            <a href="{{ route('inventario.facturas') }}" class="btn-cancel w-full sm:w-auto justify-center text-center">✕<space>Cancelar</a>
            <button type="submit" class="btn-save w-full sm:w-auto justify-center">💾 Guardar Cambios</button>
        </div>
    </form>
    </div>
</div>

<script>
const stocksData = @json($stocks);
let filaIndex = 999;

const escapeHtml = window.escapeHtml || function(text) {
    if (text === null || text === undefined) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
};

function agregarFila() {
    filaIndex++;
    let optionsHtml = '<option value="">Seleccionar producto...</option>';
    const selCliente = document.querySelector('select[name="facturable_global"]');
    const esTecnico = selCliente && selCliente.options[selCliente.selectedIndex]?.dataset?.tipo === 'tecnico';
    stocksData.forEach(s => {
        // En compras usar precio_compra, en ventas usar precio_tecnico o precio_venta
        let defaultPrice = 0;
        @if($factura->tipo_movimiento === 'compra')
            defaultPrice = s.precio_compra || 0;
        @else
            defaultPrice = (esTecnico && s.precio_tecnico > 0) ? s.precio_tecnico : (s.precio_venta || 0);
        @endif
        optionsHtml += `<option value="${s.id}" data-precio="${defaultPrice}">${escapeHtml(s.producto)} (Stock: ${s.cantidad})</option>`;
    });

    const tr = document.createElement('tr');
    tr.className = 'new-row bg-transparent';
    tr.innerHTML = `
        <td class="col-art py-2.5" style="vertical-align: top !important;">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">📦 Producto / Stock</span>
                <select name="new_items[${filaIndex}][stock_id]" required class="stock-select glass-input py-1.5 {{ $ringColor }}" data-placeholder="Seleccionar producto..." onchange="actualizarPrecio(this)">
                    ${optionsHtml}
                </select>
            </div>
        </td>
        <td class="col-cant py-2.5" style="vertical-align: top !important;">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                <input type="number" name="new_items[${filaIndex}][cantidad]" min="1" value="1" required class="glass-input text-center py-1.5 {{ $ringColor }} quantity-input font-bold" oninput="recalcularTotalesEdicion()">
            </div>
        </td>
        <td class="col-precio py-2.5" style="vertical-align: top !important;">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                <div class="relative">
                    <input type="text" name="new_items[${filaIndex}][precio_unitario]" value="0" required class="glass-input text-right py-1.5 {{ $ringColor }} font-bold text-slate-800 dark:text-white price-input transition-all" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()">
                    @if($factura->tipo_movimiento === 'venta')
                        <div class="alerta-costo-badge hidden text-[10px] font-bold text-red-500 dark:text-red-400 text-right items-center justify-end gap-1 whitespace-nowrap overflow-hidden text-ellipsis absolute right-1 top-full pointer-events-none uppercase tracking-wider">
                            <span>⚠️ MENOR AL COSTO (<span class="costo-ref font-black">$0</span>)</span>
                        </div>
                    @endif
                </div>
            </div>
        </td>
        <td class="col-subtotal py-2.5 text-center" style="vertical-align: top !important;">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                <div class="subtotal-display h-[38px] flex items-center justify-center font-bold {{ $totalTextColor }} text-center whitespace-nowrap overflow-hidden text-ellipsis px-2">
                    $0
                </div>
            </div>
        </td>
        <td class="col-accion py-2.5 text-center" style="vertical-align: top !important;">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-wider opacity-0 select-none pointer-events-none" aria-hidden="true">&nbsp;</span>
                <div class="h-[38px] flex items-center justify-center">
                    <button type="button" onclick="eliminarFila(this)" class="btn-danger btn-icon shadow-sm hover:scale-105 transition-all inline-flex items-center justify-center relative z-10" title="Eliminar ítem">
                        🗑️
                    </button>
                </div>
            </div>
        </td>
    `;
    document.getElementById('items-body').appendChild(tr);
    
    // Inicializar TomSelect en el nuevo select
    const newSelect = tr.querySelector('.stock-select');
    if (newSelect && typeof window.initGlassTomSelect === 'function') {
        window.initGlassTomSelect(newSelect);
        if (newSelect.tomselect) {
            newSelect.tomselect.on('change', function() {
                const text = newSelect.options[newSelect.selectedIndex]?.text?.trim() || '';
                if (newSelect.tomselect.control) newSelect.tomselect.control.setAttribute('title', text);
            });
        }
    }
}

let _pendingRowToDelete = null;

function eliminarFila(btn) {
    const filas = document.querySelectorAll('#items-body tr');
    if (filas.length <= 1) {
        if (typeof window.showToast === 'function') {
            window.showToast('Debe haber al menos un artículo en la factura.', 'error');
        } else {
            alert('Debe haber al menos un artículo en la factura.');
        }
        return;
    }

    const tr = btn.closest('tr');
    if (!tr) return;

    // Obtener descripción o producto para mensaje
    let itemNombre = '';
    const stockSelect = tr.querySelector('.stock-select');
    const descInput = tr.querySelector('input[type="text"][name*="descripcion"]');
    if (stockSelect && stockSelect.value) {
        if (stockSelect.tomselect && typeof stockSelect.tomselect.getItem === 'function') {
            const el = stockSelect.tomselect.getItem(stockSelect.value);
            if (el) itemNombre = el.textContent.trim().split('\n')[0].trim();
        }
        if (!itemNombre && stockSelect.selectedOptions && stockSelect.selectedOptions.length) {
            itemNombre = stockSelect.selectedOptions[0].text.trim().split('(')[0].trim();
        }
    } else if (descInput && descInput.value.trim()) {
        itemNombre = descInput.value.trim();
    }

    const modal = document.getElementById('ts-modal');
    if (modal) {
        const titleEl = document.getElementById('ts-modal-title');
        const msgEl = document.getElementById('ts-modal-msg');
        if (titleEl) titleEl.innerText = '¿Estás seguro?';
        if (msgEl) {
            msgEl.innerText = itemNombre
                ? `¿Eliminar el ítem "${itemNombre}" de la factura?`
                : '¿Eliminar este ítem de la factura?';
        }

        _pendingRowToDelete = tr;

        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            const card = document.getElementById('ts-modal-card');
            if (card) card.classList.remove('scale-95', 'opacity-0');
        }, 10);
    } else {
        const msg = itemNombre 
            ? `¿Eliminar el ítem "${itemNombre}" de la factura?` 
            : '¿Eliminar este ítem de la factura?';
        if (confirm(msg)) {
            removerFila(tr);
        }
    }
}

function removerFila(tr) {
    if (!tr) return;
    const stockSel = tr.querySelector('.stock-select');
    if (stockSel && stockSel.tomselect) {
        try { stockSel.tomselect.destroy(); } catch(e) {}
    }
    tr.remove();
    recalcularTotalesEdicion();
    if (typeof window.showToast === 'function') {
        window.showToast('Ítem eliminado de la factura.', 'info');
    }
}

function eliminarFilaNueva(btn) {
    eliminarFila(btn);
}

function actualizarPrecio(selectElem) {
    // Solo se invoca en filas NUEVAS (agregarFila), nunca en ítems existentes
    const option = selectElem.options[selectElem.selectedIndex];
    if(option && option.dataset.precio) {
        const tr = selectElem.closest('tr');
        const priceInput = tr.querySelector('.price-input');
        if(priceInput) {
            priceInput.value = window.formatNumber(parseFloat(option.dataset.precio));
            recalcularTotalesEdicion();
        }
    }
}

function verificarAlertaCostoEdicion(row) {
    @if($factura->tipo_movimiento !== 'venta')
        return false;
    @endif
    const sel = row.querySelector('.stock-select');
    if (!sel || !sel.value) return false;
    const stock = stocksData.find(s => s.id == sel.value);
    if (!stock) return false;

    const priceInput = row.querySelector('.price-input');
    const badge = row.querySelector('.alerta-costo-badge');
    const costoRef = row.querySelector('.costo-ref');

    let rawVal = priceInput ? priceInput.value.replace(/\./g, '').replace(/,/g, '').trim() : '0';
    const precioVenta = parseFloat(rawVal) || 0;
    const precioCompra = parseFloat(stock.precio_compra || '0') || 0;

    if (precioCompra > 0 && precioVenta > 0 && precioVenta < precioCompra) {
        if (badge) {
            if (costoRef) costoRef.textContent = '$' + window.formatNumber(precioCompra);
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        }
        return true;
    } else {
        if (badge) {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
        return false;
    }
}

function recalcularTotalesEdicion() {
    let totalDoc = 0;
    let hayBajoCosto = false;
    
    // Sum all rows (existing and new)
    document.querySelectorAll('tbody tr').forEach(row => {
        const qtyInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        if (qtyInput && priceInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value.replace(/\./g, '')) || 0;
            const sub = qty * price;
            const subtotalCell = row.querySelector('.subtotal-display');
            if (subtotalCell) {
                subtotalCell.textContent = '$' + window.formatNumber(sub);
            }
            totalDoc += sub;

            if (verificarAlertaCostoEdicion(row)) {
                hayBajoCosto = true;
            }
        }
    });
    
    document.getElementById('total_documento_display').textContent = '$' + window.formatNumber(totalDoc);

    const banner = document.getElementById('banner-alerta-bajo-costo');
    if (banner) {
        if (hayBajoCosto) {
            banner.classList.remove('hidden');
            banner.classList.add('flex');
        } else {
            banner.classList.add('hidden');
            banner.classList.remove('flex');
        }
    }
    
    const pagadoInput = document.getElementById('total_pagado');
    if (pagadoInput) {
        let currentPagado = parseFloat(pagadoInput.value.replace(/\./g, '')) || 0;
        if (currentPagado > totalDoc) {
            pagadoInput.value = window.formatNumber(totalDoc);
        }
    }

    // Actualizar el texto de ayuda del total pagado
    const helpText = document.getElementById('total_pagado_help');
    if (helpText) {
        helpText.textContent = `El monto total del documento es $${window.formatNumber(totalDoc)}. Modificar el pago ajustará el saldo y el estado automáticamente.`;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Hooks para el modal global de confirmación de eliminación
    document.getElementById('ts-modal-confirm')?.addEventListener('click', () => {
        if (_pendingRowToDelete) {
            const tr = _pendingRowToDelete;
            _pendingRowToDelete = null;
            removerFila(tr);
            if (typeof window.closeTsModal === 'function') {
                window.closeTsModal();
            }
        }
    });

    if (typeof window.closeTsModal === 'function') {
        const origCloseTsModal = window.closeTsModal;
        window.closeTsModal = function() {
            _pendingRowToDelete = null;
            origCloseTsModal();
        };
    }

    recalcularTotalesEdicion();

    setTimeout(() => {
        document.querySelectorAll('#factura-items-table .stock-select').forEach(sel => {
            const updateTitle = () => {
                const text = sel.options[sel.selectedIndex]?.text?.trim() || '';
                if (sel.tomselect && sel.tomselect.control) {
                    sel.tomselect.control.setAttribute('title', text);
                } else {
                    sel.setAttribute('title', text);
                }
            };
            updateTitle();
            if (sel.tomselect) {
                sel.tomselect.on('change', updateTitle);
            } else {
                sel.addEventListener('change', updateTitle);
            }
        });
    }, 150);

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            this.querySelectorAll('.price-input, #total_pagado').forEach(input => {
                if (input && input.value) {
                    input.value = input.value.replace(/\./g, '');
                }
            });
        });
    }
});
</script>
@endsection
