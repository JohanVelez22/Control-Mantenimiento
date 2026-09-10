@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="{{ route('inventario.facturas') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">🛒 Registrar Venta de Inventario</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Vende productos, descuenta del stock y genera ingreso</p>
 </div>
 </div>

 <form action="{{ route('inventario.venta.store') }}" method="POST" id="venta-form" class="space-y-5">
 @csrf

 <div class="flex flex-col md:flex-row gap-5 p-5 bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl">
 <div class="w-full md:w-48 flex-shrink-0">
 <label class="field-label">N° Factura (Auto)</label>
 <input type="text" value="Automático" readonly class="glass-input font-mono bg-white/40 dark:bg-black/20 text-gray-500 cursor-not-allowed">
 </div>
 <div class="w-full flex-1 min-w-0">
 <label class="field-label">Cliente / Proveedor *</label>
 <select name="facturable_global" required class="glass-input no-search focus:ring-emerald-500" data-placeholder="Seleccionar...">
 <option value="">Seleccionar...</option>
 @foreach($clientes as $c)
 <option value="Cliente:{{ $c->id }}" data-tipo="{{ $c->tipo_cliente }}" {{ old('facturable_global') == "Cliente:{$c->id}" ? 'selected' : '' }}>
 👤 Cliente: {{ $c->nombre }} ({{ $c->identificacion }}){{ $c->tipo_cliente === 'tecnico' ? ' 🔧 Técnico' : '' }}
 </option>
 @endforeach
 @foreach($proveedores as $prov)
 <option value="Proveedor:{{ $prov->id }}" data-tipo="proveedor" {{ old('facturable_global') == "Proveedor:{$prov->id}" ? 'selected' : '' }}>
 🏢 Proveedor: {{ $prov->nombre_razon_social }} ({{ $prov->identificacion }})
 </option>
 @endforeach
 </select>
 @error('facturable_global') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 <div class="w-full md:w-48 flex-shrink-0">
 <label class="field-label">Fecha *</label>
 <input type="date" name="fecha" required value="{{ old('fecha', date('Y-m-d')) }}" class="glass-input focus:ring-emerald-500">
 @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
 </div>
 </div>

 {{-- Tabla de ítems --}}
 <div>
 <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-5">
 <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
 <span>🛍️</span> Artículos a Vender
 </h3>
 <button type="button" onclick="agregarFila()" class="btn-clean w-full sm:w-auto justify-center">
 ➕ Agregar artículo
 </button>
 </div>

 <div class="overflow-x-auto pb-2 max-h-[420px] overflow-y-auto">
 <table class="ts-table w-full table-fixed" id="items-table">
 <thead>
 <tr>
 <th class="w-auto px-2 py-3">Artículo del Stock</th>
 <th class="w-24 text-center px-2 py-3">Cant.</th>
 <th class="w-44 text-right px-3 py-3">Precio Un. ($)</th>
 <th class="w-36 text-right px-3 py-3">Subtotal</th>
 <th class="col-accion"></th>
 </tr>
 </thead>
 <tbody id="items-body">
<tr class="item-row bg-transparent">
  <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
   <select name="items[0][stock_id]" required class="stock-select glass-input no-search py-1.5 focus:ring-emerald-500" data-placeholder="Seleccionar producto...">
   <option value="">Seleccionar producto...</option>
  @foreach($stocks as $s)
  <option value="{{ $s->id }}" data-precio-compra="{{ $s->precio_compra }}" data-precio-venta="{{ $s->precio_venta }}" data-precio-tecnico="{{ $s->precio_tecnico > 0 ? $s->precio_tecnico : $s->precio_venta }}" data-stock="{{ $s->cantidad }}">
  {{ $s->producto }} (Disp: {{ $s->cantidad }}) — P.Venta: ${{ number_format($s->precio_venta, 0, ',', '.') }}
  </option>
  @endforeach
  </select>
  </td>
  <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
  <input type="number" name="items[0][cantidad]" min="1" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-emerald-500">
  </td>
  <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
  <input type="text" name="items[0][precio_unitario]" id="precio_unitario_real_0" value="0" required class="hidden">
  <input type="text" id="precio_unitario_visual_0" value="0" oninput="window.formatCurrencyDual(this, 'precio_unitario_real_0'); recalcular()" required class="precio-input glass-input py-1.5 text-right focus:ring-emerald-500 font-bold text-slate-800 dark:text-white transition-all">
  <div class="alerta-costo-badge hidden text-xs font-bold text-red-500 dark:text-red-400 text-right items-center justify-end gap-1.5" style="margin-top: 10px !important; margin-bottom: 2px !important;">
      <span>⚠️ Menor al costo (<span class="costo-ref font-black">$0</span>)</span>
  </div>
  </td>
  <td class="text-right font-black text-emerald-600 dark:text-emerald-400 text-base subtotal-cell pr-4" style="vertical-align: top !important; padding-top: 18px; padding-bottom: 10px;">
  $0
  </td>
  <td class="col-accion text-right" style="vertical-align: top !important; padding-top: 12px; padding-bottom: 10px;">
  <button type="button" onclick="eliminarFila(this)" class="btn-danger btn-icon shadow-sm hover:scale-105 transition-all" title="Eliminar ítem">🗑️</button>
  </td>
  </tr>
 </tbody>
 <tfoot>
 <tr class="border-t border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50">
 <td colspan="3" class="text-right font-bold text-gray-500 uppercase tracking-widest text-xs pt-4 pb-4">Total Documento:</td>
 <td class="text-right font-black text-2xl text-emerald-600 dark:text-emerald-400 pt-4 pb-4 pr-4" id="total-display">$0</td>
 <td></td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>

 {{-- Pago y observaciones --}}
 <div class="flex flex-col md:flex-row justify-center gap-5 p-5 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
