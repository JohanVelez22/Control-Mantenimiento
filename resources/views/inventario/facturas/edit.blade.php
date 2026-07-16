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
                <select name="facturable_global" required class="glass-input font-bold">
                    <option value="">Seleccionar...</option>
                    @foreach($proveedores as $p)
                        <option value="Proveedor:{{ $p->id }}" {{ ($factura->facturable_type === 'App\Models\Proveedor' && $factura->facturable_id == $p->id) ? 'selected' : '' }}>
                            🏢 Proveedor: {{ $p->nombre_razon_social }} ({{ $p->identificacion }})
                        </option>
                    @endforeach
                    @foreach($clientes as $c)
                        <option value="Cliente:{{ $c->id }}" {{ ($factura->facturable_type === 'App\Models\Cliente' && $factura->facturable_id == $c->id) ? 'selected' : '' }}>
                            👤 Cliente: {{ $c->nombre }} ({{ $c->identificacion }})
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
                    <table class="ts-table">
                        <thead>
                            <tr>
                                <th class="w-[45%]">Artículo</th>
                                <th class="w-24 text-center">Cantidad</th>
                                <th class="min-w-[160px] text-right">Precio Unitario ($)</th>
                                <th class="min-w-[160px] text-right">Subtotal</th>
                                <th class="w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            @foreach($factura->items as $index => $item)
                                <tr class="existing-row">
                                    <td>
                                        <input type="hidden" name="existing_items[{{ $index }}][id]" value="{{ $item->id }}">
                                        <select name="existing_items[{{ $index }}][stock_id]" required class="stock-select glass-input py-1.5 focus:ring-orange-500" onchange="actualizarPrecio(this)">
                                            <option value="">Seleccionar producto...</option>
                                            @foreach($stocks as $s)
                                                <option value="{{ $s->id }}" data-precio="{{ $factura->tipo_movimiento === 'compra' ? $s->precio_compra : $s->precio_venta }}" {{ $item->stock_id == $s->id ? 'selected' : '' }}>
                                                    {{ $s->producto }} (Stock: {{ $s->cantidad }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="existing_items[{{ $index }}][cantidad]" min="1" value="{{ (int)$item->cantidad }}" required class="glass-input text-center py-1.5 focus:ring-orange-500 quantity-input font-bold" oninput="recalcularTotalesEdicion()">
                                    </td>
                                    <td>
                                        <input type="text" name="existing_items[{{ $index }}][precio_unitario]" value="{{ number_format((float)$item->precio_unitario, 0, ',', '.') }}" required class="glass-input text-right py-1.5 focus:ring-orange-500 font-bold text-slate-800 dark:text-white price-input" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()">
                                    </td>
                                    <td class="text-right subtotal-display py-1.5 align-middle font-bold text-slate-800 dark:text-white">
                                        ${{ number_format($item->cantidad * $item->precio_unitario, 0, ',', '.') }}
                                    </td>
                                    <td></td>
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
                <textarea name="observaciones" rows="3" class="glass-input">{{ old('observaciones', $factura->observaciones) }}</textarea>
                @error('observaciones') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>
        </div>

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
    stocksData.forEach(s => {
        // En compras usar precio_compra, en ventas usar precio_venta o dejar 0
        const defaultPrice = {{ $factura->tipo_movimiento === 'compra' ? 's.precio_compra' : 's.precio_venta' }} || 0;
        optionsHtml += `<option value="${s.id}" data-precio="${defaultPrice}">${s.producto} (Stock: ${s.cantidad})</option>`;
    });

    const tr = document.createElement('tr');
    tr.className = 'new-row bg-blue-50/20 dark:bg-blue-900/10';
    tr.innerHTML = `
        <td>
            <select name="new_items[${filaIndex}][stock_id]" required class="stock-select glass-input py-1.5 focus:ring-blue-500" onchange="actualizarPrecio(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" name="new_items[${filaIndex}][cantidad]" min="1" value="1" required class="glass-input text-center py-1.5 focus:ring-blue-500 quantity-input font-bold" oninput="recalcularTotalesEdicion()">
        </td>
        <td>
            <input type="text" name="new_items[${filaIndex}][precio_unitario]" value="0" required class="glass-input text-right py-1.5 focus:ring-blue-500 font-bold text-slate-800 dark:text-white price-input" oninput="window.formatCurrencyInput(this); recalcularTotalesEdicion()">
        </td>
        <td class="text-right subtotal-display py-1.5 align-middle font-bold text-blue-600 dark:text-blue-400">
            $0
        </td>
        <td class="text-center align-middle">
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
    const option = selectElem.options[selectElem.selectedIndex];
    if(option && option.dataset.precio) {
        const tr = selectElem.closest('tr');
        const priceInput = tr.querySelector('.price-input');
        if(priceInput) {
            priceInput.value = parseFloat(option.dataset.precio).toLocaleString('es-CO');
            recalcularTotalesEdicion();
        }
    }
}

function recalcularTotalesEdicion() {
    let totalDoc = 0;
    
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
        }
    });
    
    document.getElementById('total_documento_display').textContent = '$' + window.formatNumber(totalDoc);
    
    // Update the help text of total pagado
    const helpText = document.getElementById('total_pagado_help');
    if (helpText) {
        helpText.textContent = `El monto total del documento es $${window.formatNumber(totalDoc)}. Modificar el pago ajustará el saldo y el estado automáticamente.`;
    }
}
</script>
@endsection
