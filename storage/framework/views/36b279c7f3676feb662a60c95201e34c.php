<?php $__env->startSection('title', 'Informes y Reportes'); ?>

<?php $__env->startSection('content'); ?>
<div class="flex gap-4 mb-6 no-print">
 <a href="<?php echo e(route('reportes.financiero.diario')); ?>" class="bg-amber-500 text-white px-4 py-2 rounded-xl font-bold shadow-sm">💵 Informes Financieros</a>
 <a href="<?php echo e(route('mantenimientos.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚙️ Reporte de Mantenimientos</a>
 <a href="<?php echo e(route('electronicas.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">⚡ Reporte de Electrónica</a>
 <a href="<?php echo e(route('stocks.reportes')); ?>" class="bg-white/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 px-4 py-2 rounded-xl font-bold shadow-sm transition-colors">📦 Informe Inventario</a>
</div>

<div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex flex-col gap-4 no-print">
 <div>
 <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-2">
 📊 Informes y Reportes
 </h1>
 <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Análisis financiero y de operaciones.</p>
 </div>
</div>

<div class="glass-card p-4 mb-6 flex flex-wrap items-center gap-2 no-print">
 <a href="<?php echo e(route('reportes.financiero.diario')); ?>"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20">
 📅 Diario
 </a>
 <a href="<?php echo e(route('reportes.financiero.acumulado')); ?>"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20">
 📈 Acumulado
 </a>
 <a href="<?php echo e(route('reportes.financiero.operaciones')); ?>"
 class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500 text-white shadow-lg ">
 📋 Operaciones
 </a>
</div>

<div class="glass-card p-5 mb-4 no-print relative z-50">
 <form id="filtros-operaciones" method="GET" class="flex flex-wrap items-center gap-3">
   <select name="tipo" class="glass-input no-search w-72 font-semibold">
   <?php $__currentLoopData = $tipoLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
   <option value="<?php echo e($val); ?>" <?php echo e($tipo === $val ? 'selected' : ''); ?>><?php echo e($label); ?></option>
   <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   </select>
   <label class="font-semibold text-sm">Desde:</label>
   <input type="date" name="desde" value="<?php echo e($desde->toDateString()); ?>" class="glass-input w-36">
   <label class="font-semibold text-sm">Hasta:</label>
   <input type="date" name="hasta" value="<?php echo e($hasta->toDateString()); ?>" class="glass-input w-36">
   <button class="btn-primary py-2 px-4 text-sm" title="Filtrar">🔍 Filtrar</button>
   <div class="flex items-center gap-2 ml-auto">
        <button type="button" onclick="window.print()" class="btn-print text-sm" title="Imprimir Reporte">
        <span>🖨️</span> Imprimir
        </button>
        <button type="button" onclick="exportarOperaciones('excel', this)" class="btn-excel text-sm" title="Exportar a Excel">
        <span>📊</span> Excel
        </button>
        <button type="button" onclick="exportarOperaciones('pdf', this)" class="btn-pdf text-sm" title="Exportar a PDF">
        <span>📄</span> PDF
        </button>
    </div>
 </form>
</div>


