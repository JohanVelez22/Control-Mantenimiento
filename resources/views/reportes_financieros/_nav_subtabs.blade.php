<div class="glass-card p-3 mb-6 reportes-nav-tabs flex flex-wrap items-center gap-2.5 no-print">
    <a href="{{ route('reportes.financiero.diario') }}"
       class="ts-subtab tab-blue {{ request()->routeIs('reportes.financiero.diario') ? 'active' : '' }}">
        <span>📅</span> <span>Diario</span>
    </a>
    <a href="{{ route('reportes.financiero.acumulado') }}"
       class="ts-subtab tab-purple {{ request()->routeIs('reportes.financiero.acumulado') || request()->routeIs('reportes.index') ? 'active' : '' }}">
        <span>📈</span> <span>Acumulado</span>
    </a>
    <a href="{{ route('reportes.financiero.operaciones') }}"
       class="ts-subtab tab-teal {{ request()->routeIs('reportes.financiero.operaciones') ? 'active' : '' }}">
        <span>📋</span> <span>Operaciones</span>
    </a>
</div>
