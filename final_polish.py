import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Update fonts: add Michroma and Orbitron, use Michroma for logos
fonts_html = """<link rel="stylesheet" href="{{ asset('css/glass.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Michroma&family=Orbitron:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .font-logo { font-family: 'Michroma', sans-serif; }
    </style>"""
c = re.sub(r'<link rel="stylesheet" href="\{\{ asset\(\'css/glass\.css\'\) \}\}">.*?</style>', fonts_html, c, flags=re.DOTALL)
if 'fonts.googleapis.com' not in c:
    # If the replace failed, inject it
    c = c.replace("""<link rel="stylesheet" href="{{ asset('css/glass.css') }}">""", fonts_html)

# 2. Update Admin layout (md:flex flex-col instead of md:block)
admin_block = """<div class="hidden md:flex flex-col text-left ml-2">
                            <span class="text-sm font-bold text-slate-800 dark:text-white leading-none">{{ auth()->check() ? auth()->user()->name : 'Invitado' }}</span>
                            <span class="text-[10px] text-[#06B6D4] uppercase font-bold mt-1">{{ auth()->check() ? auth()->user()->role ?? 'Admin' : 'Invitado' }}</span>
                        </div>"""
c = re.sub(r'<div class="flex flex-col text-left hidden md:block ml-2">.*?</div>', admin_block, c, flags=re.DOTALL)

# 3. Update Logos to use font-logo
c = c.replace('font-outfit', 'font-logo')
c = c.replace('class="text-[12px] font-black tracking-[0.2em] text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300 font-logo"', 'class="text-[12px] font-black tracking-[0.2em] text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300 font-logo"')


# 4. Restore EXACT emojis and link names
nav_pattern = r'<!-- Navegación -->\s*<nav[^>]*>.*?</nav>'
new_nav = """<!-- Navegación -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('equipos.index') }}" class="nav-item {{ request()->routeIs('equipos.*') ? 'active' : '' }}" title="Equipos">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-label">Equipos</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') ? 'active' : '' }}" title="Mantenimientos">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') ? 'active' : '' }}" title="Electrónica">
                    <span class="nav-icon">⚡</span>
                    <span class="nav-label">Electrónica</span>
                </a>
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}" title="Control Stock">
                    <span class="nav-icon">📦</span>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Compra y Venta">
                    <span class="nav-icon">🧾</span>
                    <span class="nav-label">Compra y Venta</span>
                </a>
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" title="Clientes">
                    <span class="nav-icon">👤</span>
                    <span class="nav-label">Clientes</span>
                </a>
                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" title="Proveedores">
                    <span class="nav-icon">🏭</span>
                    <span class="nav-label">Proveedores</span>
                </a>
                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" title="Personal">
                    <span class="nav-icon">🛠️</span>
                    <span class="nav-label">Personal</span>
                </a>
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" title="Caja Fuerte">
                    <span class="nav-icon">💵</span>
                    <span class="nav-label">Caja Fuerte</span>
                </a>
                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" title="Cierre Diario">
                    <span class="nav-icon">🔒</span>
                    <span class="nav-label">Cierre Diario</span>
                </a>
                <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Informes Operativos">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Informes Operativos</span>
                </a>
                <a href="{{ route('reportes.financiero.acumulado') }}" class="nav-item {{ request()->routeIs('reportes.financiero.*') ? 'active' : '' }}" title="Informes Financieros">
                    <span class="nav-icon">💹</span>
                    <span class="nav-label">Informes Financieros</span>
                </a>
                <a href="{{ route('usuarios.index') }}" class="nav-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" title="Usuarios del Sistema">
                    <span class="nav-icon">👨🏻‍💻</span>
                    <span class="nav-label">Usuarios del Sistema</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-label">Empresa</span>
                </a>
            </nav>"""
c = re.sub(nav_pattern, new_nav, c, flags=re.DOTALL)


# 5. Remove Auto-scroll JS
scroll_pattern = r'<script>\s*document\.addEventListener\(\'DOMContentLoaded\', \(\) => \{\s*const tableContainer = document\.querySelector\(\'\.table-responsive, \.overflow-x-auto, table\'\);.*?\}\);\s*</script>'
c = re.sub(scroll_pattern, '', c, flags=re.DOTALL)


with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
