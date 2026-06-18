@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('inventario.facturas') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📦 Registrar Compra de Inventario</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Ingresa productos al stock y genera la orden de pago</p>
 </div>
 </div>

 <form action="{{ route('inventario.compra.store') }}" method="POST" id="compra-form" class="space-y-8">
 @csrf

 {{-- Datos de la compra --}}
 <div class="flex flex-col md:flex-row gap-5 p-5 bg-orange-50/50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-500/20 rounded-2xl">
 <div class="w-full md:w-48 flex-shrink-0">
 <label class="field-label">N° Factura (Auto)</label>
 <input type="text" value="{{ $nextNumero }}" readonly class="glass-input font-mono bg-white/40 dark:bg-black/20 text-gray-500 cursor-not-allowed">
 </div>
 <div class="w-full flex-1">
 <label class="field-label">Proveedor *</label>
 <select name="proveedor_id" required class="glass-input focus:ring-orange-500">
 <option value="">Seleccionar...</option>
 @foreach($proveedores as $prov)
 <option value="{{ $prov->id }}" {{ old('proveedor_id', request('proveedor_id')) == $prov->id ? 'selected' : '' }}>
 {{ $prov->nombre_razon_social }} ({{ $prov->identificacion }})
 </option>
 @endforeach
 </select>
 @error('proveedor_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 <div class="w-full md:w-48 flex-shrink-0">
 <label class="field-label">Fecha *</label>
 <input type="date" name="fecha" required value="{{ old('fecha', date('Y-m-d')) }}" class="glass-input focus:ring-orange-500">
 @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 </div>

 {{-- Tabla de ítems --}}
 <div>
 <div class="flex justify-between items-center mb-4">
 <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
 <span>🏷️</span> Artículos Comprados
 </h3>
 <button type="button" onclick="agregarFila()" class="btn-clean">
 ➕ Agregar artículo
 </button>
 </div>

 <div class="overflow-x-auto pb-2">
 <table class="ts-table" id="items-table">
 <thead>
 <tr>
 <th>Artículo del Stock</th>
 <th class="w-24 text-center">Cant.</th>
 <th class="w-32 text-right">Precio Un. ($)</th>
 <th class="w-32 text-right">Subtotal</th>
 <th class="w-12 text-center"></th>
 </tr>
 </thead>
 <tbody id="items-body">
 {{-- Fila base --}}
 <tr class="item-row bg-transparent">
 <td>
 <select name="items[0][stock_id]" required class="stock-select glass-input py-1.5 focus:ring-orange-500">
 <option value="">Seleccionar producto...</option>
 @foreach($stocks as $s)
 <option value="{{ $s->id }}" data-precio="{{ $s->precio_compra }}">
 {{ $s->producto }} (Stock: {{ $s->cantidad }}) — P.Compra: ${{ number_format($s->precio_compra, 0, ',', '.') }}
 </option>
 @endforeach
 </select>
 </td>
 <td>
 <input type="number" name="items[0][cantidad]" min="1" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-orange-500">
 </td>
 <td>
 <input type="number" name="items[0][precio_unitario]" min="0" step="0.01" value="0" required class="precio-input glass-input py-1.5 text-right focus:ring-orange-500 font-mono">
 </td>
 <td class="text-right font-black text-orange-600 dark:text-orange-400 text-base subtotal-cell align-middle pr-4">
 $0
 </td>
 <td class="text-center align-middle">
 <button type="button" onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600 transition-colors p-2" title="Eliminar">✕</button>
 </td>
 </tr>
 </tbody>
 <tfoot>
 <tr class="border-t border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50">
 <td colspan="3" class="text-right font-bold text-gray-500 uppercase tracking-widest text-xs pt-4 pb-4">Total Documento:</td>
 <td class="text-right font-black text-2xl text-orange-600 dark:text-orange-400 pt-4 pb-4 pr-4" id="total-display">$0</td>
 <td></td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>

 {{-- Pago y observaciones --}}
 <div class="grid grid-cols-1 md:grid-cols-2 gap-5 p-5 bg-white/40 dark:bg-slate-800/40 border border-gray-200/60 dark:border-gray-700/60 rounded-2xl">
 <div>
 <label class="field-label">Total Pagado Ahora ($) *</label>
 <input type="number" name="total_pagado" id="total_pagado" min="0" step="0.01" value="0" required 
 class="glass-input text-2xl font-black text-right focus:ring-orange-500 py-3 text-emerald-600 dark:text-emerald-400">
 <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-medium leading-tight">Si pagas menos del total, el estado quedará como <strong>Pendiente de Pago</strong> y se registrará la deuda contable.</p>
 @error('total_pagado') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 <div id="saldo-preview" class="hidden flex-col justify-center items-center bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 transition-all">
 <p class="text-xs font-bold text-yellow-700 dark:text-yellow-400 mb-1 tracking-wide uppercase">⚠️ Saldo por Pagar</p>
 <p class="text-3xl font-black text-yellow-600 dark:text-yellow-500" id="saldo-display">$0</p>
 </div>
 </div>

 <div>
 <label class="field-label">Observaciones</label>
 <textarea name="observaciones" rows="2" class="glass-input resize-y focus:ring-orange-500" placeholder="Notas sobre la compra..."></textarea>
 </div>

 <div class="flex gap-4 pt-4 border-t border-gray-200/50 dark:border-white/10">
 <a href="{{ route('inventario.facturas') }}" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-compra">
 📦 Procesar Compra y Afectar Inventario
 </button>
 </div>
 </form>
 </div>
</div>

@php
 $stocksJson = $stocks->map(fn($s) => [
 'id' => $s->id,
 'nombre' => $s->producto,
 'precio' => $s->precio_compra,
 'cantidad' => $s->cantidad,
 ])->values()->all();
@endphp
<script>
let filaIndex = 1;
const stocksData = @json($stocksJson);

function stockSelectOptions() {
 return stocksData.map(s =>
 `<option value="${s.id}" data-precio="${s.precio}">${s.nombre} (Stock: ${s.cantidad}) — P.Compra: $${s.precio.toLocaleString('es-CO')}</option>`
 ).join('');
}

function agregarFila() {
 const tbody = document.getElementById('items-body');
 const tr = document.createElement('tr');
 tr.className = 'item-row bg-transparent border-t border-gray-200 dark:border-gray-700/50';
 tr.innerHTML = `
 <td>
 <select name="items[${filaIndex}][stock_id]" required class="stock-select glass-input py-1.5 focus:ring-orange-500">
 <option value="">Seleccionar producto...</option>
 ${stockSelectOptions()}
 </select>
 </td>
 <td>
 <input type="number" name="items[${filaIndex}][cantidad]" min="1" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-orange-500">
 </td>
 <td>
 <input type="number" name="items[${filaIndex}][precio_unitario]" min="0" step="0.01" value="0" required class="precio-input glass-input py-1.5 text-right focus:ring-orange-500 font-mono">
 </td>
 <td class="text-right font-black text-orange-600 dark:text-orange-400 text-base subtotal-cell align-middle pr-4">$0</td>
 <td class="text-center align-middle">
 <button type="button" onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600 p-2">✕</button>
 </td>`;
 tbody.appendChild(tr);
 filaIndex++;
 bindFila(tr);
 
 // Inicializar TomSelect en el nuevo select
 const newSelect = tr.querySelector('.stock-select');
 if (newSelect && typeof window.initGlassTomSelect === 'function') {
 window.initGlassTomSelect(newSelect);
 }
}

function eliminarFila(btn) {
 const filas = document.querySelectorAll('.item-row');
 if (filas.length === 1) return;
 btn.closest('tr').remove();
 recalcular();
}

function bindFila(tr) {
 const sel = tr.querySelector('.stock-select');
 const cant = tr.querySelector('.cantidad-input');
 const precio = tr.querySelector('.precio-input');
 sel.addEventListener('change', () => {
 const opt = sel.options[sel.selectedIndex];
 precio.value = opt.dataset.precio || 0;
 actualizarSubtotal(tr);
 });
 cant.addEventListener('input', () => actualizarSubtotal(tr));
 precio.addEventListener('input', () => actualizarSubtotal(tr));
}

function actualizarSubtotal(tr) {
 const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
 const precio = parseFloat(tr.querySelector('.precio-input').value) || 0;
 const sub = cant * precio;
 tr.querySelector('.subtotal-cell').textContent = '$' + sub.toLocaleString('es-CO');
 recalcular();
}

function recalcular() {
 let total = 0;
 document.querySelectorAll('.item-row').forEach(tr => {
 const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
 const precio = parseFloat(tr.querySelector('.precio-input').value) || 0;
 total += cant * precio;
 });
 document.getElementById('total-display').textContent = '$' + total.toLocaleString('es-CO');
 
 // Si el usuario no ha tocado el pago manual, auto-fill el pago total (comportamiento opcional, aquí lo dejamos en 0 si no se ha tocado o actualizamos la alerta)
 calcularSaldo(total);
}

function calcularSaldo(total) {
 const pagado = parseFloat(document.getElementById('total_pagado').value) || 0;
 const saldo = total - pagado;
 const box = document.getElementById('saldo-preview');
 if (saldo > 0.01) {
 document.getElementById('saldo-display').textContent = '$' + saldo.toLocaleString('es-CO', {minimumFractionDigits: 0});
 box.classList.remove('hidden');
 box.classList.add('flex');
 } else {
 box.classList.add('hidden');
 box.classList.remove('flex');
 }
}

document.querySelectorAll('.item-row').forEach(bindFila);
document.getElementById('total_pagado').addEventListener('input', () => {
 // Extraer total quitando símbolo y puntos
 const totalText = document.getElementById('total-display').textContent.replace(/[^0-9,-]+/g,""); 
 const total = parseFloat(totalText) || 0;
 calcularSaldo(total);
});
</script>
@endsection
