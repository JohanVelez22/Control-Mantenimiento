import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# 1. Add Outfit font and apply it to body (without destroying background color)
font_link = """<link rel="stylesheet" href="{{ asset('css/glass.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
    </style>"""
c = c.replace("""<link rel="stylesheet" href="{{ asset('css/glass.css') }}">""", font_link)


# 2. Replace Sidebar Logo with "NAVEGACION"
logo_pattern = r'<!-- Brand / Logo -->.*?</a>\s*</div>'
nav_header = """<!-- Brand / Logo -->
            <div class="h-20 flex items-center justify-center expanded:justify-start border-b border-gray-200/40 dark:border-white/5 shrink-0 px-6 relative transition-all duration-300">
                <span class="text-[12px] font-black tracking-[0.2em] text-[#06B6D4] uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300 font-outfit">NAVEGACIÓN</span>
                <span class="text-[11px] font-black text-[#2563EB] uppercase group-[.expanded]:hidden font-outfit">NAV</span>
            </div>"""
c = re.sub(logo_pattern, nav_header, c, flags=re.DOTALL)


# 3. Replace Navigation Links
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


# 4. Replace Topbar Search with Centered Logo
search_pattern = r'<!-- Search global \(placeholder\) -->.*?</div>\s*</div>'
top_logo = """<!-- Centro: Logo Centrado -->
                <div class="flex-1 max-w-xl mx-4 hidden md:flex justify-center items-center">
                    <a href="{{ route('dashboard') }}" class="text-[26px] font-black tracking-widest hover:scale-105 transition-transform duration-300 font-outfit">
                        <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span><span class="text-slate-800 dark:text-white">SYSTEMAS</span>
                    </a>
                </div>
            </div>"""
c = re.sub(search_pattern, top_logo, c, flags=re.DOTALL)


# 5. Replace Top Right Icons (Profile, Bell, Sun, Logout)
right_icons_pattern = r'<!-- Derecha: Acciones rápidas y Perfil -->.*?</div>\s*</header>'
right_icons = """<!-- Derecha: Perfil e Iconos -->
                <div class="flex items-center gap-3">
                    
                    <!-- Avatar de Usuario (Clickeable hacia Usuarios) -->
                    <a href="{{ route('usuarios.index') }}" class="flex items-center gap-2 pr-4 border-r border-gray-200 dark:border-white/10 hover:opacity-80 transition-opacity mr-2">
                        @if(auth()->check() && auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Avatar" class="w-10 h-10 rounded-xl object-cover border-2 border-gray-200 dark:border-white/10">
                        @elseif(auth()->check())
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="flex flex-col text-left hidden md:block ml-2">
                            <span class="text-sm font-bold text-slate-800 dark:text-white leading-tight font-outfit">{{ auth()->check() ? auth()->user()->name : 'Invitado' }}</span>
                            <span class="text-[10px] text-blue-600 dark:text-blue-400 uppercase font-bold">{{ auth()->check() ? auth()->user()->role ?? 'Admin' : 'Invitado' }}</span>
                        </div>
                    </a>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-yellow-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>
                    </div>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/10 transition-colors group">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-yellow-500 group-hover:scale-110 transition-transform">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-full bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 hover:bg-red-50 hover:border-red-200 dark:hover:bg-red-500/20 dark:hover:border-red-500/30 transition-all group" title="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>"""
c = re.sub(right_icons_pattern, right_icons, c, flags=re.DOTALL)


# 6. Add Anchor Scroll JS at the bottom
scroll_js = """<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableContainer = document.querySelector('.table-responsive, .overflow-x-auto, table');
        if (tableContainer) {
            tableContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
</body>"""
c = c.replace('</body>', scroll_js)


with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
