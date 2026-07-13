<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto">
 <div class="glass-card p-6 md:p-8">
 <div class="flex items-center gap-3 mb-8">
 <a href="<?php echo e(route('inventario.facturas')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
 <div>
 <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">🛒 Registrar Venta de Inventario</h2>
 <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Vende productos, descuenta del stock y genera ingreso</p>
 </div>
 </div>

 <form action="<?php echo e(route('inventario.venta.store')); ?>" method="POST" id="venta-form" class="space-y-5">
 <?php echo csrf_field(); ?>

 <div class="flex flex-col md:flex-row gap-5 p-5 bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-500/20 rounded-2xl">
 <div class="w-full md:w-48 flex-shrink-0">
 <label class="field-label">N° Factura (Auto)</label>
 <input type="text" value="<?php echo e($nextNumero); ?>" readonly class="glass-input font-mono bg-white/40 dark:bg-black/20 text-gray-500 cursor-not-allowed">
 </div>
 <div class="w-full flex-1 min-w-0">
 <label class="field-label">Cliente / Proveedor *</label>
 <select name="facturable_global" required class="glass-input focus:ring-emerald-500">
 <option value="">Seleccionar...</option>
 <?php $__currentLoopData = $clientes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="Cliente:<?php echo e($c->id); ?>" <?php echo e(old('facturable_global') == "Cliente:{$c->id}" ? 'selected' : ''); ?>>
 👤 Cliente: <?php echo e($c->nombre); ?> (<?php echo e($c->identificacion); ?>)
 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 <?php $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prov): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="Proveedor:<?php echo e($prov->id); ?>" <?php echo e(old('facturable_global') == "Proveedor:{$prov->id}" ? 'selected' : ''); ?>>
 🏢 Proveedor: <?php echo e($prov->nombre_razon_social); ?> (<?php echo e($prov->identificacion); ?>)
 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 <?php $__errorArgs = ['facturable_global'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 <div class="w-full md:w-48 flex-shrink-0">
 <label class="field-label">Fecha *</label>
 <input type="date" name="fecha" required value="<?php echo e(old('fecha', date('Y-m-d'))); ?>" class="glass-input focus:ring-emerald-500">
 <?php $__errorArgs = ['fecha'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1 font-bold"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>
 </div>

 
 <div>
 <div class="flex justify-between items-center mb-5">
 <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
 <span>🛍️</span> Artículos a Vender
 </h3>
 <button type="button" onclick="agregarFila()" class="btn-clean">
 ➕ Agregar artículo
 </button>
 </div>

 <div class="overflow-x-auto pb-2 max-h-[420px] overflow-y-auto">
 <table class="ts-table" id="items-table">
 <thead>
 <tr>
 <th class="w-[45%]">Artículo del Stock</th>
 <th class="w-24 text-center">Cant.</th>
 <th class="min-w-[160px] text-right">Precio Un. ($)</th>
 <th class="min-w-[160px] text-right">Subtotal</th>
 <th class="w-12 text-center"></th>
 </tr>
 </thead>
 <tbody id="items-body">
 <tr class="item-row bg-transparent">
 <td>
 <select name="items[0][stock_id]" required class="stock-select glass-input py-1.5 focus:ring-emerald-500">
 <option value="">Seleccionar producto...</option>
 <?php $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <option value="<?php echo e($s->id); ?>" data-precio="<?php echo e($s->precio_venta); ?>" data-stock="<?php echo e($s->cantidad); ?>">
 <?php echo e($s->producto); ?> (Disp: <?php echo e($s->cantidad); ?>) — P.Venta: $<?php echo e(number_format($s->precio_venta, 0, ',', '.')); ?>

 </option>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </select>
 </td>
 <td>
 <input type="number" name="items[0][cantidad]" min="1" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-emerald-500">
 </td>
 <td>
 <input type="text" name="items[0][precio_unitario]" value="0" oninput="window.formatCurrencyInput(this); recalcular()" required class="precio-input glass-input py-1.5 text-right focus:ring-emerald-500 font-mono">
 </td>
 <td class="text-right font-black text-emerald-600 dark:text-emerald-400 text-base subtotal-cell align-middle pr-4">
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
 <td class="text-right font-black text-2xl text-emerald-600 dark:text-emerald-400 pt-4 pb-4 pr-4" id="total-display">$0</td>
 <td></td>
 </tr>
 </tfoot>
 </table>
 </div>
 </div>

 
 <div class="flex flex-col md:flex-row justify-center gap-5 p-5 bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
 <div class="text-center w-full md:w-1/2">
 <label class="field-label text-center block">Total Recibido Ahora ($) *</label>
 <input type="text" name="total_pagado" id="total_pagado" value="0" oninput="window.formatCurrencyInput(this); recalcular()" required 
 class="glass-input text-2xl font-black text-center focus:ring-emerald-500 py-3 text-emerald-600 dark:text-emerald-400">
 <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-medium leading-tight">Si recibes menos del total, el estado quedará como <strong>Pendiente de Cobro</strong> y se registrará la deuda contable del cliente.</p>
 </div>
 <div id="saldo-preview" class="hidden w-full md:w-1/2 flex-col justify-center items-center bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 transition-all">
 <p class="text-xs font-bold text-yellow-700 dark:text-yellow-400 mb-1 tracking-wide uppercase">⚠️ Saldo por Cobrar</p>
 <p class="text-3xl font-black text-yellow-600 dark:text-yellow-500" id="saldo-display">$0</p>
 </div>
 </div>

 <div>
 <label class="field-label">Observaciones</label>
 <textarea name="observaciones" rows="2" class="glass-input resize-y focus:ring-emerald-500" placeholder="Notas sobre la venta..."></textarea>
 </div>

 <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
 <a href="<?php echo e(route('inventario.facturas')); ?>" class="btn-cancel">↩️ Cancelar</a>
 <button type="submit" class="btn-venta">
 🛒 Procesar Venta y Afectar Inventario
 </button>
 </div>
 </form>
 </div>
</div>

<?php
 $stocksJson = $stocks->map(fn($s) => [
 'id' => $s->id,
 'nombre' => $s->producto,
 'precio' => $s->precio_venta,
 'cantidad' => $s->cantidad,
 ])->values()->all();
?>
<script>
let filaIndex = 1;
const stocksData = <?php echo json_encode($stocksJson, 15, 512) ?>;

function stockSelectOptions() {
    return stocksData.map(s =>
      `<option value="${s.id}" data-precio="${s.precio}" data-stock="${s.cantidad}">${s.nombre} (Disp: ${s.cantidad}) — P.Venta: $${window.formatNumber(s.precio)}</option>`
    ).join('');
}

function agregarFila() {
 const tbody = document.getElementById('items-body');
 const tr = document.createElement('tr');
 tr.className = 'item-row bg-transparent border-t border-gray-200 dark:border-gray-700/50';
 tr.innerHTML = `
 <td>
 <select name="items[${filaIndex}][stock_id]" required class="stock-select glass-input py-1.5 focus:ring-emerald-500">
 <option value="">Seleccionar producto...</option>
 ${stockSelectOptions()}
 </select>
 </td>
 <td>
 <input type="number" name="items[${filaIndex}][cantidad]" min="1" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-emerald-500">
 </td>
 <td>
 <input type="text" name="items[${filaIndex}][precio_unitario]" value="0" oninput="window.formatCurrencyInput(this); recalcular()" required class="precio-input glass-input py-1.5 text-right focus:ring-emerald-500 font-mono">
 </td>
 <td class="text-right font-black text-emerald-600 dark:text-emerald-400 text-base subtotal-cell align-middle pr-4">$0</td>
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
 if (document.querySelectorAll('.item-row').length === 1) return;
 btn.closest('tr').remove();
 recalcular();
}

function bindFila(tr) {
 const sel = tr.querySelector('.stock-select');
 const cant = tr.querySelector('.cantidad-input');
 const precio = tr.querySelector('.precio-input');
 sel.addEventListener('change', () => {
 const opt = sel.options[sel.selectedIndex];
  precio.value = window.formatNumber(parseInt(opt.dataset.precio || 0));
 const maxStock = parseInt(opt.dataset.stock) || 0;
 cant.max = maxStock;
 actualizarSubtotal(tr);
 });
 cant.addEventListener('input', () => actualizarSubtotal(tr));
}

function actualizarSubtotal(tr) {
 const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
 const precioText = tr.querySelector('.precio-input').value.replace(/\./g, '');
 const precio = parseFloat(precioText) || 0;
  tr.querySelector('.subtotal-cell').textContent = '$' + window.formatNumber(cant * precio);
 recalcular();
}

function recalcular() {
 let total = 0;
 document.querySelectorAll('.item-row').forEach(tr => {
 const cant = parseFloat(tr.querySelector('.cantidad-input').value) || 0;
 const pText = tr.querySelector('.precio-input').value.replace(/\./g, '');
 const p = parseFloat(pText) || 0;
 total += cant * p;
 });
  document.getElementById('total-display').textContent = '$' + window.formatNumber(total);
  
  // Auto-fill o alertar
  const totalText = document.getElementById('total-display').textContent.replace(/[^0-9,-]+/g,""); 
  const tot = parseFloat(totalText) || 0;
  calcularSaldo(tot);
}

function calcularSaldo(total) {
 const pagadoText = document.getElementById('total_pagado').value.replace(/\./g, '');
 const pagado = parseFloat(pagadoText) || 0;
 const saldo = total - pagado;
 const box = document.getElementById('saldo-preview');
  if (saldo > 0.01) {
  document.getElementById('saldo-display').textContent = '$' + window.formatNumber(Math.round(saldo));
 box.classList.remove('hidden');
 box.classList.add('flex');
 } else {
 box.classList.add('hidden');
 box.classList.remove('flex');
 }
}

document.querySelectorAll('.item-row').forEach(bindFila);
 // Listener delegado a global
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/inventario/venta.blade.php ENDPATH**/ ?>