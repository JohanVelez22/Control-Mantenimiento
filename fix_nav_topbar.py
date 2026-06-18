import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Update NAVEGACION styling (Michroma font, exact color, slightly larger, no font-black)
nav_logo_pattern = r'<span class="text-\[12px\] font-black tracking-\[0\.2em\] text-\[#06B6D4\] uppercase opacity-0 group-\[\.expanded\]:opacity-100 transition-opacity duration-300 font-logo">NAVEGACIÓN</span>'
new_nav_logo = '<span class="text-[13px] font-normal tracking-widest text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300 font-logo">NAVEGACIÓN</span>'
c = re.sub(nav_logo_pattern, new_nav_logo, c)


# 2. Update the Sidebar Navigation Links to the EXACT order and names in the image
nav_pattern = r'<!-- Navegación -->\s*<nav[^>]*>.*?</nav>'
new_nav = """<!-- Navegación -->
            <nav class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-hide py-4 px-2 space-y-1">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" title="Clientes">
                    <span class="nav-icon">👤</span>
                    <span class="nav-label">Clientes</span>
                </a>
                <a href="{{ route('equipos.index') }}" class="nav-item {{ request()->routeIs('equipos.*') ? 'active' : '' }}" title="Equipos">
                    <span class="nav-icon">🖥️</span>
                    <span class="nav-label">Equipos</span>
                </a>
                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" title="Proveedores">
                    <span class="nav-icon">🏭</span>
                    <span class="nav-label">Proveedores</span>
                </a>
                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" title="Técnicos">
                    <span class="nav-icon">🛠️</span>
                    <span class="nav-label">Técnicos</span>
                </a>
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') ? 'active' : '' }}" title="Control Stock">
                    <span class="nav-icon">📦</span>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Operaciones (C/V)">
                    <span class="nav-icon">📄</span>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') ? 'active' : '' }}" title="Mantenimientos">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') ? 'active' : '' }}" title="Electrónica">
                    <span class="nav-icon">⚡</span>
                    <span class="nav-label">Electrónica</span>
                </a>
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" title="Caja (Ing/Egr)">
                    <span class="nav-icon">💵</span>
                    <span class="nav-label">Caja (Ing/Egr)</span>
                </a>
                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" title="Arqueo / Cierre">
                    <span class="nav-icon">🔒</span>
                    <span class="nav-label">Arqueo / Cierre</span>
                </a>
                <a href="{{ route('reportes.index') }}" class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}" title="Info Operativos">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Info Operativos</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-label">Empresa</span>
                </a>
            </nav>"""
c = re.sub(nav_pattern, new_nav, c, flags=re.DOTALL)


# 3. Remove Logout button from bottom of sidebar
logout_sidebar_pattern = r'<!-- User Mini Profile \(Fondo\) -->.*?</div>\s*</aside>'
c = re.sub(logout_sidebar_pattern, '</aside>', c, flags=re.DOTALL)


# 4. Update Top Right Icons to use Emojis and rectangular borders (rounded-xl)
# Re-do the entire top right icons block
right_icons_pattern = r'<!-- Notification Bell -->.*?</form>'
new_icons = """<!-- Notification Bell -->
                    <div class="relative">
                        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#1e293b]/30 border border-gray-600/30 hover:bg-gray-700/50 transition-colors group text-lg" onclick="document.getElementById('notif-dropdown').classList.toggle('hidden')">
                            🔔
                        </button>
                    </div>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#1e293b]/30 border border-gray-600/30 hover:bg-gray-700/50 transition-colors group text-lg">
                        🌞
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-all group text-lg" title="Cerrar Sesión">
                            🚪
                        </button>
                    </form>"""
c = re.sub(right_icons_pattern, new_icons, c, flags=re.DOTALL)


with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
