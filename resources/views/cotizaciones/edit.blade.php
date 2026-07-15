@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('cotizaciones.show', $cotizacion) }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Cotización {{ $cotizacion->codigo }}</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Modifica los ítems o detalles de la cotización antes de facturar.</p>
            </div>
        </div>

        <form action="{{ route('cotizaciones.update', $cotizacion) }}" method="POST" id="cotizacion-form" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="flex flex-col md:flex-row gap-5 p-5 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl">
                <div class="w-full flex-1 min-w-0">
                    <label class="field-label">Cliente *</label>
                    <select name="cliente_id" required class="glass-input focus:ring-blue-500" data-tomselect>
                        <option value="">Buscar cliente...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ old('cliente_id', $cotizacion->cliente_id) == $c->id ? 'selected' : '' }}>
                                {{ $c->nombre }} ({{ $c->identificacion }})
                            </option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="w-full md:w-48 flex-shrink-0">
                    <label class="field-label">Fecha *</label>
                    <input type="date" name="fecha" required value="{{ old('fecha', \Carbon\Carbon::parse($cotizacion->fecha)->format('Y-m-d')) }}" class="glass-input focus:ring-blue-500">
                    @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="w-full md:w-48 flex-shrink-0">
                    <label class="field-label">Validez (Días) *</label>
                    <input type="number" name="validez_dias" required min="1" value="{{ old('validez_dias', $cotizacion->validez_dias) }}" class="glass-input focus:ring-blue-500 text-center">
                    @error('validez_dias') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Tabla de ítems --}}
            <div>
                <div class="flex justify-between items-center mb-5">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                        <span>🛍️</span> Ítems a Cotizar
                    </h3>
                    <button type="button" onclick="agregarFila()" class="btn-clean">
                        ➕ Agregar línea
                    </button>
                </div>

                <div class="overflow-x-auto pb-2 max-h-[500px] overflow-y-auto">
                    <table class="ts-table" id="items-table">
                        <thead>
                            <tr>
                                <th class="w-32">Tipo</th>
                                <th class="w-[45%]">Descripción / Producto</th>
                                <th class="w-24 text-center">Cant.</th>
                                <th class="min-w-[150px] text-right">Precio Un. ($)</th>
                                <th class="min-w-[150px] text-right">Subtotal</th>
                                <th class="w-12 text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <!-- La primera fila se inserta por JS -->
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50">
                                <td colspan="4" class="text-right font-bold text-gray-500 uppercase tracking-widest text-xs pt-4 pb-4">Total Cotización:</td>
                                <td class="text-right font-black text-2xl text-blue-600 dark:text-blue-400 pt-4 pb-4 pr-4" id="total-display">$0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div>
                <label class="field-label">Notas para el cliente</label>
                <textarea name="notas" rows="3" class="glass-input resize-y focus:ring-blue-500" placeholder="Ej: Precios sujetos a cambio sin previo aviso. Tiempo estimado de entrega: 3 días hábiles.">{{ old('notas', $cotizacion->notas) }}</textarea>
            </div>

            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('cotizaciones.index') }}" class="btn-cancel">↩️ Cancelar</a>
                <button type="submit" class="btn-primary px-8">
                    💾 Guardar Cotización
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TomSelect CDN -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<style>
    /* Adaptar tom-select al glassmorphism */
    .ts-control {
        background-color: rgba(255, 255, 255, 0.4) !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        border-radius: 0.5rem !important;
        padding: 0.6rem !important;
        color: #334155 !important;
    }
    .dark .ts-control {
        background-color: rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #e2e8f0 !important;
    }
</style>

@php
    $stocksJson = $stocks->map(fn($s) => [
        'id' => $s->id,
        'nombre' => $s->producto,
        'precio' => $s->precio_venta,
        'cantidad' => $s->cantidad,
    ])->values()->all();
@endphp
<script>
let filaIndex = 0;
const stocksData = @json($stocksJson);

function getStockOptions() {
    return `<option value="">Seleccionar producto del stock...</option>` + 
           stocksData.map(s => `<option value="${s.id}" data-precio="${s.precio}" data-nombre="${s.nombre}">${s.nombre} (Disp: ${s.cantidad}) — $${window.formatNumber(s.precio)}</option>`).join('');
}

