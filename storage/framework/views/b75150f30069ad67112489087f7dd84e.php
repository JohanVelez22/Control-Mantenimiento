
<?php $__env->startSection('title', 'Informes y Reportes - Diario'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex gap-4 mb-6 no-print">
 <a href="<?php echo e(route('reportes.financiero.diario')); ?>" class="bg-amber-500 text-white px-4 py-2 rounded-xl font-bold shadow-sm">💵 Informes Financieros</a>
 <a href="<?php echo e(route('mantenimientos.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="<?php echo e(route('electronicas.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Reporte de Electrónica</a>
 <a href="<?php echo e(route('stocks.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">📦 Informe Inventario</a>
</div>

<div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-4">
 <div>
 <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-2">
 📊 Informes y Reportes
 </h1>
 <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Mostrando todos los movimientos del <strong><?php echo e(\Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM [de] YYYY')); ?></strong>.</p>
 </div>
</div>

<div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-2 no-print">
 <a href="<?php echo e(route('reportes.financiero.diario')); ?>"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500 text-white shadow-lg ">
 📅 Diario
 </a>
 <a href="<?php echo e(route('reportes.financiero.acumulado')); ?>"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20">
 📈 Acumulado
 </a>
 <a href="<?php echo e(route('reportes.financiero.operaciones')); ?>"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20">
 📋 Operaciones
 </a>
</div>

<div class="glass-card p-5 mb-4 no-print">
 <form id="filtros-diario" method="GET" class="flex flex-wrap items-center gap-3">
   <label class="font-semibold text-sm">📅 Fecha:</label>
   <input type="date" name="fecha" value="<?php echo e($fecha); ?>" class="glass-input w-44">
   <button class="btn-primary py-2 px-5 text-sm" title="Filtrar">Ver Día</button>
   <a href="<?php echo e(route('reportes.financiero.diario')); ?>" class="btn-clean text-sm">Hoy</a>
  
   <div class="flex items-center gap-2 ml-auto">
       <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
       <span>🖨️</span> Imprimir
       </button>
       <button type="button" onclick="exportarDiario('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
       <span class="no-print-emoji">📊</span> Excel
       </button>
       <button type="button" onclick="exportarDiario('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
       <span class="no-print-emoji">📄</span> PDF
       </button>
   </div>
 </form>
</div>

 
 <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
 <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📈</span> Ingresos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">$<?php echo e(number_format($resumen['total_ingresos'], 0, ',', '.')); ?></p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
 <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📉</span> Egresos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">$<?php echo e(number_format($resumen['total_egresos'], 0, ',', '.')); ?></p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
 <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🔧</span> Mantenimientos</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10">$<?php echo e(number_format($resumen['total_mantenimientos'], 0, ',', '.')); ?></p>
 </div>
 <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
 <div class="absolute -right-6 -top-6 w-24 h-24 bg-gray-500/20 rounded-full blur-2xl group-hover:bg-gray-500/30 transition-all"></div>
 <p class="text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🚫</span> Anulados</p>
 <p class="text-2xl font-black text-slate-800 dark:text-white z-10"><?php echo e($resumen['total_anulados']); ?></p>
 </div>
 </div>

 
 <div class="glass-card p-6 md:p-8 mt-4">
 <div class="flex justify-between items-center mb-4">
 <h3 class="text-lg font-bold">Movimientos del Día (<?php echo e($movimientos->count()); ?>)</h3>
 </div>

 <?php if($movimientos->isEmpty()): ?>
 <div class="flex flex-col items-center justify-center space-y-3 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm p-12 rounded-2xl border border-white/20 my-4">
     <div class="text-5xl opacity-80">📭</div>
     <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
     <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No hubo movimientos en esta fecha.</p>
 </div>
 <?php else: ?>
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm">
 <thead>
 <tr>
 <th class="p-3 text-center">Tipo</th>
 <th class="p-3 text-left">Descripción</th>
 <th class="p-3 text-center">Progreso</th>
 <th class="p-3 text-center">Estado</th>
 <th class="p-3 text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 <?php $__currentLoopData = $movimientos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mov): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <?php
   $isAnulado = !empty($mov['anulado']);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 ?>
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-center">
 <td class="p-3 <?php echo e($dimLight); ?>">
   <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-bold bg-<?php echo e($mov['color']); ?>-100 text-<?php echo e($mov['color']); ?>-800 dark:bg-<?php echo e($mov['color']); ?>-900/40 dark:text-<?php echo e($mov['color']); ?>-300">
   <span class="no-print-emoji"><?php echo e($mov['icono']); ?></span> <?php echo e(ucfirst($mov['tipo'])); ?>

   </span>
 </td>
 <td class="p-3 text-left text-gray-700 dark:text-gray-300 <?php echo e($dim); ?>"><?php echo e($mov['descripcion']); ?></td>
   <td class="p-3 <?php echo e($dimLight); ?>">
   <?php
       $progreso = strtolower($mov['estado'] ?? '');
       
       // Clarificar el "progreso" para transacciones que no son mantenimientos
       if(in_array($mov['tipo'], ['ingreso', 'egreso'])) $progreso = 'procesado';
       if(in_array($mov['tipo'], ['venta', 'compra'])) $progreso = 'emitida';

       $pillClass = 'pill-pending';
       if(in_array($progreso, ['terminado', 'entregado'])) $pillClass = 'pill-done';
       elseif($progreso === 'emitida') $pillClass = 'pill-preventivo';
       elseif($progreso === 'procesado') $pillClass = 'pill-especialidad';
       elseif(in_array($progreso, ['en_proceso', 'reparado'])) $pillClass = 'pill-efectivo';
   ?>
   <span class="pill <?php echo e($pillClass); ?> <?php echo e($isAnulado ? 'opacity-70' : ''); ?>"><?php echo e(ucfirst($progreso) ?: '—'); ?></span>
   </td>
 <td class="p-3">
 <span class="pill <?php echo e($isAnulado ? 'pill-anulado' : 'pill-done'); ?>">
 <?php echo e($isAnulado ? 'Anulado' : 'Activo'); ?>

 </span>
 </td>
 <td class="p-3 text-center font-bold text-gray-900 dark:text-gray-100">
 $<?php echo e(number_format($mov['monto'] ?? 0, 0, ',', '.')); ?>

 </td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </tbody>
   <tfoot>
    <tr class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
        <?php
            $neto = $movimientos->where('anulado', false)->whereIn('tipo', ['ingreso','venta','mantenimiento','electronica'])->sum('monto') 
                  - $movimientos->where('anulado', false)->whereIn('tipo', ['egreso','compra'])->sum('monto');
        ?>
        <td class="text-center font-bold text-xs">Total: <?php echo e($movimientos->count()); ?></td>
        <td colspan="3" class="text-right uppercase text-xs">Balance Neto del Día:</td>
        <td class="text-center font-black text-lg text-gray-900 dark:text-gray-100">$<?php echo e(number_format($neto, 0, ',', '.')); ?></td>
    </tr>
 </tfoot>
 </table>
 </div>
 <?php endif; ?>
 </div>

</div>

<script>
function exportarDiario(tipo, btn) {
    const form = document.getElementById('filtros-diario');
    const params = new URLSearchParams(new FormData(form));
    params.set('export', tipo);
    const url = window.location.pathname + '?' + params.toString();
    const fallbackName = 'Reporte_Diario_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
    
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

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm 8mm;
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
        font-size: 9pt !important;
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
    
    table, .ts-table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 15px !important;
        margin-bottom: 15px !important;
        font-size: 8.5pt !important;
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
        font-size: 8pt !important;
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
    
    .grid {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 15px !important;
        margin-bottom: 20px !important;
    }
    
    .grid > div {
        flex: 1 1 20% !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        padding: 12px !important;
        background-color: #f8fafc !important;
        text-align: center !important;
        box-shadow: none !important;
    }
    
    .grid p {
        margin: 0 !important;
    }
    
    .grid p.text-xs {
        font-size: 7.5pt !important;
        color: #4a5568 !important;
        font-weight: bold !important;
    }
    
    .grid p.text-2xl, .grid p.text-3xl {
        font-size: 14pt !important;
        font-weight: 800 !important;
        color: #1a202c !important;
        margin-top: 5px !important;
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
    
    .no-print-emoji, table td span.no-print-emoji, .grid p span {
        display: none !important;
    }
    
    .responsive-table td::before {
        display: none !important;
    }
    
    h1, h2, h3 {
        color: #1a202c !important;
        font-weight: bold !important;
    }
    
    h1 {
        font-size: 16pt !important;
        margin-bottom: 5px !important;
    }
    
    h3 {
        font-size: 11pt !important;
        margin-bottom: 10px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding-bottom: 5px !important;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/reportes_financieros/diario.blade.php ENDPATH**/ ?>