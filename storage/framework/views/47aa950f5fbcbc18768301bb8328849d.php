<?php $__env->startSection('title', 'Detalles de Producto'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        
        
        <?php if(!$stock->active): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-center shadow-[0_4px_20px_rgba(239,68,68,0.15)]">
            <span class="text-2xl drop-shadow-md">🚫</span>
            <h3 class="font-black text-red-600 dark:text-red-400 mt-1 uppercase tracking-widest">Producto Anulado / Inactivo</h3>
            <p class="text-sm font-medium text-red-500/80 mt-1">Este producto no está disponible para nuevas operaciones.</p>
        </div>
        <?php endif; ?>

        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6">
            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('stocks.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
                <div>
                    <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                        <?php echo e($stock->producto); ?>

                        <span class="pill <?php echo e($stock->active ? 'pill-done' : 'pill-anulado'); ?> text-sm py-1 px-3">
                            <?php echo e($stock->active ? 'ACTIVO' : 'INACTIVO'); ?>

                        </span>
                    </h2>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-2">
                        Código: <span class="font-mono text-indigo-500"><?php echo e($stock->codigo ?? 'N/A'); ?></span> | 
                        Categoría: <?php echo e($stock->categoria ?? 'General'); ?> <?php echo e($stock->subcategoria ? ' / ' . $stock->subcategoria : ''); ?>

                    </p>
                </div>
            </div>
            
            <div class="flex items-center gap-3 shrink-0">
                <a href="<?php echo e(route('stocks.print', $stock->id)); ?>" target="_blank" class="btn-ghost border-blue-500/20 text-blue-600">
                    🖨️ Imprimir
                </a>
                
                <?php if(!auth()->user()->isInvitado()): ?>
                <a href="<?php echo e(route('stocks.edit', $stock->id)); ?>" class="btn-ghost border-yellow-500/20 text-yellow-600">
                    ✏️ Editar
                </a>
                <button type="button" onclick="openAnularModal('<?php echo e(route('stocks.anular', $stock->id)); ?>', <?php echo e(!$stock->active ? 'true' : 'false'); ?>)" class="btn-danger">
                    <?php echo e($stock->active ? '🚫 Anular' : '✅ Reactivar'); ?>

                </button>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="p-5 rounded-2xl bg-white/40 dark:bg-slate-800/40 border border-gray-200/50 dark:border-white/5 backdrop-blur-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-xl shadow-lg shrink-0">
                    🏭
                </div>
                <div>
                    <p class="text-[10px] font-black text-indigo-500 tracking-widest uppercase mb-1">Proveedor Predeterminado</p>
                    <p class="font-black text-xl text-slate-800 dark:text-white leading-tight">
                        <?php echo e($proveedor->nombre_razon_social ?? '—'); ?>

                    </p>
                    <p class="text-xs font-semibold text-gray-500 mt-1">ID: <?php echo e($proveedor->identificacion ?? 'N/A'); ?></p>
                </div>
            </div>
            
            <div class="p-5 rounded-2xl bg-white/40 dark:bg-slate-800/40 border border-gray-200/50 dark:border-white/5 backdrop-blur-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-blue-500 tracking-widest uppercase mb-1">Existencia Actual</p>
                    <p class="font-black text-4xl text-slate-800 dark:text-white leading-tight">
                        <?php echo e($stock->cantidad); ?> <span class="text-sm text-gray-500 font-bold">Unidades</span>
                    </p>
                </div>
                <div class="text-5xl opacity-80 drop-shadow-md">📦</div>
            </div>
        </div>

        
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Estructura de Precios</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="glass-card p-4 text-center">
                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Costo de Compra</p>
                <p class="text-xl font-black text-slate-800 dark:text-white">$<?php echo e(number_format($stock->precio_compra, 0, ',', '.')); ?></p>
            </div>
            <div class="glass-card p-4 text-center">
                <p class="text-[10px] font-bold text-purple-500 uppercase tracking-widest mb-1">Precio a Técnico</p>
                <p class="text-xl font-black text-purple-600 dark:text-purple-400">$<?php echo e(number_format($stock->precio_tecnico, 0, ',', '.')); ?></p>
            </div>
            <div class="glass-card p-4 text-center">
                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Precio Público Venta</p>
                <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">$<?php echo e(number_format($stock->precio_venta, 0, ',', '.')); ?></p>
                <?php
                    $utilidadPesos = $stock->precio_venta - $stock->precio_compra;
                    $utilidadPct = $stock->utilidad ?? 0;
                ?>
                <p class="text-xs font-bold text-emerald-500 mt-1" title="Margen: <?php echo e($utilidadPct); ?>%">+$<?php echo e(number_format($utilidadPesos, 0, ',', '.')); ?></p>
            </div>
        </div>

        
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Historial de Operaciones (Compras y Ventas)</h3>
        <div class="overflow-x-auto overflow-y-auto max-h-[400px] relative mb-6">
            <table class="ts-table mb-0">
                <thead class="sticky top-0 z-20 shadow-sm">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Factura</th>
                        <th>Entidad</th>
                        <th class="text-center w-20">Cant.</th>
                        <th class="text-right">Precio U.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $historial; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 <?php echo e($item->factura->estado === 'anulada' ? 'opacity-50 grayscale' : ''); ?>">
                        <td class="text-sm font-medium"><?php echo e(\Carbon\Carbon::parse($item->factura->fecha ?? $item->created_at)->format('d/m/Y')); ?></td>
                        <td>
                            <span class="pill <?php echo e($item->factura->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done'); ?> text-xs">
                                <?php echo e($item->factura->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta'); ?>

                            </span>
                        </td>
                        <td class="font-bold">
                            <a href="<?php echo e(route('inventario.facturas.show', $item->factura_id)); ?>" class="text-blue-600 hover:underline">
                                <?php echo e($item->factura->numero_factura); ?>

                            </a>
                        </td>
                        <td class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            <?php echo e($item->factura->facturable->nombre_razon_social ?? $item->factura->facturable->nombre ?? '—'); ?>

                        </td>
                        <td class="text-center font-bold"><?php echo e($item->cantidad); ?></td>
                        <td class="text-right font-mono">$<?php echo e(number_format($item->precio_unitario, 0, ',', '.')); ?></td>
                        <td class="text-right font-black <?php echo e($item->factura->tipo_movimiento === 'compra' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'); ?>">
                            <?php echo e($item->factura->tipo_movimiento === 'compra' ? '-' : '+'); ?>$<?php echo e(number_format($item->subtotal, 0, ',', '.')); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="text-center p-8 text-gray-500 font-medium">
                            No hay compras ni ventas registradas para este producto.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
            <span>Última actualización: <?php echo e($stock->updated_at->format('d/m/Y H:i:s')); ?></span>
            <span>Registro inicial: <?php echo e($stock->created_at->format('d/m/Y H:i:s')); ?></span>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/stocks/show.blade.php ENDPATH**/ ?>