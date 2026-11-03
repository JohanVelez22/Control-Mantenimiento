            <!-- TOPBAR DE VIDRIO -->
            <header id="ts-topbar" class="h-16 px-4 md:px-6 flex items-center justify-between no-print">
                <!-- Izquierda: Toggle móvil afin al tema (Liquid Glass azul/cyan) -->
                <div class="flex items-center gap-3">
                    <button type="button" aria-label="Abrir menú" class="lg:hidden p-2.5 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 active:bg-blue-500/30 border border-blue-500/20 dark:border-cyan-500/30 text-blue-600 dark:text-cyan-400 hover:text-blue-700 dark:hover:text-cyan-300 transition-all duration-200 shadow-sm shadow-blue-500/10 flex items-center justify-center active:scale-95 group" onclick="toggleMobileSidebar()">
                        <svg class="w-5 h-5 transition-transform group-hover:scale-105" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <!-- Centro: Único Logo Centrado (visible en desktop, oculto en móvil/tablets para no colisionar) -->
                <div class="topbar-logo-container hidden lg:flex justify-center items-center">
                    <a href="{{ route('dashboard') }}" class="text-[20px] font-black tracking-widest hover:scale-105 transition-transform duration-300 font-logo flex items-center gap-2.5">
                        <img src="{{ asset('images/wolfcore.png') }}" alt="WolfCore System" class="w-7 h-7 object-contain">
                        <span class="text-[#2563EB] dark:text-[#3B82F6]">WOLFCORE</span>
                        <span class="text-slate-800 dark:text-white">SYSTEM</span>
                    </a>
                </div>

                <!-- Derecha: Perfil e Iconos -->
                <div class="flex items-center gap-3">
                    
                    <!-- Avatar de Usuario (Clickeable hacia Usuarios o Perfil propio con efecto escala interactivo sin tinte azul) -->
                    <a href="{{ auth()->check() && auth()->user()->isAdmin() ? route('usuarios.index') : (auth()->check() ? route('usuarios.edit', auth()->user()) : '#') }}" 
                       class="group flex items-center gap-2 pr-4 border-r border-gray-200 dark:border-white/10 hover:scale-105 transition-transform duration-300 mr-2 cursor-pointer"
                       title="{{ auth()->check() && auth()->user()->isAdmin() ? 'Gestionar Usuarios' : 'Mi Perfil' }}">
                        @if(auth()->check() && auth()->user()->photo)
                            <img src="{{ asset('storage/' . auth()->user()->photo) }}" alt="Avatar" class="w-10 h-10 rounded-xl object-cover border-2 border-gray-200 dark:border-white/10 group-hover:shadow-md transition-all duration-300" onerror="this.style.display='none'; var fb=document.getElementById('topbar-avatar-fallback'); if(fb) fb.classList.remove('hidden');">
                            <div id="topbar-avatar-fallback" class="hidden w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg transition-all duration-300">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @elseif(auth()->check())
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white flex items-center justify-center font-bold shadow-lg transition-all duration-300">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="hidden md:flex flex-col text-left ml-2">
                            <span class="text-sm font-bold text-slate-800 dark:text-white leading-none">{{ auth()->check() ? auth()->user()->name : 'Invitado' }}</span>
                            <span class="text-[10px] text-[#06B6D4] uppercase font-bold mt-1 tracking-wider">{{ auth()->check() ? auth()->user()->role ?? 'Admin' : 'Invitado' }}</span>
                        </div>
                    </a>

                    <!-- Notification Bell -->
                    <div class="relative">
                        <button class="notif-toggle-btn group relative" onclick="toggleNotifDropdown(); this.blur();" title="Notificaciones" aria-label="Notificaciones">
                            <span class="inline-block transition-transform duration-200 group-hover:scale-110">🔔</span>
                            @if(isset($totalPendientes) && $totalPendientes > 0)
                                <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5 pointer-events-none">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border border-white dark:border-slate-900 shadow-sm shadow-red-500/50"></span>
                                </span>
                            @endif
                        </button>
                        
                        <!-- Notification Dropdown - Frosted Matte Acrylic Glass (Portaled to body) -->
                        <div id="notif-dropdown" class="hidden fixed z-[99999] opacity-0 scale-95 transition-all duration-200 origin-top pointer-events-auto">

                            <!-- Arrow tip -->
                            <div class="flex justify-start">
                                <div id="notif-arrow"></div>
                            </div>

                            <!-- Acrylic panel -->
                            <div id="notif-panel" class="content-scroll">

                                <!-- Header -->
                                <div id="notif-header" class="px-4 py-2.5 relative text-center">
                                    <span id="notif-title" class="text-[11px] font-semibold tracking-[0.15em] font-logo inline-block uppercase text-cyan-600 dark:text-cyan-400">Pendientes</span>
                                    @if(isset($totalPendientes) && $totalPendientes > 0)
                                    <span class="absolute top-1/2 -translate-y-1/2 flex items-center justify-center text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="right:16px; background:#ef4444; box-shadow:0 2px 6px rgba(239,68,68,0.5)">{{ $totalPendientes }}</span>
                                    @endif
                                </div>

                                <!-- Items -->
                                @if(isset($totalPendientes) && $totalPendientes > 0)
                                <div class="py-1">
                                    @if($mantPendientes > 0)
                                    <button onclick="openNotifModal('mant'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(59,130,246,0.15);">🔧</div>
                                        <span class="text-sm font-semibold flex-1 text-slate-800 dark:text-slate-100">Mantenimiento</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#3b82f6; box-shadow:0 2px 6px rgba(59,130,246,0.4)">{{ $mantPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($elecPendientes > 0)
                                    <button onclick="openNotifModal('elec'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(168,85,247,0.15);">⚡</div>
                                        <span class="text-sm font-semibold flex-1 text-slate-800 dark:text-slate-100">Electrónica</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#a855f7; box-shadow:0 2px 6px rgba(168,85,247,0.4)">{{ $elecPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($cotPendientes > 0)
                                    <button onclick="openNotifModal('cot'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(99,102,241,0.15);">📑</div>
                                        <span class="text-sm font-semibold flex-1 text-slate-800 dark:text-slate-100">Cotizaciones</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#6366f1; box-shadow:0 2px 6px rgba(99,102,241,0.4)">{{ $cotPendientes }}</span>
                                    </button>
                                    @endif
                                    @if($cajaPendientes > 0)
                                    <button onclick="openNotifModal('caja'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                            class="notif-row w-full text-left px-4 py-2.5 flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 text-sm" style="background:rgba(245,158,11,0.15);">💰</div>
                                        <span class="text-sm font-semibold flex-1 text-slate-800 dark:text-slate-100">Saldos / Caja</span>
                                        <span class="text-[11px] font-black px-2 py-0.5 rounded-full text-white" style="background:#f59e0b; box-shadow:0 2px 6px rgba(245,158,11,0.4)">{{ $cajaPendientes }}</span>
                                    </button>
                                    @endif
                                </div>
                                <!-- Footer CTA -->
                                <button id="notif-footer" onclick="openNotifModal('all'); document.getElementById('notif-dropdown').classList.add('hidden');"
                                        class="w-full py-2.5 text-xs font-bold text-center tracking-wide">
                                    Ver todos →
                                </button>
                                @else
                                <div class="py-1">
                                    <div class="px-4 py-5 text-center">
                                        <div class="text-2xl mb-1">✅</div>
                                        <p id="notif-empty-title" class="text-sm font-bold text-slate-800 dark:text-slate-100">¡Todo al día!</p>
                                        <p id="notif-empty-sub" class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Sin tareas pendientes.</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <script>
                    function applyNotifDropdownTheme() {
                        // El estilo de acrílico mate se gestiona íntegramente por CSS en _topbar.css
                    }
                    window.applyNotifDropdownTheme = applyNotifDropdownTheme;
                    document.addEventListener('DOMContentLoaded', applyNotifDropdownTheme);
                    </script>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="theme-toggle-btn group" title="Cambiar tema" aria-label="Cambiar tema">
                        <span class="dark:hidden inline-block transition-transform duration-200 group-hover:scale-110">☀️</span>
                        <span class="hidden dark:inline inline-block transition-transform duration-200 group-hover:scale-110">🌙</span>
                    </button>
                    
                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="m-0 flex items-center">
                        @csrf
                        <button type="submit" class="logout-toggle-btn group text-lg" title="Cerrar Sesión" aria-label="Cerrar Sesión">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 group-hover:scale-110 transition-transform">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </header>
