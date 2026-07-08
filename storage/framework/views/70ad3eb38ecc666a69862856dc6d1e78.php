<?php if($paginator->hasPages()): ?>
    <nav role="navigation" aria-label="<?php echo e(__('Pagination Navigation')); ?>" class="-mt-2 relative z-10" aria-label="<?php echo e(__('Pagination Navigation')); ?>" class="-mt-2 relative z-10" aria-label="<?php echo e(__('Pagination Navigation')); ?>" class="-mt-2 relative z-10">

        
        <div class="flex gap-2 items-center justify-between sm:hidden">
            <?php if($paginator->onFirstPage()): ?>
                <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-400 bg-white/30 border border-white/40 backdrop-blur-md cursor-not-allowed leading-5 rounded-xl dark:text-gray-500 dark:bg-slate-800/30 dark:border-white/5">
                    <?php echo __('pagination.previous'); ?>

                </span>
            <?php else: ?>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-700 bg-white/60 border border-white/60 backdrop-blur-md leading-5 rounded-xl hover:bg-white/80 hover:text-blue-600 transition ease-in-out duration-200 dark:bg-slate-800/60 dark:border-white/10 dark:text-gray-200 dark:hover:bg-slate-700/80 dark:hover:text-blue-400">
                    <?php echo __('pagination.previous'); ?>

                </a>
            <?php endif; ?>

            <?php if($paginator->hasMorePages()): ?>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-700 bg-white/60 border border-white/60 backdrop-blur-md leading-5 rounded-xl hover:bg-white/80 hover:text-blue-600 transition ease-in-out duration-200 dark:bg-slate-800/60 dark:border-white/10 dark:text-gray-200 dark:hover:bg-slate-700/80 dark:hover:text-blue-400">
                    <?php echo __('pagination.next'); ?>

                </a>
            <?php else: ?>
                <span class="inline-flex items-center px-4 py-2 text-sm font-bold text-gray-400 bg-white/30 border border-white/40 backdrop-blur-md cursor-not-allowed leading-5 rounded-xl dark:text-gray-500 dark:bg-slate-800/30 dark:border-white/5">
                    <?php echo __('pagination.next'); ?>

                </span>
            <?php endif; ?>
        </div>

        
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between bg-white/40 dark:bg-slate-800/40 backdrop-blur-xl border border-white/50 dark:border-white/10 p-2 px-4 rounded-2xl shadow-lg">

            
            <div>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    <?php echo __('Showing'); ?>

                    <?php if($paginator->firstItem()): ?>
                        <span class="font-bold text-blue-600 dark:text-blue-400"><?php echo e($paginator->firstItem()); ?></span>
                        <?php echo __('to'); ?>

                        <span class="font-bold text-blue-600 dark:text-blue-400"><?php echo e($paginator->lastItem()); ?></span>
                    <?php else: ?>
                        <?php echo e($paginator->count()); ?>

                    <?php endif; ?>
                    <?php echo __('of'); ?>

                    <span class="font-bold text-slate-800 dark:text-white"><?php echo e($paginator->total()); ?></span>
                    <?php echo __('results'); ?>

                </p>
            </div>

            
            <div>
                <span class="inline-flex items-center gap-1">

                    
                    <?php if($paginator->onFirstPage()): ?>
                        <span aria-disabled="true" aria-label="<?php echo e(__('pagination.previous')); ?>">
                            <span class="inline-flex items-center justify-center w-9 h-9 text-gray-400 bg-transparent cursor-not-allowed rounded-lg dark:text-gray-600" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    <?php else: ?>
                        <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-white/50 border border-white/60 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-md transition-all duration-200 dark:text-slate-300 dark:bg-slate-700/50 dark:border-white/10 dark:hover:bg-slate-700 dark:hover:text-blue-400" aria-label="<?php echo e(__('pagination.previous')); ?>">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php endif; ?>

                    
                    <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        
                        <?php if(is_string($element)): ?>
                            <span aria-disabled="true">
                                <span class="inline-flex items-center justify-center w-9 h-9 text-sm font-bold text-slate-400 dark:text-slate-500 cursor-default"><?php echo e($element); ?></span>
                            </span>
                        <?php endif; ?>

                        
                        <?php if(is_array($element)): ?>
                            <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($page == $paginator->currentPage()): ?>
                                    <span aria-current="page">
                                        <span class="inline-flex items-center justify-center w-9 h-9 text-sm font-black text-white bg-gradient-to-br from-blue-600 to-indigo-600 rounded-lg shadow-lg shadow-blue-500/30 dark:shadow-blue-500/20 cursor-default transform scale-110 mx-1"><?php echo e($page); ?></span>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo e($url); ?>" class="inline-flex items-center justify-center w-9 h-9 text-sm font-bold text-slate-600 bg-white/50 border border-white/60 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-md transition-all duration-200 dark:text-slate-300 dark:bg-slate-700/50 dark:border-white/10 dark:hover:bg-slate-700 dark:hover:text-blue-400" aria-label="<?php echo e(__('Go to page :page', ['page' => $page])); ?>">
                                        <?php echo e($page); ?>

                                    </a>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    
                    <?php if($paginator->hasMorePages()): ?>
                        <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" class="inline-flex items-center justify-center w-9 h-9 text-slate-600 bg-white/50 border border-white/60 rounded-lg hover:bg-white hover:text-blue-600 hover:shadow-md transition-all duration-200 dark:text-slate-300 dark:bg-slate-700/50 dark:border-white/10 dark:hover:bg-slate-700 dark:hover:text-blue-400" aria-label="<?php echo e(__('pagination.next')); ?>">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    <?php else: ?>
                        <span aria-disabled="true" aria-label="<?php echo e(__('pagination.next')); ?>">
                            <span class="inline-flex items-center justify-center w-9 h-9 text-gray-400 bg-transparent cursor-not-allowed rounded-lg dark:text-gray-600" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </nav>
<?php endif; ?>
<?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/vendor/pagination/tailwind.blade.php ENDPATH**/ ?>