<div class="text-center w-full md:w-1/2">
  <label class="field-label text-center block">Total Recibido Ahora ($) *</label>
  <input type="text" name="total_pagado" id="total_pagado_real" value="0" required class="hidden">
  <input type="text" id="total_pagado_visual" value="0" oninput="window.formatCurrencyDual(this, 'total_pagado_real'); recalcular()" required 
  class="glass-input text-2xl font-black text-center focus:ring-emerald-500 py-3 text-emerald-600 dark:text-emerald-400">
  <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-medium leading-tight">Si recibes menos del total, el estado quedará como <strong>Pendiente de Cobro</strong> y se registrará la deuda contable del cliente.</p>
</div>
 <div id="saldo-preview" class="hidden w-full md:w-1/2 flex-col justify-center items-center bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 transition-all">
 <p class="text-xs font-bold text-yellow-700 dark:text-yellow-400 mb-1 tracking-wide uppercase">⚠️ Saldo por Cobrar</p>
 <p class="text-2xl font-black text-yellow-600 dark:text-yellow-500" id="saldo-display">$0</p>
 </div>
 </div>

 <div>
 <label class="field-label">Observaciones</label>
 <textarea name="observaciones" rows="2" class="glass-input resize-y focus:ring-emerald-500" placeholder="Notas sobre la venta (ej: autorización de descuentos especiales)..."></textarea>
 </div>

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
              Hay uno o más productos con precio de venta inferior a su costo de adquisición. Puedes procesar la venta si se trata de un descuento autorizado o liquidación, pero generará margen negativo en caja.
          </p>
      </div>
  </div>

 <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="{{ route('inventario.facturas') }}" class="btn-cancel w-full sm:w-auto justify-center text-center">↩️ Cancelar</a>
 <button type="submit" class="btn-venta w-full sm:w-auto justify-center">
 🛒 Procesar Venta y Afectar Inventario
 </button>
 </div>
 </form>
 </div>
</div>

@php
 $stocksJson = $stocks->map(fn($s) => [
 'id' => $s->id,
 'nombre' => $s->producto,
 'precio_compra' => (float) $s->precio_compra,
 'precio_venta' => (float) $s->precio_venta,
 'precio_tecnico' => (float) ($s->precio_tecnico > 0 ? $s->precio_tecnico : $s->precio_venta),
 'cantidad' => $s->cantidad,
 ])->values()->all();
@endphp
<script>
let filaIndex = 1;
const stocksData = @json($stocksJson);

function esTecnicoActual() {
    const selCliente = document.querySelector('select[name="facturable_global"]');
    if (!selCliente) return false;
    const opt = selCliente.options[selCliente.selectedIndex];
    return opt ? opt.dataset.tipo === 'tecnico' : false;
}

function getPrecioStock(stock) {
    if (!stock) return 0;
    return esTecnicoActual() ? stock.precio_tecnico : stock.precio_venta;
}

function stockSelectOptions() {
    const esTec = esTecnicoActual();
    return stocksData.map(s => {
      const p = esTec ? s.precio_tecnico : s.precio_venta;
      const labelTag = esTec ? '🔧 P.Técnico' : 'P.Venta';
      return `<option value="${s.id}" data-precio-compra="${s.precio_compra}" data-precio-venta="${s.precio_venta}" data-precio-tecnico="${s.precio_tecnico}" data-stock="${s.cantidad}">${s.nombre} (Disp: ${s.cantidad}) — ${labelTag}: $${window.formatNumber(p)}</option>`;
    }).join('');
}

