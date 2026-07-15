        <!-- SIDEBAR DE VIDRIO (Fijo) -->
        <aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col">
            <!-- Brand / Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200/40 dark:border-white/5 shrink-0 px-6 relative transition-all duration-150">
                <span class="text-[11px] font-semibold tracking-[0.15em] text-[#06B6D4] uppercase font-logo text-center w-full sidebar-brand-text transition-opacity duration-200">NAVEGACIÓN</span>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 flex flex-col justify-between overflow-y-auto overflow-x-hidden py-4 px-2 gap-1">
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
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') && !request()->routeIs('stocks.reportes') ? 'active' : '' }}" title="Control Stock">
                    <span class="nav-icon">📦</span>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" title="Operaciones (C/V)">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-label">Operaciones (C/V)</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') && !request()->routeIs('mantenimientos.reportes') ? 'active' : '' }}" title="Mantenimientos">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') && !request()->routeIs('electronicas.reportes') ? 'active' : '' }}" title="Electrónica">
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
                <a href="{{ route('reportes.financiero.diario') }}" class="nav-item {{ request()->routeIs('reportes.*') || request()->routeIs('mantenimientos.reportes') || request()->routeIs('electronicas.reportes') || request()->routeIs('stocks.reportes') ? 'active' : '' }}" title="Info Operativos">
                    <span class="nav-icon">📈</span>
                    <span class="nav-label">Info Operativos</span>
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" title="Empresa">
                    <span class="nav-icon">🏢</span>
                    <span class="nav-label">Empresa</span>
                </a>
                @if(auth()->user() && auth()->user()->isAdmin())
                <a href="{{ route('eventos.index') }}" class="nav-item {{ request()->routeIs('eventos.*') ? 'active' : '' }}" title="Eventos (Auditoría)">
                    <span class="nav-icon">🕵🏻</span>
                    <span class="nav-label">Eventos</span>
                </a>
                @endif
            </nav>
            
            </aside>