<div class="glass-card p-6">
 <?php if($registros->isEmpty()): ?>
 <div class="flex flex-col items-center justify-center space-y-3 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm p-12 rounded-2xl border border-white/20 my-4">
     <div class="text-5xl opacity-80">📭</div>
     <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No se encontraron registros</h3>
     <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No se encontraron operaciones en este período.</p>
 </div>
 <?php else: ?>
  <div class="flex justify-between items-center mb-4">
     <div>
         <h3 class="text-lg font-bold"><?php echo e($tipoLabels[$tipo]); ?> <span class="text-sm font-normal text-gray-500">(<?php echo e($registros->total()); ?> registros)</span></h3>
         <div class="print-date hidden-screen text-xs text-gray-500 font-semibold mt-0.5"><strong>Fecha Impresión:</strong> <?php echo e(\Carbon\Carbon::now()->format('d/m/Y h:i A')); ?></div>
     </div>
  </div>

 
 <?php if($tipo === 'solo_mantenimientos'): ?>
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm text-center">
 <thead>
 <tr>
 <th class="p-2 text-center">Orden</th><th class="p-2 text-left">Equipo / Cliente</th>
 <th class="p-2 text-center">Técnico</th><th class="p-2 text-center">Entrada</th><th class="p-2 text-center">Salida</th>
 <th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th><th class="p-2 text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 <?php $__currentLoopData = $registros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <?php
   $isAnulado = !empty($m->anulado);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 ?>
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="p-2 font-mono font-bold whitespace-nowrap <?php echo e($dim); ?>">
 <?php echo e($m->id_orden); ?>

 </td>
 <td class="p-2 text-left <?php echo e($dim); ?>">
 <span class="font-bold text-slate-800 dark:text-white"><?php echo e($m->equipo->nombre ?? '—'); ?></span>
 <span class="text-xs text-gray-500 font-semibold">(<?php echo e($m->equipo->cliente->nombre ?? '—'); ?>)</span>
 </td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($m->tecnico->nombre ?? '—'); ?></td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($m->fecha_entrada->format('d/m/Y')); ?></td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($m->fecha_salida ? $m->fecha_salida->format('d/m/Y') : 'Pendiente'); ?></td>
 <td class="p-2 <?php echo e($dimLight); ?>"><span class="pill pill-efectivo <?php echo e($isAnulado ? 'opacity-70' : ''); ?>"><?php echo e(ucfirst($m->estado)); ?></span></td>
 <td class="p-2">
 <span class="pill <?php echo e($isAnulado ? 'pill-anulado' : 'pill-done'); ?>">
 <?php echo e($isAnulado ? 'Anulado' : 'Activo'); ?>

 </span>
 </td>
 <td class="p-2 text-center font-bold text-gray-900 dark:text-gray-100 <?php echo e($dim); ?>" <?php echo $isAnulado ? 'style="color: #dd6b20 !important;"' : ''; ?>>$<?php echo e(number_format($m->costo, 0, ',', '.')); ?></td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </tbody>
  <tfoot>
    <tr class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
        <td class="text-center font-bold text-xs whitespace-nowrap">TOTAL: <?php echo e($registros->count()); ?></td>
        <td colspan="6" class="text-right uppercase text-xs">Total Costos Mantenimientos:</td>
        <td class="text-center font-bold text-xs">$<?php echo e(number_format($registros->where('anulado', 0)->sum('costo'), 0, ',', '.')); ?></td>
    </tr>
 </tfoot>
 </table>
 </div>
 
 
 <?php elseif($tipo === 'solo_electronica'): ?>
 <div class="overflow-x-auto pb-2">
 <table class="ts-table table-electronica responsive-table w-full text-sm text-center">
 <thead>
 <tr>
 <th class="p-2 text-center">Orden</th><th class="p-2 text-left">Dispositivo / Cliente</th>
 <th class="p-2 text-center">Técnico</th><th class="p-2 text-center">Entrada</th><th class="p-2 text-center">Salida</th>
 <th class="p-2 text-center">Progreso</th><th class="p-2 text-center">Estado</th><th class="p-2 text-center">Costo</th>
 </tr>
 </thead>
 <tbody>
 <?php $__currentLoopData = $registros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <?php
   $isAnulado = !empty($e->anulado);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 ?>
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="p-2 font-mono font-bold whitespace-nowrap <?php echo e($dim); ?>">
 <?php echo e($e->id_orden); ?>

 </td>
 <td class="p-2 text-left <?php echo e($dim); ?>">
 <span class="font-bold text-slate-800 dark:text-white"><?php echo e($e->equipo->nombre ?? '—'); ?></span>
 <span class="text-xs text-gray-500 font-semibold">(<?php echo e($e->equipo->cliente->nombre ?? '—'); ?>)</span>
 </td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($e->tecnico->nombre ?? '—'); ?></td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($e->fecha_entrada->format('d/m/Y')); ?></td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($e->fecha_salida ? $e->fecha_salida->format('d/m/Y') : 'Pendiente'); ?></td>
 <td class="p-2 <?php echo e($dimLight); ?>"><span class="pill pill-pending <?php echo e($isAnulado ? 'opacity-70' : ''); ?>"><?php echo e(ucfirst($e->estado)); ?></span></td>
 <td class="p-2">
 <span class="pill <?php echo e($isAnulado ? 'pill-anulado' : 'pill-done'); ?>">
 <?php echo e($isAnulado ? 'Anulado' : 'Activo'); ?>

 </span>
 </td>
 <td class="p-2 text-center font-bold text-gray-900 dark:text-gray-100 <?php echo e($dim); ?>" <?php echo $isAnulado ? 'style="color: #dd6b20 !important;"' : ''; ?>>$<?php echo e(number_format($e->costo, 0, ',', '.')); ?></td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </tbody>
  <tfoot>
    <tr class="bg-gray-100/50 dark:bg-gray-800/50 font-bold text-center">
        <td class="text-center font-bold text-xs whitespace-nowrap">TOTAL: <?php echo e($registros->count()); ?></td>
        <td colspan="6" class="text-right uppercase text-xs">Total Costos Electrónica:</td>
        <td class="text-center font-bold text-xs">$<?php echo e(number_format($registros->where('anulado', 0)->sum('costo'), 0, ',', '.')); ?></td>
    </tr>
 </tfoot>
 </table>
 </div>
 
 
 <?php elseif(in_array($tipo, ['solo_ingresos', 'solo_egresos'])): ?>
 <div class="overflow-x-auto pb-2">
 <table class="ts-table responsive-table w-full text-sm text-center">
 <thead>
 <tr>
 <th class="p-2 text-center">Código</th>
 <th class="p-2 text-center">Fecha</th>
 <th class="p-2 text-left">Persona / Empresa</th>
 <th class="p-2 text-center">Concepto</th>
 <th class="p-2 text-center">Tipo Pago</th>
 <th class="p-2 text-center">Progreso</th>
 <th class="p-2 text-center">Estado</th>
 <th class="p-2 text-center">Monto</th>
 </tr>
 </thead>
 <tbody>
 <?php $__currentLoopData = $registros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <?php
   $isAnulado = !empty($c->anulado);
   $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
   $dimLight = $isAnulado ? 'opacity-60' : '';
 ?>
 <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
 <td class="p-2 font-bold <?php echo e($dim); ?>"><?php echo e($c->id); ?></td>
 <td class="p-2 <?php echo e($dim); ?>"><?php echo e($c->fecha->format('d/m/Y')); ?></td>
 <td class="p-2 text-left <?php echo e($dim); ?>"><?php echo e($c->persona ?? $c->empresa ?? '—'); ?></td>
 <td class="p-2 text-xs <?php echo e($dim); ?>"><?php echo e($c->concepto->nombre ?? '—'); ?></td>
 <td class="p-2 text-xs capitalize <?php echo e($dim); ?>"><?php echo e($c->tipo_pago); ?></td>
 <td class="p-2 <?php echo e($dimLight); ?>"><span class="pill pill-especialidad <?php echo e($isAnulado ? 'opacity-70' : ''); ?>">Procesado</span></td>
 <td class="p-2">
 <span class="pill <?php echo e($isAnulado ? 'pill-anulado' : 'pill-done'); ?>">
 <?php echo e($isAnulado ? 'Anulado' : 'Activo'); ?>

 </span>
 </td>
 <td class="p-2 text-center font-bold text-gray-900 dark:text-gray-100 <?php echo e($dim); ?>" <?php echo $isAnulado ? 'style="color: #dd6b20 !important;"' : ''; ?>>$<?php echo e(number_format($c->monto, 0, ',', '.')); ?></td>
 </tr>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </tbody>
 <tfoot>
    <tr class="bg-gray-100 dark:bg-gray-800">
        <td class="text-center font-bold text-xs whitespace-nowrap">TOTAL: <?php echo e($registros->count()); ?></td>
        <td colspan="6" class="text-right uppercase text-xs">TOTAL MONTO:</td>
        <td class="text-center font-bold text-xs">$<?php echo e(number_format($registros->where('anulado', 0)->sum('monto'), 0, ',', '.')); ?></td>
    </tr>
 </tfoot>
 </table>
 </div>
 
 
 <?php else: ?>
  <div class="overflow-x-auto pb-2">
  <table class="ts-table responsive-table w-full text-sm text-center">
  <thead>
  <tr>
  <th class="p-2 text-center">Factura Nº</th>
  <th class="p-2 text-center">Fecha</th>
  <th class="p-2 text-left">Persona / Empresa</th>
  <th class="p-2 text-center">Pagado</th>
 <th class="p-2 text-center">Estado</th>
 <th class="p-2 text-center">Total</th>
  </tr>
  </thead>
  <tbody>
  <?php $__currentLoopData = $registros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <?php
    $isAnulado = $f->estado === 'anulada';
    $dim = $isAnulado ? 'opacity-60 grayscale text-gray-400 dark:text-gray-500' : '';
    $stClass = 'pill-pending';
    if($f->estado === 'emitida') $stClass = 'pill-done';
    if($f->estado === 'anulada') $stClass = 'pill-anulado';
    
    $label = ucfirst(str_replace('_', ' ', $f->estado));
    if($f->estado === 'pendiente_pago') $label = 'Pendiente';
  ?>
  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
  <td class="p-2 font-mono font-bold <?php echo e($dim); ?>"><?php echo e($f->numero_factura); ?></td>
  <td class="p-2 <?php echo e($dim); ?>"><?php echo e($f->fecha->format('d/m/Y')); ?></td>
  <td class="p-2 text-left <?php echo e($dim); ?>"><?php echo e($f->facturable->nombre ?? $f->facturable->nombre_razon_social ?? '—'); ?></td>
  <td class="p-2 font-semibold text-gray-900 dark:text-gray-100 <?php echo e($dim); ?>">$<?php echo e(number_format($f->total_pagado, 0, ',', '.')); ?></td>
  <td class="p-2">
  <span class="pill <?php echo e($stClass); ?>">
  <?php echo e($label); ?>

  </span>
  </td>
 <td class="p-2 text-center font-bold text-gray-900 dark:text-gray-100 <?php echo e($dim); ?>" <?php echo $isAnulado ? 'style="color: #dd6b20 !important;"' : ''; ?>>$<?php echo e(number_format($f->total_documento, 0, ',', '.')); ?></td>
  </tr>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </tbody>
 <tfoot>
    <tr class="bg-gray-100 dark:bg-gray-800">
        <td class="text-center font-bold text-xs whitespace-nowrap">TOTAL: <?php echo e($registros->count()); ?></td>
        <td colspan="4" class="text-right uppercase text-xs font-bold">TOTAL INVENTARIO:</td>
        <td class="text-center font-bold text-xs">$<?php echo e(number_format($registros->filter(function($i) { return $i->estado !== 'anulada'; })->sum('total_documento'), 0, ',', '.')); ?></td>
    </tr>
 </tfoot>
 </table>
 </div>
 <div class="mt-6 flex justify-end">
  <?php echo e($registros->appends(request()->query())->links()); ?>

  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

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
        min-height: auto !important;
        height: auto !important;
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
        min-height: auto !important;
        height: auto !important;
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
        height: auto !important;
        min-height: auto !important;
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
    
    table, .ts-table,
    th, td, tfoot td,
    thead th:first-child, thead th:last-child,
    tbody tr:last-child td:first-child, tbody tr:last-child td:last-child,
    tfoot tr:last-child td:first-child, tfoot tr:last-child td:last-child {
        border-radius: 0 !important;
    }

    table, .ts-table {
        display: table !important;
        width: 100% !important;
        border-collapse: collapse !important;
        margin-top: 4px !important;
        margin-bottom: 15px !important;
        font-size: 8.5pt !important;
        background-color: #ffffff !important;
        background: #ffffff !important;
        box-shadow: none !important;
        filter: none !important;
    }
    
    thead {
        display: table-header-group !important;
    }
    
    tbody {
        display: table-row-group !important;
    }
    
    tfoot, .tfoot {
        display: table-footer-group !important;
        font-weight: bold !important;
    }
    
    tr {
        display: table-row !important;
        page-break-inside: avoid !important;
    }
    
    table th, .ts-table th, table td, .ts-table td, tfoot td, .tfoot td {
        display: table-cell !important;
        border: none !important;
        padding: 7px 10px !important;
        vertical-align: middle !important;
    }
    
    table tbody td, .ts-table tbody td {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    
    table th, .ts-table th, table thead th {
        background-color: #2d3748 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        text-transform: uppercase !important;
        font-size: 8pt !important;
    }
    
    table tbody tr:nth-child(even) td, .ts-table tbody tr:nth-child(even) td {
        background-color: #f7fafc !important;
    }
    
    table tfoot td, .ts-table tfoot td, table .tfoot td, .ts-table .tfoot td {
        background-color: #2d3748 !important;
        color: #ffffff !important;
        font-weight: bold !important;
        font-size: 8pt !important;
    }
    
    tfoot td *, .tfoot td *, tfoot td span, .tfoot td span, tfoot td div, .tfoot td div, tfoot td strong, .tfoot td strong {
        display: inline !important;
        border: none !important;
        background: transparent !important;
        background-color: transparent !important;
        color: #ffffff !important;
        font-size: inherit !important;
        box-shadow: none !important;
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
    
    .no-print-emoji, table td span.no-print-emoji, .grid p span, span.text-lg {
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
        margin-bottom: 4px !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding-bottom: 3px !important;
    }

    .flex, .flex-col, .flex-wrap, .items-center, .justify-between {
        display: block !important;
    }
    .mb-4 {
        margin-bottom: 4px !important;
    }
    .overflow-x-auto {
        overflow: visible !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .no-print {
        display: none !important;
    }
    .print-date {
        display: block !important;
        font-size: 8pt !important;
        color: #4a5568 !important;
        margin-top: 2px !important;
        margin-bottom: 6px !important;
        font-weight: bold !important;
    }
    .glass-card {
        margin-top: 4px !important;
        margin-bottom: 4px !important;
    }
}
</style>
<?php $__env->stopSection(); ?>

<script>
function exportarOperaciones(tipo, btn) {
    const form = document.getElementById('filtros-operaciones');
    const params = new URLSearchParams(new FormData(form));
    params.set('export', tipo);
    const url = window.location.pathname + '?' + params.toString();
    const fallbackName = 'Reporte_Operaciones_' + new Date().toISOString().slice(0,10) + (tipo === 'pdf' ? '.pdf' : '.xlsx');
    
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


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/reportes_financieros/operaciones.blade.php ENDPATH**/ ?>