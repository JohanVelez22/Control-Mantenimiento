<div class="glass-card p-4 mb-6 reportes-nav-tabs flex flex-wrap items-center gap-2 no-print">
    <a href="{{ route('reportes.financiero.diario') }}"
       class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap
       {{ request()->routeIs('reportes.financiero.diario')
           ? 'border-b-[3px] border-blue-600 dark:border-blue-500 text-blue-700 dark:text-blue-300 bg-blue-50/60 dark:bg-blue-900/20'
           : 'border-b-[3px] border-transparent bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20' }}">
        📅 Diario
    </a>

    <a href="{{ route('reportes.financiero.acumulado') }}"
       class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap
       {{ request()->routeIs('reportes.financiero.acumulado') || request()->routeIs('reportes.index')
           ? 'border-b-[3px] border-purple-600 dark:border-purple-500 text-purple-700 dark:text-purple-300 bg-purple-50/60 dark:bg-purple-900/20'
           : 'border-b-[3px] border-transparent bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20' }}">
        📈 Acumulado
    </a>

    <a href="{{ route('reportes.financiero.operaciones') }}"
       class="px-5 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap
       {{ request()->routeIs('reportes.financiero.operaciones')
           ? 'border-b-[3px] border-teal-600 dark:border-teal-500 text-teal-700 dark:text-teal-300 bg-teal-50/60 dark:bg-teal-900/20'
           : 'border-b-[3px] border-transparent bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20' }}">
        📋 Operaciones
    </a>
</div>
