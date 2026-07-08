<?php $__env->startSection('content'); ?>
<div class="flex gap-4 mb-6 no-print">
    <a href="<?php echo e(route('reportes.financiero.diario')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">💵 Informes Financieros</a>
    <a href="<?php echo e(route('mantenimientos.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
    <a href="<?php echo e(route('electronicas.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Reporte de Electrónica</a>
    <a href="<?php echo e(route('stocks.reportes')); ?>" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm">📦 Informe Inventario</a>
</div>

<div class="glass-card p-6 mb-6">
    <!-- Encabezado y Botones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4 no-print">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Reporte Detallado de Inventario</h2>
        </div>
        
        <div class="flex flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-print">
                <span>🖨️</span> Imprimir
            </button>
            <button type="button" onclick="exportarReporte('excel', this)" class="btn-excel">
                <span>📊</span> Excel
            </button>
            <button type="button" onclick="exportarReporte('pdf', this)" class="btn-pdf">
                <span>📄</span> PDF
            </button>
        </div>
    </div>

    <!-- Formulario de Filtros -->
    <form id="filtros-stock" action="<?php echo e(route('stocks.reportes')); ?>" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-4 items-end mb-8 p-5 glass-card no-print relative z-50">
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Proveedor</label>
            <select name="proveedor_id" class="glass-input">
                <option value="todos">Todos los proveedores</option>
                <?php $__currentLoopData = $proveedores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prov): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($prov->id); ?>" <?php echo e(request('proveedor_id') == $prov->id ? 'selected' : ''); ?>><?php echo e($prov->nombre_razon_social); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Categoría</label>
            <select name="categoria" class="glass-input">
                <option value="todos">Todas las categorías</option>
                <?php $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cat); ?>" <?php echo e(request('categoria') == $cat ? 'selected' : ''); ?>><?php echo e($cat); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Subcategoría</label>
            <select name="subcategoria" class="glass-input">
                <option value="todos">Todas las subcategorías</option>
                <?php $__currentLoopData = $subcategorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($sub); ?>" <?php echo e(request('subcategoria') == $sub ? 'selected' : ''); ?>><?php echo e($sub); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Desde</label>
            <input type="date" name="desde" value="<?php echo e(request('desde', date('Y-m-01'))); ?>" class="glass-input">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
            <input type="date" name="hasta" value="<?php echo e(request('hasta', date('Y-m-d'))); ?>" class="glass-input">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Buscar Costo Por</label>
            <select name="price_type" class="glass-input no-search">
                <option value="precio_compra" <?php echo e(request('price_type') === 'precio_compra' ? 'selected' : ''); ?>>P. Compra</option>
                <option value="precio_venta" <?php echo e(request('price_type') === 'precio_venta' ? 'selected' : ''); ?>>P. Venta</option>
                <option value="precio_tecnico" <?php echo e(request('price_type') === 'precio_tecnico' ? 'selected' : ''); ?>>P. Técnico</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Costo Mínimo ($)</label>
            <input type="text" id="min_costo_visual" value="<?php echo e(request('min_costo') ? number_format(request('min_costo'), 0, '', '.') : ''); ?>" placeholder="0" class="glass-input">
            <input type="hidden" name="min_costo" id="min_costo" value="<?php echo e(request('min_costo')); ?>">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Costo Máximo ($)</label>
            <input type="text" id="max_costo_visual" value="<?php echo e(request('max_costo') ? number_format(request('max_costo'), 0, '', '.') : ''); ?>" placeholder="0" class="glass-input">
            <input type="hidden" name="max_costo" id="max_costo" value="<?php echo e(request('max_costo')); ?>">
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Estado</label>
            <select name="estado" class="glass-input no-search">
                <option value="todos" <?php echo e(request('estado') === 'todos' ? 'selected' : ''); ?>>Todos</option>
                <option value="activo" <?php echo e(request('estado') === 'activo' || request('estado') === null ? 'selected' : ''); ?>>Activo</option>
                <option value="inactivo" <?php echo e(request('estado') === 'inactivo' ? 'selected' : ''); ?>>Inactivo</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">Búsqueda Rápida</label>
            <input type="text" name="search" id="real_time_search" class="glass-input" value="<?php echo e(request('search')); ?>" placeholder="Producto, código...">
        </div>

        <div class="md:col-span-4 lg:col-span-5 flex justify-end gap-2 mt-2">
            <a href="<?php echo e(route('stocks.reportes')); ?>" class="btn-clean">
                🧹 Limpiar
            </a>
            <button type="submit" class="btn-primary">
                🌪️ Filtrar Reporte
            </button>
        </div>
    </form>

    <!-- Encabezado solo visible al imprimir -->
    <div class="print-header hidden-screen">
        <p style="text-align: center; margin-top: 0; font-size: 10px; color: #4a5568;">Generado el: <?php echo e(date('d/m/Y h:i A')); ?></p>
    </div>

    <!-- Tabla con Datos -->
    <div class="overflow-x-auto pb-2">
        <table class="ts-table responsive-table reportes-tabla-imprimir w-full">
            <thead>
                <tr>
                    <th class="text-left w-24">Código</th>
                    <th class="text-center">Producto</th>
                    <th class="text-center">Proveedor</th>
                    <th class="text-center w-20">Cant.</th>
                    <th class="text-center w-24">Estado</th>
                    <th class="text-right w-28">P. Compra</th>
                    <th class="text-center w-20">Utilidad</th>
                    <th class="text-right w-28">P. Venta</th>
                    <th class="text-right w-28">P. Técnico</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stock): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $isAnulado = !$stock->active;
                    $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
                    $dimLight = $isAnulado ? 'opacity-60' : '';
                ?>
                <tr>
                    <td class="text-sm font-bold text-slate-500 dark:text-slate-400 <?php echo e($dim); ?>">
                        <?php if(!empty($stock->codigo)): ?>
                            <a href="<?php echo e(route('stocks.index', ['locate' => $stock->id])); ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline transition-colors no-print-link">
                                <?php echo e($stock->codigo); ?>

                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-center <?php echo e($dim); ?>">
                        <div class="flex flex-col items-center leading-tight">
                            <a href="<?php echo e(route('stocks.index', ['locate' => $stock->id])); ?>" class="font-bold text-slate-800 dark:text-white hover:text-blue-500 dark:hover:text-blue-400 transition-colors">
                                <?php echo e($stock->producto); ?>

                            </a>
                        </div>
                        <?php if($stock->categoria || $stock->subcategoria): ?>
                        <div class="text-[10px] text-gray-500 tracking-wider uppercase mt-1">
                            <?php echo e($stock->categoria ?? 'Sin Categoría'); ?> <?php echo e($stock->subcategoria ? ' / ' . $stock->subcategoria : ''); ?>

                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm font-medium text-center <?php echo e($dim); ?>">
                        <?php if(!empty($stock->proveedor_id)): ?>
                            <a href="<?php echo e(route('proveedores.index', ['locate' => $stock->proveedor_id])); ?>" class="flex flex-col items-center gap-0 group no-print-link transition-colors" title="Ver en tabla de proveedores">
                                <span class="text-slate-800 dark:text-white font-bold whitespace-nowrap group-hover:text-blue-500 dark:group-hover:text-blue-400 transition-colors">
                                    <?php echo e($stock->getRelationValue('proveedor')->nombre_razon_social ?? 'Proveedor ' . $stock->proveedor_id); ?>

                                </span>
                                <?php if(optional($stock->getRelationValue('proveedor'))->identificacion): ?>
                                <span class="text-[11px] font-semibold text-gray-500 tracking-wider uppercase mt-0.5">
                                    <?php echo e($stock->getRelationValue('proveedor')->identificacion); ?>

                                </span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <?php echo e($stock->getRawOriginal('proveedor') ?: '-'); ?>

                        <?php endif; ?>
                    </td>
                    <td class="text-center <?php echo e($dim); ?>">
                        <span class="pill <?php echo e($stock->cantidad > 5 ? 'pill-done' : 'pill-anulado'); ?>">
                            <?php echo e($stock->cantidad); ?>

                        </span>
                    </td>
                    <td class="text-center">
                        <span class="pill <?php echo e($isAnulado ? 'pill-anulado' : 'pill-done'); ?>">
                            <?php echo e($isAnulado ? 'Inactivo' : 'Activo'); ?>

                        </span>
                    </td>
                    <td class="text-right font-medium <?php echo e($dim); ?>">$<?php echo e(number_format($stock->precio_compra, 0, '', '.')); ?></td>
                    <td class="text-center font-bold text-green-600 dark:text-green-400 <?php echo e($dim); ?>">
                        +<?php echo e($stock->utilidad); ?>%
                    </td>
                    <td class="text-right font-black text-slate-800 dark:text-white <?php echo e($dim); ?>">$<?php echo e(number_format($stock->precio_venta, 0, '', '.')); ?></td>
                    <td class="text-right font-black text-slate-800 dark:text-white <?php echo e($dim); ?>">$<?php echo e(number_format($stock->precio_tecnico, 0, '', '.')); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9" class="p-12 text-center bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="text-5xl opacity-80">📦</div>
                            <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Intenta con otros filtros de búsqueda.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            <?php if($stocks->count() > 0): ?>
            <tfoot class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
                <tr>
                    <td colspan="2" class="text-left uppercase text-xs font-bold pt-2 pl-4">
                        <div class="flex items-center">
                            <span>Total: <span class="text-slate-800 dark:text-white"><?php echo e($stocks->total()); ?></span></span>
                        </div>
                    </td>
                    <td class="text-right uppercase text-xs pt-2">Totales:</td>
                    <td class="text-center font-bold pt-2"><?php echo e($stocks->sum('cantidad')); ?></td>
                    <td class="pt-2"></td>
                    <td class="text-center font-black text-slate-800 dark:text-slate-200 pt-2">$<?php echo e(number_format($stocks->sum('precio_compra'), 0, '', '.')); ?></td>
                    <td class="pt-2"></td>
                    <td class="text-center font-black text-slate-800 dark:text-slate-200 pt-2">$<?php echo e(number_format($stocks->sum('precio_venta'), 0, '', '.')); ?></td>
                    <td class="text-center font-black text-slate-800 dark:text-slate-200 pt-2">$<?php echo e(number_format($stocks->sum('precio_tecnico'), 0, '', '.')); ?></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
    <div class="mt-4 no-print">
        <?php echo e($stocks->appends(request()->query())->links()); ?>

    </div>
