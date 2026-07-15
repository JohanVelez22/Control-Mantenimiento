            <!-- TOPBAR DE VIDRIO -->
            <header id="ts-topbar" class="h-16 px-4 md:px-6 flex items-center justify-between no-print">
                <!-- Izquierda: Toggle móvil -->
                <div class="flex items-center gap-3">
                    <button class="lg:hidden p-2 bg-gray-100/50 dark:bg-gray-800/50 rounded-xl transition-colors" onclick="toggleMobileSidebar()">
                        ☰
                    </button>
                </div>

                <!-- Centro: Logo Centrado -->
                <div class="topbar-logo-container hidden md:flex justify-center items-center">
                    <a href="{{ route('dashboard') }}" class="text-[20px] font-black tracking-widest hover:scale-105 transition-transform duration-300 font-logo flex items-center gap-2">
                        <span class="text-[#2563EB] dark:text-[#3B82F6]">TECNI</span>
                        <span class="text-slate-800 dark:text-white">SYSTEMAS</span>
                    </a>
                </div>

                <!-- Derecha: Perfil e Iconos -->
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
                        <div class="hidden md:flex flex-col text-left ml-2">
                            <span class="text-sm font-bold text-slate-800 dark:text-white leading-none">{{ auth()->check() ? auth()->user()->name : 'Invitado' }}</span>
                            <span class="text-[10px] text-[#06B6D4] uppercase font-bold mt-1">{{ auth()->check() ? auth()->user()->role ?? 'Admin' : 'Invitado' }}</span>
                        </div>
                    </a>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg relative" onclick="toggleNotifDropdown()">
                            🔔
                            @if(isset($totalPendientes) && $totalPendientes > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border border-white dark:border-slate-800"></span>
                                </span>
                            @endif
                        </button>
                        
                        <!-- Notification Dropdown - Acrylic Glass, centered, rectangular -->
                        <div id="notif-dropdown" class="hidden absolute right-1/2 translate-x-1/2 mt-3 z-50 opacity-0 scale-95 transition-all duration-200 origin-top" style="width:240px">

                            <!-- Arrow tip -->
                            <div class="flex justify-center">
                                <div id="notif-arrow" class="w-3 h-3 rotate-45 border-l border-t" style="margin-bottom:-7px; background:rgba(255,255,255,0.82); border-color:rgba(255,255,255,0.4);"></div>
                            </div>

                            <!-- Acrylic panel -->
                            <div id="notif-panel" class="rounded-lg overflow-hidden content-scroll"
                                 style="background:rgba(255,255,255,0.82); border:1px solid rgba(255,255,255,0.5); backdrop-filter:blur(32px) saturate(200%); -webkit-backdrop-filter:blur(32px) saturate(200%); box-shadow:0 8px 32px rgba(0,0,0,0.18), inset 0 1px 0 rgba(255,255,255,0.6);">

                                <!-- Header -->
                                <div id="notif-header" class="px-3 py-2.5 relative border-b text-center"
                                     style="background:rgba(37,99,235,0.12); border-color:rgba(37,99,235,0.15);">
                                    <span id="notif-title" class="text-[11px] font-semibold tracking-[0.15em] font-logo inline-block uppercase" style="color:#06B6D4;">Pendientes</span>
                                    @if(isset($totalPendientes) && $totalPendientes > 0)
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center justify-center min-w-[20px] h-[20px] px-1 rounded-full text-white text-[10px] font-black" style="background:#ef4444; box-shadow:0 2px 6px rgba(239,68,68,0.5)">{{ $totalPendientes }}</span>
                                    @endif
                                </div>

                                <!-- Items -->
                                @if(isset($totalPendientes) && $totalPendientes > 0)
                                <div class="py-1">
                                    @if($mantPendientes > 0)
                                    <button onclick="openNotifModal('mant'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                            style="border-bottom:1px solid rgba(0,0,0,0.05);">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(59,130,246,0.15);">🔧</div>
                                        <span class="text-sm font-semibold flex-1" style="color:#1e293b;">Mantenimiento</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#3b82f6; box-shadow:0 2px 6px rgba(59,130,246,0.4)">{{ $mantPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($elecPendientes > 0)
                                    <button onclick="openNotifModal('elec'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-purple-50 dark:hover:bg-purple-900/30"
                                            style="border-bottom:1px solid rgba(0,0,0,0.05);">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(168,85,247,0.15);">⚡</div>
                                        <span class="text-sm font-semibold flex-1" style="color:#1e293b;">Electrónica</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#a855f7; box-shadow:0 2px 6px rgba(168,85,247,0.4)">{{ $elecPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($cajaPendientes > 0)
                                    <button onclick="openNotifModal('caja'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3 transition-colors hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(245,158,11,0.15);">💰</div>
                                        <span class="text-sm font-semibold flex-1" style="color:#1e293b;">Saldos / Caja</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#f59e0b; box-shadow:0 2px 6px rgba(245,158,11,0.4)">{{ $cajaPendientes }}</span>
                                    </button>
                                    @endif
                                </div>
                                <!-- Footer CTA -->
                                <button id="notif-footer" onclick="openNotifModal('all'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                        class="w-full py-2.5 text-xs font-bold text-center tracking-wide transition-colors hover:bg-blue-50 dark:hover:bg-slate-800/50"
                                        style="border-top:1px solid rgba(37,99,235,0.15); background:rgba(37,99,235,0.10); color:#1d4ed8;">
                                    Ver todos →
                                </button>
                                @else
                                <div class="py-1">
                                    <div class="px-4 py-5 text-center">
                                        <div class="text-2xl mb-1">✅</div>
                                        <p class="text-sm font-semibold" style="color:#374151;">¡Todo al día!</p>
                                        <p class="text-xs mt-0.5" style="color:#9ca3af;">Sin tareas pendientes.</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <script>
                    // Adapt acrylic dropdown to dark mode
                    function applyNotifDropdownTheme() {
                        const isDark = document.documentElement.classList.contains('dark');
                        const panel = document.getElementById('notif-panel');
                        const header = document.getElementById('notif-header');
                        const arrow = document.getElementById('notif-arrow');
                        const title = document.getElementById('notif-title');
                        const svg = document.getElementById('notif-header-svg');
                        const footer = document.getElementById('notif-footer');
                        
                        if (!panel) return;
                        
                        if (isDark) {
                            panel.style.background = 'rgba(15,23,42,0.98)';
                            panel.style.border = '1px solid rgba(255,255,255,0.12)';
                            panel.style.boxShadow = '0 20px 40px rgba(0,0,0,0.8), inset 0 1px 0 rgba(255,255,255,0.1)';
                            if (arrow)  { arrow.style.background = 'rgba(15,23,42,0.98)'; arrow.style.borderColor = 'rgba(255,255,255,0.12)'; }
                            
                            if (header) { 
                                header.style.background = 'transparent'; 
                                header.style.borderColor = 'rgba(255,255,255,0.1)'; 
                            }
                            if (title) { title.style.color = '#06B6D4'; } // cyan-500 matching NAVEGACION
                            
                            if (footer) {
                                footer.style.background = 'rgba(30,41,59,0.5)'; // bg-slate-800/50
                                footer.style.borderColor = 'rgba(255,255,255,0.05)';
                                footer.style.color = '#38bdf8';
                            }
                            
                            document.querySelectorAll('.notif-row span.flex-1').forEach(el => el.style.color = '#f1f5f9');
                            document.querySelectorAll('.notif-row').forEach(el => el.style.borderBottomColor = 'rgba(255,255,255,0.05)');
                        } else {
                            panel.style.background = 'rgba(255,255,255,0.98)';
                            panel.style.border = '1px solid rgba(0,0,0,0.1)';
                            panel.style.boxShadow = '0 20px 40px rgba(0,0,0,0.1)';
                            if (arrow)  { arrow.style.background = 'rgba(255,255,255,0.98)'; arrow.style.borderColor = 'rgba(0,0,0,0.1)'; }
                            
                            if (header) { 
                                header.style.background = 'transparent'; 
                                header.style.borderColor = 'rgba(0,0,0,0.08)'; 
                            }
                            if (title) { title.style.color = '#06B6D4'; } // cyan-500 matching NAVEGACION
                            
                            if (footer) {
                                footer.style.background = 'rgba(248,250,252,0.8)'; // bg-slate-50/80
                                footer.style.borderColor = 'rgba(0,0,0,0.05)';
                                footer.style.color = '#2563eb';
                            }
                            
                            document.querySelectorAll('.notif-row span.flex-1').forEach(el => el.style.color = '#1e293b');
                            document.querySelectorAll('.notif-row').forEach(el => el.style.borderBottomColor = 'rgba(0,0,0,0.05)');
                        }
                    }
                    document.addEventListener('DOMContentLoaded', applyNotifDropdownTheme);
                    // Re-apply when theme toggle is clicked
                    document.addEventListener('click', e => {
                        if (e.target.closest('#theme-toggle') || e.target.closest('#theme-toggle-login')) {
                            setTimeout(applyNotifDropdownTheme, 50);
                        }
                    });
                    </script>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/60 border border-gray-200 hover:bg-gray-100 dark:bg-[#1e293b]/50 dark:border-gray-600/40 dark:hover:bg-gray-700/60 shadow-sm transition-colors group text-lg">
                        <span class="dark:hidden">☀️</span>
                        <span class="hidden dark:inline">🌙</span>
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 pl-1">
                        @csrf
                        <button type="submit" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-500/10 border border-red-500/20 hover:bg-red-500/20 transition-all group text-lg" title="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-red-500 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>
