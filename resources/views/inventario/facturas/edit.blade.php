@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Cliente / Proveedor --}}
            <div class="md:col-span-2 min-w-0">
                <label class="field-label">Cliente / Proveedor *</label>
                <select name="facturable_global" required class="glass-input no-search font-bold" data-placeholder="Seleccionar...">
                    <option value="">Seleccionar...</option>
                    @foreach($proveedores as $p)
                        <option value="Proveedor:{{ $p->id }}" {{ ($factura->facturable_type === 'App\Models\Proveedor' && $factura->facturable_id == $p->id) ? 'selected' : '' }}>
                            🏢 Proveedor: {{ $p->nombre_razon_social }} ({{ $p->identificacion }})
                        </option>
                    @endforeach
                    @foreach($clientes as $c)
                        <option value="Cliente:{{ $c->id }}" data-tipo="{{ $c->tipo_cliente }}" {{ ($factura->facturable_type === 'App\Models\Cliente' && $factura->facturable_id == $c->id) ? 'selected' : '' }}>
                            👤 Cliente: {{ $c->nombre }} ({{ $c->identificacion }}){{ $c->tipo_cliente === 'tecnico' ? ' — 🔧 Técnico' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('facturable_global') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            {{-- Fecha --}}
            <div>
                <label class="field-label">Fecha de Factura *</label>
                <input type="date" name="fecha" required value="{{ old('fecha', $factura->fecha->format('Y-m-d')) }}" class="glass-input">
                @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>


            {{-- Artículos de la Factura --}}
            <div class="md:col-span-2">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                        <span>📦</span> Artículos de la Factura
                    </h3>
                    <button type="button" onclick="agregarFila()" class="btn-clean">
                        ➕ Agregar artículo
                    </button>
                </div>
                <div class="overflow-x-auto pb-2">
                    <table class="ts-table w-full table-fixed">
                        <thead>
                            <tr>
                                <th class="w-auto px-2 py-3">Artículo</th>
                                <th class="w-24 text-center px-2 py-3">Cantidad</th>
                                <th class="w-40 text-right px-3 py-3">Precio Unitario ($)</th>
                                <th class="w-36 text-right px-3 py-3">Subtotal</th>
                                <th class="w-10 text-center px-2 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            @foreach($factura->items as $index => $item)
                                <tr class="existing-row">
                                    <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
                                        <input type="hidden" name="existing_items[{{ $index }}][id]" value="{{ $item->id }}">
                                        @if($item->stock_id)
                                            <div class="flex flex-col gap-1">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">📦 Producto Stock</span>
                                                <select name="existing_items[{{ $index }}][stock_id]" required class="stock-select glass-input no-search py-1.5 focus:ring-orange-500" data-placeholder="Seleccionar producto...">
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
                                                <input type="text" name="existing_items[{{ $index }}][descripcion]" value="{{ $item->descripcion }}" required class="glass-input py-1.5 focus:ring-orange-500 font-bold" placeholder="Descripción del artículo/servicio">
                                            </div>
                                        @endif
                                    </td>
                                    <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
                                        <input type="number" name="existing_items[{{ $index }}][cantidad]" min="1" value="{{ (int)$item->cantidad }}" required class="glass-input text-center py-1.5 focus:ring-orange-500 quantity-input font-bold" oninput="recalcularTotalesEdicion()">
                                    </td>
                                    <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
                                        <input type="text" name="existing_items[{{ $index }}][precio_unitario]" value="{{ number_format((float)$item->precio_unitario, 0, ',', '.') }}" required class="glass-input text-right py-1.5 focus:ring-orange-500 font-bold text-slate-800 dark:text-white price-input transition-all" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()">
                                        @if($factura->tipo_movimiento === 'venta')
                                             <div class="alerta-costo-badge hidden text-xs font-bold text-red-500 dark:text-red-400 text-right items-center justify-end gap-1.5" style="margin-top: 10px !important; margin-bottom: 2px !important;">
                                                 <span>⚠️ Menor al costo (<span class="costo-ref font-black">$0</span>)</span>
                                             </div>
                                        @endif
                                    </td>
                                    <td class="text-right subtotal-display pr-4 font-bold text-slate-800 dark:text-white" style="vertical-align: top !important; padding-top: 18px; padding-bottom: 10px;">
                                        ${{ number_format($item->cantidad * $item->precio_unitario, 0, ',', '.') }}
                                    </td>
                                    <td style="vertical-align: top !important; padding-top: 14px; padding-bottom: 10px;"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-between items-center p-5 bg-white/10 dark:bg-slate-900/25 border border-white/40 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
                    <span class="font-bold text-xs uppercase tracking-widest text-gray-500 dark:text-gray-400">Nuevo Total Documento:</span>
                    <span class="text-2xl font-black text-blue-600 dark:text-blue-400" id="total_documento_display">${{ number_format($factura->total_documento, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Total Pagado --}}
            <div class="md:col-span-2 p-4 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
                <label class="field-label text-center block text-sm">Total Pagado ($) *</label>
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

        <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
            <a href="{{ route('inventario.facturas') }}" class="btn-cancel">↩️ Cancelar</a>
            <button type="submit" class="btn-save">💾 Guardar Cambios</button>
        </div>
    </form>
    </div>
</div>

<script>
const stocksData = @json($stocks);
let filaIndex = 999;

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
        optionsHtml += `<option value="${s.id}" data-precio="${defaultPrice}">${s.producto} (Stock: ${s.cantidad})</option>`;
    });

    const tr = document.createElement('tr');
    tr.className = 'new-row bg-blue-50/20 dark:bg-blue-900/10';
    tr.innerHTML = `
        <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
            <div class="flex flex-col gap-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">📦 Producto Stock</span>
                <select name="new_items[${filaIndex}][stock_id]" required class="stock-select glass-input no-search py-1.5 focus:ring-blue-500" data-placeholder="Seleccionar producto..." onchange="actualizarPrecio(this)">
                    ${optionsHtml}
                </select>
            </div>
        </td>
        <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
            <input type="number" name="new_items[${filaIndex}][cantidad]" min="1" value="1" required class="glass-input text-center py-1.5 focus:ring-blue-500 quantity-input font-bold" oninput="recalcularTotalesEdicion()">
        </td>
        <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
            <input type="text" name="new_items[${filaIndex}][precio_unitario]" value="0" required class="glass-input text-right py-1.5 focus:ring-blue-500 font-bold text-slate-800 dark:text-white price-input transition-all" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()">
            @if($factura->tipo_movimiento === 'venta')
                <div class="alerta-costo-badge hidden text-xs font-bold text-red-500 dark:text-red-400 text-right items-center justify-end gap-1.5" style="margin-top: 10px !important; margin-bottom: 2px !important;">
                    <span>⚠️ Menor al costo (<span class="costo-ref font-black">$0</span>)</span>
                </div>
            @endif
        </td>
        <td class="text-right subtotal-display pr-4 font-bold text-blue-600 dark:text-blue-400" style="vertical-align: top !important; padding-top: 18px; padding-bottom: 10px;">
            $0
        </td>
        <td class="text-center" style="vertical-align: top !important; padding-top: 14px; padding-bottom: 10px;">
            <button type="button" onclick="eliminarFilaNueva(this)" class="text-red-400 hover:text-red-600 p-2" title="Eliminar fila nueva">✕</button>
        </td>
    `;
    document.getElementById('items-body').appendChild(tr);
    
    // Inicializar TomSelect en el nuevo select
    const newSelect = tr.querySelector('.stock-select');
    if (newSelect && typeof window.initGlassTomSelect === 'function') {
        window.initGlassTomSelect(newSelect);
    }
}

function eliminarFilaNueva(btn) {
    btn.closest('tr').remove();
    recalcularTotalesEdicion();
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
    recalcularTotalesEdicion();

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