</div>

<style>
/* ── Bloque de estilos solo para impresión ── */
@media print {
    @page {
        size: A4 landscape;
        margin: 10mm 8mm 15mm 8mm;
    }
    
    .no-print,
    #ts-sidebar,
    #ts-topbar,
    header,
    aside,
    nav,
    form,
    button,
    .btn,
    .pagination,
    a[href*="export"],
    .flex.gap-4.mb-6.no-print {
        display: none !important;
    }
    
    /* Disable flexbox layouts during print to prevent desktop viewport scaling and right-side clipping */
    .flex.min-h-screen,
    #main-wrapper {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    #ts-main,
    main {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 !important;
        padding: 8mm 6mm !important; /* Force physical narrow margins */
        box-sizing: border-box !important;
        box-shadow: none !important;
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    html, body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
        font-size: 8pt !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    .glass-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
        margin-bottom: 20px !important;
        padding: 0 !important;
    }
    
    /* Encabezado visible al imprimir */
    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 4mm;
    }
    
    table, .ts-table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 15px !important;
        margin-bottom: 15px !important;
        font-size: 8pt !important;
    }
    
    thead {
        display: table-header-group !important;
    }
    
    tr {
        page-break-inside: avoid !important;
    }
    
    th {
        background-color: #2d3748 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        text-transform: uppercase !important;
        border: 1px solid #cbd5e0 !important;
        padding: 8px 10px !important;
        font-size: 7.5pt !important;
    }
    
    td {
        border: 1px solid #cbd5e0 !important;
        padding: 7px 10px !important;
        background-color: #ffffff !important;
        color: #000000 !important;
        vertical-align: middle !important;
    }
    
    tbody tr:nth-child(even) td {
        background-color: #f8fafc !important;
    }
    
    tfoot, .tfoot {
        display: table-footer-group !important;
        font-weight: bold !important;
    }
    
    tfoot td {
        border: 1px solid #cbd5e0 !important;
        border-top: 1px solid #cbd5e0 !important;
        background-color: #e2e8f0 !important;
        font-weight: bold !important;
    }
    
    span.pill, .badge, table td span, .ts-table td span, .reportes-tabla-imprimir td span {
        display: inline !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        font-size: 8pt !important;
        font-weight: normal !important;
        background: transparent !important;
        background-color: transparent !important;
        color: #000000 !important;
        text-transform: uppercase !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    
    .no-print-emoji, table td span.no-print-emoji, span.text-lg {
        display: none !important;
    }
    
    .responsive-table td::before {
        display: none !important;
    }

</style>

<script>
    // Filtrado en tiempo real de la tabla (Cliente-side)
    document.getElementById('real_time_search').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.reportes-tabla-imprimir tbody tr');

        rows.forEach(row => {
            if (row.cells.length > 1) { // Evitar la fila de "No hay registros"
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll("select.glass-input").forEach((el) => {
            if (el.tomselect) return;
            if (window.initGlassTomSelect) {
                window.initGlassTomSelect(el);
            }
        });
    });

    function formatInput(visualId, realId) {
        const inputVisual = document.getElementById(visualId);
        const inputReal = document.getElementById(realId);

        if (inputVisual && inputReal) {
            inputVisual.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value !== "") {
                    inputReal.value = value;
                    e.target.value = new Intl.NumberFormat('es-CO').format(value);
                } else {
                    inputReal.value = "";
                    e.target.value = "";
                }
            });
        }
    }

    formatInput('min_costo_visual', 'min_costo');
    formatInput('max_costo_visual', 'max_costo');

    function exportarReporte(tipo, btn) {
        const form = document.getElementById('filtros-stock');
        const params = new URLSearchParams(new FormData(form));
        params.set('export', tipo);
        const url = window.location.pathname + '?' + params.toString();
        const fallbackName = 'Reporte_Inventario_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
        
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span>...';
        
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Error al generar el reporte');
                let filename = fallbackName;
                const disposition = response.headers.get('Content-Disposition');
                if (disposition && disposition.indexOf('attachment') !== -1) {
                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) { 
                        filename = matches[1].replace(/['"]/g, '');
                    }
                }
                return response.blob().then(blob => ({ blob, filename }));
            })
            .then(({ blob, filename }) => {
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(blobUrl);
                a.remove();
            })
            .catch(error => {
                console.error(error);
                alert('Hubo un error al generar o descargar el reporte.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = origText;
            });
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/stocks/reportes.blade.php ENDPATH**/ ?>