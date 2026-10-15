<div class="ts-reportes-main-nav no-print">
    <a href="{{ route('reportes.financiero.diario') }}" class="ts-reporte-nav-card tab-amber {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
        <span>💵</span>
        <span>Informes Financieros</span>
    </a>
    <a href="{{ route('mantenimientos.reportes') }}" class="ts-reporte-nav-card tab-blue {{ request()->routeIs('mantenimientos.reportes') ? 'active' : '' }}">
        <span>⚙️</span>
        <span>Reporte de Mantenimientos</span>
    </a>
    <a href="{{ route('electronicas.reportes') }}" class="ts-reporte-nav-card tab-purple {{ request()->routeIs('electronicas.reportes') ? 'active' : '' }}">
        <span>⚡</span>
        <span>Reporte de Electrónica</span>
    </a>
    <a href="{{ route('stocks.reportes') }}" class="ts-reporte-nav-card tab-orange {{ request()->routeIs('stocks.reportes') ? 'active' : '' }}">
        <span>📦</span>
        <span>Informe Inventario</span>
    </a>
</div>
