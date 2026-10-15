<div class="flex gap-4 mb-6 no-print overflow-x-auto pb-2">
    <a href="{{ route('reportes.financiero.diario') }}"
       class="px-5 py-2.5 rounded-xl font-bold transition-all whitespace-nowrap
       {{ request()->routeIs('reportes.*')
           ? 'border-b-[3px] border-amber-600 dark:border-amber-500 text-amber-700 dark:text-amber-300 bg-amber-50/60 dark:bg-amber-900/20'
           : 'border-b-[3px] border-transparent bg-amber-500/10 text-amber-700 dark:text-amber-300 hover:bg-amber-500/20' }}">
        💵 Informes Financieros
    </a>

    <a href="{{ route('mantenimientos.reportes') }}"
       class="px-5 py-2.5 rounded-xl font-bold transition-all whitespace-nowrap
       {{ request()->routeIs('mantenimientos.reportes')
           ? 'border-b-[3px] border-blue-600 dark:border-blue-500 text-blue-700 dark:text-blue-300 bg-blue-50/60 dark:bg-blue-900/20'
           : 'border-b-[3px] border-transparent bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20' }}">
        ⚙️ Reporte de Mantenimientos
    </a>

    <a href="{{ route('electronicas.reportes') }}"
       class="px-5 py-2.5 rounded-xl font-bold transition-all whitespace-nowrap
       {{ request()->routeIs('electronicas.reportes')
           ? 'border-b-[3px] border-purple-600 dark:border-purple-500 text-purple-700 dark:text-purple-300 bg-purple-50/60 dark:bg-purple-900/20'
           : 'border-b-[3px] border-transparent bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20' }}">
        ⚡ Reporte de Electrónica
    </a>

    <a href="{{ route('stocks.reportes') }}"
       class="px-5 py-2.5 rounded-xl font-bold transition-all whitespace-nowrap
       {{ request()->routeIs('stocks.reportes')
           ? 'border-b-[3px] border-orange-600 dark:border-orange-500 text-orange-700 dark:text-orange-300 bg-orange-50/60 dark:bg-orange-900/20'
           : 'border-b-[3px] border-transparent bg-orange-500/10 text-orange-700 dark:text-orange-300 hover:bg-orange-500/20' }}">
        📦 Informe Inventario
    </a>
</div>
