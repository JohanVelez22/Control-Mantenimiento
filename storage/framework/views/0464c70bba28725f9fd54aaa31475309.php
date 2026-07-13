<?php $__env->startSection('content'); ?>

<?php $__env->startPush('modals'); ?>

<div id="pwd-delete-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300 z-50">
    <div class="ts-modal-card scale-95 opacity-0" id="pwd-delete-card">
        <div class="p-6">
            <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center text-3xl mx-auto mb-4">
                🔓
            </div>
            <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Eliminar Registro</h3>
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                Ingresa tu contraseña o la del administrador para confirmar la eliminación.
            </p>
            <form id="delete-form" method="POST" class="space-y-4">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <div>
                    <input type="password" name="password_confirm" id="pwd-delete-input" required placeholder="Contraseña..." class="glass-input text-center tracking-widest text-lg">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeDeletePwd()" class="flex-1 btn-ghost justify-center">Cancelar</button>
                    <button type="submit" class="flex-1 btn-danger justify-center font-bold">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openDeletePwd(url) {
        const modal = document.getElementById('pwd-delete-modal');
        const card = document.getElementById('pwd-delete-card');
        document.getElementById('delete-form').action = url;
        document.getElementById('pwd-delete-input').value = '';
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
            document.getElementById('pwd-delete-input').focus();
        }, 10);
    }
    
    function closeDeletePwd() {
        const modal = document.getElementById('pwd-delete-modal');
        const card = document.getElementById('pwd-delete-card');
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    document.addEventListener('keydown', e => { 
        if (e.key === 'Escape') {
            closeDeletePwd(); 
        }
    });
</script>
<?php $__env->stopPush(); ?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <a href="<?php echo e(route('stocks.index')); ?>" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    🏷️ Categorías de Inventario
                </h2>
                <p class="text-gray-500 font-medium mt-1">Gestiona las categorías y subcategorías de los productos del stock.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <form action="<?php echo e(route('stocks.categorias.store')); ?>" method="POST" class="glass-card p-6">
                <?php echo csrf_field(); ?>
                <h3 class="font-bold text-slate-800 dark:text-white mb-4">Nueva Clasificación</h3>
                <div class="space-y-4">
                    <div>
                        <label class="field-label">Tipo de Clasificación</label>
                        <select name="tipo" class="glass-input no-search font-bold" required>
                            <option value="categoria">🗂️ Categoría Principal</option>
                            <option value="subcategoria">📂 Subcategoría</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Nombre</label>
                        <input type="text" name="nombre" required class="glass-input" placeholder="Ej: Pantallas, Accesorios...">
                    </div>
                    <button type="submit" class="btn-primary w-full justify-center">Crear Clasificación</button>
                </div>
            </form>
        </div>

        <div class="md:col-span-2">
            <div class="glass-card p-6">
                <h3 class="font-bold text-slate-800 dark:text-white mb-4">Clasificaciones Existentes</h3>
                
                <ul class="space-y-3">
                    <?php $__empty_1 = true; $__currentLoopData = $categorias; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 bg-white/50 dark:bg-slate-800/50 rounded-xl border border-gray-200/50 dark:border-white/5 gap-2">
                        <form action="<?php echo e(route('stocks.categorias.update', $c->id)); ?>" method="POST" class="flex-1 w-full flex flex-wrap sm:flex-nowrap gap-2 items-center">
                            <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                            <select name="tipo" class="glass-input py-1.5 px-2 text-xs w-auto no-search font-bold <?php echo e($c->tipo == 'categoria' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400'); ?>">
                                <option value="categoria" <?php echo e($c->tipo == 'categoria' ? 'selected' : ''); ?>>🗂️ Categoría</option>
                                <option value="subcategoria" <?php echo e($c->tipo == 'subcategoria' ? 'selected' : ''); ?>>📂 Subcat</option>
                            </select>
                            <input type="text" name="nombre" value="<?php echo e($c->nombre); ?>" required class="glass-input flex-1 py-1.5 px-3 text-sm min-w-[120px]">
                            <button type="submit" class="btn-ghost px-3 py-1.5 text-xs text-blue-600 border-blue-500/20 hover:bg-blue-500/10">💾</button>
                        </form>
                        <div class="ml-auto sm:ml-0">
                            <button type="button" onclick="openDeletePwd('<?php echo e(route('stocks.categorias.destroy', $c->id)); ?>')" class="btn-danger px-3 py-1.5 text-xs">🗑️</button>
                        </div>
                    </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="p-4 text-center text-gray-500">No hay clasificaciones registradas.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/stocks/categorias/index.blade.php ENDPATH**/ ?>