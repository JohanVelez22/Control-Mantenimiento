import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Make sidebar dark and replace logo with NAVEGACIÓN
c = re.sub(
    r'<aside id="ts-sidebar"[^>]+>',
    '<aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col shrink-0 relative bg-[#0B1121] border-r border-white/5 transition-all duration-300 z-30 shadow-[4px_0_24px_rgba(0,0,0,0.2)] w-[70px] expanded:w-[280px]">',
    c
)

logo_pattern = r'<!-- Brand / Logo -->.*?</a>'
nav_header = """<!-- Brand / Logo -->
            <div class="h-20 flex items-center justify-center expanded:justify-start border-b border-white/5 shrink-0 px-6 relative transition-all duration-300">
                <span class="text-[11px] font-black tracking-[0.2em] text-[#2563EB] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>
                <span class="text-[11px] font-black text-[#2563EB] uppercase group-[.expanded]:hidden">NAV</span>
            </div>"""
c = re.sub(logo_pattern, nav_header, c, flags=re.DOTALL)

# Rebuild the entire Nav block
nav_pattern = r'<!-- Navegación -->.*?</nav>'
new_nav = """<!-- Navegación -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <div class="nav-icon">📊</div>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" title="Clientes">
                    <div class="nav-icon">👤</div>
                    <span class="nav-label">Clientes</span>
                </a>
                <a href="{{ route('equipos.index') }}" class="nav-item {{ request()->routeIs('equipos.*') ? 'active' : '' }}" title="Equipos">
                    <div class="nav-icon">🖥️</div>
                    <span class="nav-label">Equipos</span>
                </a>
                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" title="Proveedores">
                    <div class="nav-icon">🏭</div>
                    <span class="nav-label">Proveedores</span>
                </a>
                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" title="Técnicos">
                    <div class="nav-icon">🛠️</div>
                    <span class="nav-label">Técnicos</span>
                </a>
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}" title="Control Stock">
                    <div class="nav-icon">📦</div>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Operaciones (C/V)">
                    <div class="nav-icon">📄</div>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') ? 'active' : '' }}" title="Mantenimientos">
                    <div class="nav-icon">⚙️</div>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') ? 'active' : '' }}" title="Electrónica">
                    <div class="nav-icon">⚡</div>
                    <span class="nav-label">Electrónica</span>
                </a>
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" title="Caja (Ing/Egr)">
                    <div class="nav-icon">💵</div>
                    <span class="nav-label">Caja (Ing/Egr)</span>
                </a>
                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" title="Arqueo / Cierre">
                    <div class="nav-icon">🔒</div>
                    <span class="nav-label">Arqueo / Cierre</span>
                </a>
                <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Info Operativos">
                    <div class="nav-icon">📈</div>
                    <span class="nav-label">Info Operativos</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <div class="nav-icon">🏢</div>
                    <span class="nav-label">Empresa</span>
                </a>
            </nav>"""
c = re.sub(nav_pattern, new_nav, c, flags=re.DOTALL)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
