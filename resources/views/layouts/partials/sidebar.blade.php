        <!-- SIDEBAR DE VIDRIO (Fijo en Desktop, Drawer en Móvil) -->
        <aside id="ts-sidebar" class="no-print group hover:expanded flex flex-col">
            <!-- Brand / Logo -->
            <div class="h-16 flex flex-col items-center justify-center border-b border-gray-200/40 dark:border-white/5 shrink-0 px-4 relative transition-all duration-150">
                <!-- Título WOLFCORE SYSTEM: Aparece sobre NAVEGACIÓN únicamente cuando se oculta en el topbar (zoom >= 150% o móvil) -->
                <div class="sidebar-logo-title flex items-center justify-center gap-1.5 font-logo text-[13px] font-black leading-tight tracking-wider transition-all duration-200 select-none">
                    <span class="text-[#2563EB] dark:text-[#3B82F6]">WOLFCORE</span>
                    <span class="text-slate-800 dark:text-white">SYSTEM</span>
                </div>

                <!-- NAVEGACIÓN -->
                <span class="sidebar-brand-text font-semibold uppercase font-logo text-center transition-all duration-200 select-none">NAVEGACIÓN</span>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 flex flex-col justify-between overflow-y-auto overflow-x-hidden py-4 px-2 gap-1">
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif title="Dashboard">
                    <span class="nav-icon"><x-icon name="dashboard" class="w-4 h-4" /></span>
                    <span class="nav-label">Dashboard</span>
                </a>
                <a href="{{ route('clientes.index') }}" class="nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}" @if(request()->routeIs('clientes.*')) aria-current="page" @endif title="Clientes">
                    <span class="nav-icon"><x-icon name="clientes" class="w-4 h-4" /></span>
                    <span class="nav-label">Clientes</span>
                </a>
                <a href="{{ route('equipos.index') }}" class="nav-item {{ request()->routeIs('equipos.*') ? 'active' : '' }}" @if(request()->routeIs('equipos.*')) aria-current="page" @endif title="Equipos">
                    <span class="nav-icon"><x-icon name="equipos" class="w-4 h-4" /></span>
                    <span class="nav-label">Equipos</span>
                </a>
                <a href="{{ route('proveedores.index') }}" class="nav-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" @if(request()->routeIs('proveedores.*')) aria-current="page" @endif title="Proveedores">
                    <span class="nav-icon"><x-icon name="proveedores" class="w-4 h-4" /></span>
                    <span class="nav-label">Proveedores</span>
                </a>
                <a href="{{ route('tecnicos.index') }}" class="nav-item {{ request()->routeIs('tecnicos.*') ? 'active' : '' }}" @if(request()->routeIs('tecnicos.*')) aria-current="page" @endif title="Técnicos">
                    <span class="nav-icon"><x-icon name="tecnicos" class="w-4 h-4" /></span>
                    <span class="nav-label">Técnicos</span>
                </a>
                <a href="{{ route('stocks.index') }}" class="nav-item {{ request()->routeIs('stocks.*') && !request()->routeIs('stocks.reportes') ? 'active' : '' }}" @if(request()->routeIs('stocks.*') && !request()->routeIs('stocks.reportes')) aria-current="page" @endif title="Control Stock">
                    <span class="nav-icon"><x-icon name="stock" class="w-4 h-4" /></span>
                    <span class="nav-label">Control Stock</span>
                </a>
                <a href="{{ route('inventario.facturas') }}" class="nav-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}" @if(request()->routeIs('inventario.*')) aria-current="page" @endif title="Compras y ventas">
                    <span class="nav-icon"><x-icon name="operaciones" class="w-4 h-4" /></span>
                    <span class="nav-label">Compras y ventas</span>
                </a>
                <a href="{{ route('cotizaciones.index') }}" class="nav-item {{ request()->routeIs('cotizaciones.*') ? 'active' : '' }}" @if(request()->routeIs('cotizaciones.*')) aria-current="page" @endif title="Cotizaciones">
                    <span class="nav-icon"><x-icon name="cotizaciones" class="w-4 h-4" /></span>
                    <span class="nav-label">Cotizaciones</span>
                </a>
                <a href="{{ route('mantenimientos.index') }}" class="nav-item {{ request()->routeIs('mantenimientos.*') && !request()->routeIs('mantenimientos.reportes') ? 'active' : '' }}" @if(request()->routeIs('mantenimientos.*') && !request()->routeIs('mantenimientos.reportes')) aria-current="page" @endif title="Mantenimientos">
                    <span class="nav-icon"><x-icon name="mantenimientos" class="w-4 h-4" /></span>
                    <span class="nav-label">Mantenimientos</span>
                </a>
                <a href="{{ route('electronicas.index') }}" class="nav-item {{ request()->routeIs('electronicas.*') && !request()->routeIs('electronicas.reportes') ? 'active' : '' }}" @if(request()->routeIs('electronicas.*') && !request()->routeIs('electronicas.reportes')) aria-current="page" @endif title="Electrónica">
                    <span class="nav-icon"><x-icon name="electronica" class="w-4 h-4" /></span>
                    <span class="nav-label">Electrónica</span>
                </a>
                <a href="{{ route('caja.index') }}" class="nav-item {{ request()->routeIs('caja.*') ? 'active' : '' }}" @if(request()->routeIs('caja.*')) aria-current="page" @endif title="Caja">
                    <span class="nav-icon"><x-icon name="caja" class="w-4 h-4" /></span>
                    <span class="nav-label">Caja</span>
                </a>
                <a href="{{ route('cierre.index') }}" class="nav-item {{ request()->routeIs('cierre.*') ? 'active' : '' }}" @if(request()->routeIs('cierre.*')) aria-current="page" @endif title="Arqueo / Cierre">
                    <span class="nav-icon"><x-icon name="cierre" class="w-4 h-4" /></span>
                    <span class="nav-label">Arqueo / Cierre</span>
                </a>
                <a href="{{ route('reportes.financiero.diario') }}" class="nav-item {{ request()->routeIs('reportes.*') || request()->routeIs('mantenimientos.reportes') || request()->routeIs('electronicas.reportes') || request()->routeIs('stocks.reportes') ? 'active' : '' }}" @if(request()->routeIs('reportes.*') || request()->routeIs('mantenimientos.reportes') || request()->routeIs('electronicas.reportes') || request()->routeIs('stocks.reportes')) aria-current="page" @endif title="Reportes">
                    <span class="nav-icon"><x-icon name="reportes" class="w-4 h-4" /></span>
                    <span class="nav-label">Reportes</span>
                </a>
                @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" @if(request()->routeIs('configuracion.*')) aria-current="page" @endif title="Empresa">
                    <span class="nav-icon"><x-icon name="empresa" class="w-4 h-4" /></span>
                    <span class="nav-label">Empresa</span>
                </a>
                <a href="{{ route('eventos.index') }}" class="nav-item {{ request()->routeIs('eventos.*') ? 'active' : '' }}" @if(request()->routeIs('eventos.*')) aria-current="page" @endif title="Eventos (Auditoría)">
                    <span class="nav-icon"><x-icon name="eventos" class="w-4 h-4" /></span>
                    <span class="nav-label">Eventos</span>
                </a>
                @endif
            </nav>
            
            </aside>
