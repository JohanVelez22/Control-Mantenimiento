import re

with open('resources/views/layouts/app.blade.php', 'r', encoding='utf-8') as f:
    c = f.read()

# Make topbar dark and remove search bar
header_pattern = r'<header id="ts-topbar" class="h-16 px-4 md:px-6 flex items-center justify-between no-print">.*?</header>'
new_header = """<header id="ts-topbar" class="h-20 px-4 md:px-6 flex items-center justify-between no-print bg-[#0B1121] border-b border-white/5">
                <!-- Izquierda: Toggle móvil -->
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 bg-white/5 hover:bg-white/10 rounded-xl transition-colors" onclick="toggleMobileSidebar()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>

                <!-- Centro: Logo Centrado -->
                <div class="flex-1 max-w-xl mx-4 hidden md:flex justify-center items-center">
                    <a href="{{ route('dashboard') }}" class="text-[26px] font-black tracking-widest hover:scale-105 transition-transform duration-300">
                        <span class="text-[#2563EB]">TECNI</span><span class="text-white">SYSTEMAS</span>
                    </a>
                </div>

                <!-- Derecha: Perfil e Iconos -->
                <div class="flex items-center gap-3">
                    
                    <!-- Avatar de Usuario (Clickeable hacia Usuarios) -->
                    <a href="{{ route('usuarios.index') }}" class="flex items-center gap-2 pr-4 border-r border-white/10 hover:opacity-80 transition-opacity mr-2">
                        @if(auth()->check() && auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Avatar" class="w-10 h-10 rounded-xl object-cover border-2 border-white/10">
                        @elseif(auth()->check())
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="flex flex-col text-left hidden md:block ml-2">
                            <span class="text-sm font-bold text-white leading-tight">{{ auth()->check() ? auth()->user()->name : 'Invitado' }}</span>
                            <span class="text-[10px] text-blue-400 uppercase font-bold">{{ auth()->check() ? auth()->user()->role ?? 'Admin' : 'Invitado' }}</span>
                        </div>
                    </a>

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
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-full bg-white/5 border border-white/10 hover:bg-red-500/20 hover:border-red-500/30 transition-all group" title="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>"""

c = re.sub(header_pattern, new_header, c, flags=re.DOTALL)

with open('resources/views/layouts/app.blade.php', 'w', encoding='utf-8') as f:
    f.write(c)
