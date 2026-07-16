<script>
let filaIndex = 0;
const stocksData = @json($stocksJson);

function getStockOptions() {
    return `<option value="">Seleccionar producto del stock...</option>` + 
           stocksData.map(s => `<option value="${s.id}" data-precio="${s.precio}" data-nombre="${s.nombre}" data-cantidad="${s.cantidad}">${s.nombre} (Disp: ${s.cantidad}) — $${window.formatNumber(s.precio)}</option>`).join('');
}

function agregarFila(itemData = null) {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.className = 'item-row bg-white/20 dark:bg-slate-900/20 border-t border-slate-200/50 dark:border-slate-700/50 hover:bg-white/50 dark:hover:bg-slate-800/30 transition-colors';
    
    let isStock = itemData ? itemData.tipo === 'stock' : false;
    let cant = itemData ? itemData.cantidad : 1;
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
        <td class="align-middle relative">
            <input type="number" name="items[${filaIndex}][cantidad]" min="1" value="${cant}" required class="cantidad-input glass-input py-1.5 text-center focus:ring-blue-500">
            <div class="stock-warning text-[10px] text-orange-500 font-bold absolute -bottom-3 left-0 w-full text-center hidden">Sin stock</div>
        </td>
        <td class="align-middle">
            <input type="text" name="items[${filaIndex}][precio_unitario]" id="precio_unitario_real_${filaIndex}" value="${precio}" required class="hidden">
            <input type="text" id="precio_unitario_visual_${filaIndex}" value="${window.formatNumber(precio)}" placeholder="0" oninput="window.formatCurrencyDual(this, 'precio_unitario_real_${filaIndex}'); recalcular()" required class="precio-input glass-input py-1.5 text-right focus:ring-blue-500 font-bold text-slate-800 dark:text-white">
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
    
    // Limpiar maxStock y warning al cambiar tipo
    tr.dataset.maxStock = '';
    validarStock(tr);

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
            
            // Buscar el stock disponible para validación inicial
            const stockItem = stocksData.find(s => s.id == itemData.item_id);
            if (stockItem) {
                tr.dataset.maxStock = stockItem.cantidad;
                validarStock(tr);
            }
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
            
            tr.dataset.maxStock = opt.dataset.cantidad;
            
            actualizarSubtotal(tr);
            validarStock(tr);
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
    cant.addEventListener('input', () => {
        actualizarSubtotal(tr);
        validarStock(tr);
    });
}

function validarStock(tr) {
    const cantInput = tr.querySelector('.cantidad-input');
    const warning = tr.querySelector('.stock-warning');
    const maxStockStr = tr.dataset.maxStock;
    
    if (maxStockStr && maxStockStr !== '') {
        const maxStock = parseInt(maxStockStr, 10);
        const currentCant = parseInt(cantInput.value, 10) || 0;
        
        if (currentCant > maxStock) {
            warning.textContent = `Disp: ${maxStock}`;
            warning.classList.remove('hidden');
            cantInput.classList.add('border-orange-400', 'ring-orange-400');
        } else {
            warning.classList.add('hidden');
            cantInput.classList.remove('border-orange-400', 'ring-orange-400');
        }
    } else {
        warning.classList.add('hidden');
        cantInput.classList.remove('border-orange-400', 'ring-orange-400');
    }
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
</script>