function agregarFila() {
  const tbody = document.getElementById('items-body');
  const tr = document.createElement('tr');
  tr.className = 'item-row bg-transparent border-t border-gray-200 dark:border-gray-700/50';
  tr.innerHTML = `
  <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
   <select name="items[${filaIndex}][stock_id]" required class="stock-select glass-input no-search py-1.5 focus:ring-emerald-500" data-placeholder="Seleccionar producto...">
   <option value="">Seleccionar producto...</option>
  ${stockSelectOptions()}
  </select>
  </td>
  <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
  <input type="number" name="items[${filaIndex}][cantidad]" min="1" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-emerald-500">
  </td>
  <td style="vertical-align: top !important; padding-top: 10px; padding-bottom: 10px;">
  <input type="text" name="items[${filaIndex}][precio_unitario]" id="precio_unitario_real_${filaIndex}" value="0" required class="hidden">
  <input type="text" id="precio_unitario_visual_${filaIndex}" value="0" oninput="window.formatCurrencyDual(this, 'precio_unitario_real_${filaIndex}'); recalcular()" required class="precio-input glass-input py-1.5 text-right focus:ring-emerald-500 font-bold text-slate-800 dark:text-white transition-all">
  <div class="alerta-costo-badge hidden text-xs font-bold text-red-500 dark:text-red-400 text-right items-center justify-end gap-1.5" style="margin-top: 10px !important; margin-bottom: 2px !important;">
      <span>⚠️ Menor al costo (<span class="costo-ref font-black">$0</span>)</span>
  </div>
  </td>
  <td class="text-right font-black text-emerald-600 dark:text-emerald-400 text-base subtotal-cell pr-4" style="vertical-align: top !important; padding-top: 18px; padding-bottom: 10px;">$0</td>
  <td class="col-accion text-right" style="vertical-align: top !important; padding-top: 12px; padding-bottom: 10px;">
  <button type="button" onclick="eliminarFila(this)" class="btn-danger btn-icon shadow-sm hover:scale-105 transition-all" title="Eliminar ítem">🗑️</button>
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
  if (document.querySelectorAll('.item-row').length === 1) {
    if (typeof window.showToast === 'function') {
      window.showToast('Debe haber al menos un ítem en la venta.', 'error');
    } else {
      alert('Debe haber al menos un ítem en la venta.');
    }
    return;
  }
  btn.closest('tr').remove();
  recalcular();
}

function bindFila(tr) {
  const sel = tr.querySelector('.stock-select');
  const cant = tr.querySelector('.cantidad-input');
  const precioVisual = tr.querySelector('[id^="precio_unitario_visual_"]');
  const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
  sel.addEventListener('change', () => {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    const stock = stocksData.find(s => s.id == opt.value);
    const precio = getPrecioStock(stock);
    if (precioReal) precioReal.value = precio;
    if (precioVisual) precioVisual.value = window.formatNumber(precio);
    actualizarSubtotal(tr);
  });
  cant.addEventListener('input', () => actualizarSubtotal(tr));
}

function actualizarSubtotal(tr) {
  const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
  const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
  const precio = parseFloat(precioReal?.value || '0') || 0;
  tr.querySelector('.subtotal-cell').textContent = '$' + window.formatNumber(cant * precio);
  recalcular();
}

function verificarAlertaCosto(tr) {
  const sel = tr.querySelector('.stock-select');
  if (!sel || !sel.value) return false;
  const stock = stocksData.find(s => s.id == sel.value);
  if (!stock) return false;

  const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
  const precioVisual = tr.querySelector('[id^="precio_unitario_visual_"]');
  const badge = tr.querySelector('.alerta-costo-badge');
  const costoRef = tr.querySelector('.costo-ref');

  const precioVenta = parseFloat(precioReal?.value || '0') || 0;
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

function recalcular() {
  let total = 0;
  let hayBajoCosto = false;
  document.querySelectorAll('.item-row').forEach(tr => {
    const cant = parseFloat(tr.querySelector('.cantidad-input')?.value || '0') || 0;
    const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
    const precio = parseFloat(precioReal?.value || '0') || 0;
    const subtotal = cant * precio;
    const subCell = tr.querySelector('.subtotal-cell');
    if (subCell) {
      subCell.textContent = '$' + window.formatNumber(subtotal);
    }
    total += subtotal;
    if (verificarAlertaCosto(tr)) {
      hayBajoCosto = true;
    }
  });
  document.getElementById('total-display').textContent = '$' + window.formatNumber(total);

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
  
  calcularSaldo(total);
}

function calcularSaldo(total) {
  const pagadoReal = document.getElementById('total_pagado_real');
  const pagado = parseFloat(pagadoReal?.value || '0') || 0;
  const saldo = total - pagado;
  const box = document.getElementById('saldo-preview');
  if (saldo > 0.01) {
    document.getElementById('saldo-display').textContent = '$' + window.formatNumber(Math.max(0, saldo));
    box.classList.remove('hidden');
    box.classList.add('flex');
  } else {
    box.classList.add('hidden');
    box.classList.remove('flex');
  }
}

const clienteGlobalSelect = document.querySelector('select[name="facturable_global"]');
if (clienteGlobalSelect) {
    clienteGlobalSelect.addEventListener('change', () => {
        document.querySelectorAll('.item-row').forEach(tr => {
            const sel = tr.querySelector('.stock-select');
            if (!sel || !sel.value) return;
            const stock = stocksData.find(s => s.id == sel.value);
            if (stock) {
                const nuevoPrecio = getPrecioStock(stock);
                const precioReal = tr.querySelector('[id^="precio_unitario_real_"]');
                const precioVisual = tr.querySelector('[id^="precio_unitario_visual_"]');
                if (precioReal) precioReal.value = nuevoPrecio;
                if (precioVisual) precioVisual.value = window.formatNumber(nuevoPrecio);
                actualizarSubtotal(tr);
            }
        });
    });
}

document.querySelectorAll('.item-row').forEach(bindFila);
</script>
@endsection
