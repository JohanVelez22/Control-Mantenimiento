<?php $__env->startSection('content'); ?>


<div class="absolute top-5 right-5 z-10">
 <button id="theme-toggle-login"
 class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg"
 title="Cambiar tema" aria-label="Cambiar tema">
    <span class="dark:hidden">☀️</span>
    <span class="hidden dark:inline">🌙</span>
 </button>
</div>

<div class="w-full max-w-lg px-6 pb-16">

 
 <div class="flex justify-center mb-8">
     <div class="text-[24px] font-black tracking-widest font-logo flex items-center gap-2">
         <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
         <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
     </div>
 </div>

 
 <div class="text-center mb-8">
 <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-2xl shadow-xl mb-4">🔧</div>
 <h1 class="text-3xl font-black text-gray-900 dark:text-white">Iniciar Sesión</h1>
 <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tus credenciales para acceder al sistema</p>
 </div>

 
 <div class="glass-card p-8 md:p-10">

 
 <?php if($errors->any()): ?>
 <div class="mb-5 p-3.5 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm space-y-1">
 <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
 <p class="flex items-center gap-2"><span>⚠️</span> <?php echo e($error); ?></p>
 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </div>
 <?php endif; ?>

 <form method="POST" action="<?php echo e(route('login')); ?>" class="space-y-5">
 <?php echo csrf_field(); ?>

 
 <div>
 <label for="email" class="field-label mb-1.5 flex items-center gap-2">
 <span>📧</span> Correo Electrónico
 </label>
 <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
 required autofocus placeholder="usuario@empresa.com"
 class="glass-input mt-1">
 <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 
 <div>
 <label for="password" class="field-label mb-1.5 flex items-center gap-2">
 <span>🔑</span> Contraseña
 </label>
 <input type="password" id="password" name="password"
 required placeholder="••••••••"
 class="glass-input mt-1">
 <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs font-bold mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
 </div>

 
 <button type="submit" class="w-full btn-primary py-3 justify-center text-lg mt-4 ">
 Entrar al Sistema →
  </button>
  </form>

  </div>
</div>

<script>
(function(){
 if (localStorage.getItem('color-theme') === 'dark' ||
 (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
 document.documentElement.classList.add('dark');
 } else {
 document.documentElement.classList.remove('dark');
 }
 var btn = document.getElementById('theme-toggle-login');
 if (btn) {
 btn.addEventListener('click', function() {
 if (document.documentElement.classList.contains('dark')) {
 document.documentElement.classList.remove('dark');
 localStorage.setItem('color-theme', 'light');
 } else {
 document.documentElement.classList.add('dark');
 localStorage.setItem('color-theme', 'dark');
 }
 });
 }
})();
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\ServBay\www\control-mantenimiento-equipos\resources\views/auth/login.blade.php ENDPATH**/ ?>