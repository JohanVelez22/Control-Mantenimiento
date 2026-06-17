import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Font Outfit + Body `#0A0F1C`
content = content.replace(
    """<link rel="stylesheet" href="{{ asset('css/glass.css') }}">""",
    """<link rel="stylesheet" href="{{ asset('css/glass.css') }}">\n    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">\n    <style>\n        body { font-family: 'Outfit', sans-serif !important; background-color: #0A0F1C !important; }\n        .nav-item.active { background: linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(37,99,235,0.05) 100%); color: #60A5FA; border-left: 3px solid #3B82F6; font-weight: 700; }\n    </style>"""
)

# 2. Sidebar Dark `#0B1121`
content = re.sub(
    r'<aside id="ts-sidebar"[^>]+>',
    '<aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col shrink-0 relative bg-[#0B1121] border-r border-white/5 transition-all duration-300 z-30 shadow-[4px_0_24px_rgba(0,0,0,0.2)] w-[70px] expanded:w-[280px]">',
    content
)

# 3. NAVEGACION instead of Logo in Sidebar
logo_pattern = r'<!-- Brand / Logo -->.*?</a>'
nav_header = """<!-- Brand / Logo -->
            <div class="h-20 flex items-center justify-center expanded:justify-start border-b border-white/5 shrink-0 px-6 relative transition-all duration-300">
                <span class="text-[11px] font-black tracking-[0.2em] text-blue-500 uppercase opacity-0 group-[.expanded]:opacity-100 transition-opacity duration-300">NAVEGACIÓN</span>
                <span class="text-[11px] font-black text-blue-500 uppercase group-[.expanded]:hidden">NAV</span>
            </div>"""
content = re.sub(logo_pattern, nav_header, content, flags=re.DOTALL)

# 4. Topbar `#0B1121`
content = re.sub(
    r'<header class="h-20 flex items-center justify-between[^>]+>',
    '<header class="h-20 flex items-center justify-between px-4 sm:px-6 lg:px-8 bg-[#0B1121] border-b border-white/5 relative z-20 shrink-0">',
    content
)

# 5. Centered Logo replacing search bar
search_pattern = r'<!-- Centro: Barra de búsqueda global -->.*?</div>\s*</div>'
top_logo = """<!-- Centro: Logo -->
                <div class="flex-1 max-w-xl mx-4 hidden md:flex justify-center items-center">
                    <span class="text-2xl font-black tracking-widest"><span class="text-[#2563EB]">TECNI</span><span class="text-white">SYSTEMAS</span></span>
                </div>"""
content = re.sub(search_pattern, top_logo, content, flags=re.DOTALL)

# 6. Quick actions and icons (replace with notification bell, theme toggle, SVG logout)
right_actions_pattern = r'<!-- Derecha: Acciones rápidas y Perfil -->.*?<!-- Avatar de Usuario -->'
right_actions = """<!-- Derecha: Acciones rápidas y Perfil -->
                <div class="flex items-center gap-3">
                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition-colors group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-yellow-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>
                    </div>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-white/10 transition-colors group">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-yellow-500 group-hover:scale-110 transition-transform">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-2">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-red-500/20 hover:border-red-500/30 transition-all group">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>

                    <!-- Avatar de Usuario -->"""
content = re.sub(right_actions_pattern, right_actions, content, flags=re.DOTALL)

# 7. Remove Logout from Bottom of Sidebar
logout_sidebar = r'<!-- User Mini Profile \(Fondo\) -->.*?</div>\s*</aside>'
content = re.sub(logout_sidebar, '</aside>', content, flags=re.DOTALL)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)