function agregarFila(itemData = null) {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.className = 'item-row bg-transparent border-t border-gray-200 dark:border-gray-700/50';
    
    let isStock = itemData ? itemData.tipo === 'stock' : false;
    let cant = itemData ? itemData.cantidad : 1;
    let desc = itemData ? (itemData.descripcion || '') : '';
    let precio = itemData ? itemData.precio_unitario : 0;
    
    tr.innerHTML = `
        <td class="align-middle">
            <select name="items[${filaIndex}][tipo]" class="tipo-select glass-input py-1.5 text-sm" data-tomselect>
                <option value="libre" ${!isStock ? 'selected' : ''}>Servicio / Libre</option>
                <option value="stock" ${isStock ? 'selected' : ''}>Producto Stock</option>
            </select>
        </td>
        <td class="desc-cell align-middle">
            <!-- Renderizado dinámico -->
        </td>
        <td class="align-middle">
            <input type="number" name="items[${filaIndex}][cantidad]" min="1" value="${cant}" required class="cantidad-input glass-input py-1.5 text-center focus:ring-blue-500">
        </td>
        <td class="align-middle">
            <input type="text" name="items[${filaIndex}][precio_unitario]" id="precio_unitario_real_${filaIndex}" value="${precio}" required class="hidden">
            <input type="text" id="precio_unitario_visual_${filaIndex}" value="${window.formatNumber(precio)}" placeholder="0" oninput="window.formatCurrencyDual(this, 'precio_unitario_real_${filaIndex}'); recalcular()" required class="precio-input glass-input py-1.5 text-right focus:ring-blue-500 font-mono">
        </td>
        <td class="text-right font-black text-blue-600 dark:text-blue-400 text-base subtotal-cell align-middle pr-4">$${window.formatNumber(cant * precio)}</td>
        <td class="text-center align-middle">
            <button type="button" onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600 p-2">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
    bindInputs(tr);
    
    const newTipoSel = tr.querySelector('.tipo-select');
    
    // Inyectar el campo de descripción según el tipo (stock o libre)
    window.cambiarTipo(newTipoSel, tr, newTipoSel.value, itemData);

    if (newTipoSel) {
        const ts = window.initGlassTomSelect(newTipoSel);
        ts.on('change', function(value) {
            window.cambiarTipo(newTipoSel, tr, value);
        });
    }
    filaIndex++;
    recalcular();
}

window.cambiarTipo = function(select, tr, val, itemData = null) {
    const tdDesc = tr.querySelector('.desc-cell');
    const idx = select.name.match(/\[(\d+)\]/)[1];
    
    // Guardar referencia al control TS si existe para destruirlo limpiamente
    if (tr.tomselectObj) {
        tr.tomselectObj.destroy();
        tr.tomselectObj = null;
    }

    if (val === 'stock') {
        tdDesc.innerHTML = `
            <select class="stock-select glass-input py-1.5" required>
                ${getStockOptions()}
            </select>
            <input type="hidden" name="items[${idx}][item_id]" class="stock-id-input">
            <input type="hidden" name="items[${idx}][descripcion]" class="stock-desc-input">
        `;
        const newSel = tdDesc.querySelector('.stock-select');
        
        // Si hay data existente, pre-seleccionar el stock
        if (itemData && itemData.item_id) {
            newSel.value = itemData.item_id;
            tr.querySelector('.stock-id-input').value = itemData.item_id;
            tr.querySelector('.stock-desc-input').value = itemData.descripcion;
        }

        if (typeof window.initGlassTomSelect === 'function') {
            tr.tomselectObj = window.initGlassTomSelect(newSel);
        }
        
        newSel.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (!opt.value) return;
            const pReal = tr.querySelector('[id^="precio_unitario_real_"]');
            const pVis = tr.querySelector('[id^="precio_unitario_visual_"]');
            tr.querySelector('.stock-id-input').value = opt.value;
            tr.querySelector('.stock-desc-input').value = opt.dataset.nombre;
            
            pReal.value = opt.dataset.precio;
            pVis.value = window.formatNumber(opt.dataset.precio);
            actualizarSubtotal(tr);
        });
    } else {
        let defaultDesc = itemData ? (itemData.descripcion || '') : '';
        tdDesc.innerHTML = `<input type="text" name="items[${idx}][descripcion]" value="${defaultDesc}" class="desc-input glass-input py-1.5 focus:ring-blue-500" placeholder="Descripción de mano de obra o servicio..." required>`;
    }
};

function eliminarFila(btn) {
    if (document.querySelectorAll('.item-row').length === 1) return;
    const tr = btn.closest('tr');
    if (tr.tomselectObj) tr.tomselectObj.destroy();
    tr.remove();
    recalcular();
}

function bindInputs(tr) {
    const cant = tr.querySelector('.cantidad-input');
    cant.addEventListener('input', () => actualizarSubtotal(tr));
}

function actualizarSubtotal(tr) {
    const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
    const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
    const precio = parseFloat(precioReal?.value || '0') || 0;
    tr.querySelector('.subtotal-cell').textContent = '$' + window.formatNumber(cant * precio);
    recalcular();
}

function recalcular() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(tr => {
        const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
        const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
        const precio = parseFloat(precioReal?.value || '0') || 0;
        total += cant * precio;
    });
    document.getElementById('total-display').textContent = '$' + window.formatNumber(total);
}

// El sistema usará initGlassTomSelect de app.blade.php

@php
    $existingItems = $cotizacion->items->map(function($i) {
        return [
            'tipo' => $i->tipo,
            'item_id' => $i->item_id,
            'descripcion' => $i->descripcion,
            'cantidad' => $i->cantidad,
            'precio_unitario' => $i->precio_unitario,
        ];
    })->toJson();
@endphp

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar select de cliente principal
    const clienteSelect = document.querySelector('select[name="cliente_id"]');
    if (clienteSelect) {
        window.initGlassTomSelect(clienteSelect);
    }

    const itemsExistentes = {!! $existingItems !!};
    if (itemsExistentes.length === 0) {
        agregarFila();
    } else {
        itemsExistentes.forEach(item => {
            agregarFila(item);
        });
    }
});
</script>
@endsection
