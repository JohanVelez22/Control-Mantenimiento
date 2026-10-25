@extends('layouts.app')

@section('title', 'Liquid Glass Design System — Catálogo de Componentes')

@section('content')
<div class="space-y-8 max-w-7xl mx-auto pb-12">
    <!-- Encabezado Principal -->
    <div class="glass p-6 md:p-8 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4 border border-white/20 dark:border-white/5 shadow-xl">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400">
                    <x-icon name="dashboard" class="w-6 h-6" />
                </span>
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                    Liquid Glass Design System
                </h1>
            </div>
            <p class="text-sm md:text-base text-slate-600 dark:text-slate-400 mt-2 max-w-3xl">
                Catálogo maestro y guía viva de estilos para <strong>Tecni-Systemas</strong>. Base y arquitectura pionera para desarrollos Laravel de alta fidelidad estética.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                v2.0 Modular
            </span>
            <a href="{{ route('dashboard') }}" class="btn-ghost-secondary flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold">
                Volver al Panel
            </a>
        </div>
    </div>

    <!-- 1. Paleta de Colores y Tokens -->
    <div class="glass p-6 md:p-8 rounded-2xl border border-white/20 dark:border-white/5 space-y-4">
        <div class="border-b border-gray-200/40 dark:border-white/5 pb-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <x-icon name="zap" class="w-5 h-5 text-amber-500" />
                1. Tokens de Color Curados
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Variables declaradas en <code>resources/css/glass/_tokens.css</code>.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4 pt-2">
            <div class="p-4 rounded-xl bg-[#2563EB] text-white shadow-md flex flex-col justify-between h-28">
                <span class="text-xs font-medium opacity-80">--ts-blue</span>
                <div>
                    <p class="text-sm font-bold">Azul Eléctrico</p>
                    <p class="text-[11px] opacity-75">#2563EB</p>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-[#1D4ED8] text-white shadow-md flex flex-col justify-between h-28">
                <span class="text-xs font-medium opacity-80">--ts-blue-dark</span>
                <div>
                    <p class="text-sm font-bold">Azul Profundo</p>
                    <p class="text-[11px] opacity-75">#1D4ED8</p>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-[#06B6D4] text-white shadow-md flex flex-col justify-between h-28">
                <span class="text-xs font-medium opacity-80">--ts-cyan</span>
                <div>
                    <p class="text-sm font-bold">Cian Tecnológico</p>
                    <p class="text-[11px] opacity-75">#06B6D4</p>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-[#0F172A] text-white shadow-md flex flex-col justify-between h-28 border border-white/10">
                <span class="text-xs font-medium opacity-80">--ts-slate</span>
                <div>
                    <p class="text-sm font-bold">Azul Noche</p>
                    <p class="text-[11px] opacity-75">#0F172A</p>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-emerald-600 text-white shadow-md flex flex-col justify-between h-28">
                <span class="text-xs font-medium opacity-80">--ts-success</span>
                <div>
                    <p class="text-sm font-bold">Éxito / Activo</p>
                    <p class="text-[11px] opacity-75">#059669</p>
                </div>
            </div>
            <div class="p-4 rounded-xl bg-rose-600 text-white shadow-md flex flex-col justify-between h-28">
                <span class="text-xs font-medium opacity-80">--ts-danger</span>
                <div>
                    <p class="text-sm font-bold">Peligro / Anulado</p>
                    <p class="text-[11px] opacity-75">#E11D48</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Galería de Iconos Vectoriales (Componente x-icon) -->
    <div class="glass p-6 md:p-8 rounded-2xl border border-white/20 dark:border-white/5 space-y-4">
        <div class="border-b border-gray-200/40 dark:border-white/5 pb-3 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <x-icon name="tools" class="w-5 h-5 text-blue-500" />
                    2. Iconografía Vectorial SVG (Componente <code>x-icon</code>)
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Reemplaza emojis del sistema operativo para una visualización uniforme en Windows, Mac, Linux y móviles.</p>
            </div>
            <span class="text-xs font-mono bg-slate-100 dark:bg-white/5 px-2.5 py-1 rounded-md text-slate-600 dark:text-slate-300">15 Iconos Core</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 pt-2">
            @php
                $iconList = [
                    ['name' => 'dashboard', 'label' => 'Dashboard'],
                    ['name' => 'clientes', 'label' => 'Clientes'],
                    ['name' => 'equipos', 'label' => 'Equipos'],
                    ['name' => 'proveedores', 'label' => 'Proveedores'],
                    ['name' => 'tecnicos', 'label' => 'Técnicos'],
                    ['name' => 'stock', 'label' => 'Stock / Inventario'],
                    ['name' => 'operaciones', 'label' => 'Operaciones C/V'],
                    ['name' => 'cotizaciones', 'label' => 'Cotizaciones'],
                    ['name' => 'mantenimientos', 'label' => 'Mantenimientos'],
                    ['name' => 'electronica', 'label' => 'Electrónica'],
                    ['name' => 'caja', 'label' => 'Caja Chica'],
                    ['name' => 'cierre', 'label' => 'Arqueo / Cierre'],
                    ['name' => 'reportes', 'label' => 'Informes'],
                    ['name' => 'empresa', 'label' => 'Configuración'],
                    ['name' => 'eventos', 'label' => 'Auditoría / Eventos'],
                ];
            @endphp
            @foreach($iconList as $item)
                <div class="p-3 rounded-xl bg-white/40 dark:bg-white/5 border border-white/20 dark:border-white/5 flex items-center gap-3 hover:border-blue-500/40 transition-all">
                    <span class="p-2 rounded-lg bg-blue-600/10 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 shrink-0">
                        <x-icon name="{{ $item['name'] }}" class="w-5 h-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-800 dark:text-white truncate">{{ $item['label'] }}</p>
                        <p class="text-[10px] font-mono text-slate-500 dark:text-slate-400 truncate">name="{{ $item['name'] }}"</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- 3. Botones y Controles -->
    <div class="glass p-6 md:p-8 rounded-2xl border border-white/20 dark:border-white/5 space-y-4">
        <div class="border-b border-gray-200/40 dark:border-white/5 pb-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <x-icon name="operaciones" class="w-5 h-5 text-indigo-500" />
                3. Botones con Reflejo Especular Líquido
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Micro-animaciones con curvas cubic-bezier(0.4, 0, 0.2, 1) y brillo superior calibrado.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="button" class="btn-primary">
                <x-icon name="dashboard" class="w-4 h-4" />
                <span>.btn-primary</span>
            </button>
            <button type="button" class="btn-purple">
                <x-icon name="stock" class="w-4 h-4" />
                <span>.btn-purple</span>
            </button>
            <button type="button" class="btn-success">
                <x-icon name="clientes" class="w-4 h-4" />
                <span>.btn-success</span>
            </button>
            <button type="button" class="btn-clean">
                <x-icon name="tools" class="w-4 h-4" />
                <span>.btn-clean</span>
            </button>
            <button type="button" class="btn-danger">
                <x-icon name="cierre" class="w-4 h-4" />
                <span>.btn-danger</span>
            </button>
            <button type="button" class="btn-cancel">
                <span>.btn-cancel</span>
            </button>
        </div>
    </div>

    <!-- 4. Insignias Semánticas (.pill) -->
    <div class="glass p-6 md:p-8 rounded-2xl border border-white/20 dark:border-white/5 space-y-4">
        <div class="border-b border-gray-200/40 dark:border-white/5 pb-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <x-icon name="shield-check" class="w-5 h-5 text-emerald-500" />
                4. Insignias de Estado (.pill)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pills translúcidas con soporte completo en tema claro y oscuro.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <span class="pill pill-success font-bold text-xs px-3 py-1">● ACTIVO / PAGADO</span>
            <span class="pill pill-warning font-bold text-xs px-3 py-1">▲ PENDIENTE / PROCESO</span>
            <span class="pill pill-danger font-bold text-xs px-3 py-1">✖ ANULADO / BAJA</span>
            <span class="pill pill-info font-bold text-xs px-3 py-1">ℹ COTIZADO / INFO</span>
        </div>
    </div>

    <!-- 5. Componente x-table y Patrón de Datos -->
    <div class="glass p-6 md:p-8 rounded-2xl border border-white/20 dark:border-white/5 space-y-4">
        <div class="border-b border-gray-200/40 dark:border-white/5 pb-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <x-icon name="clipboard" class="w-5 h-5 text-cyan-500" />
                5. Tablas de Datos de Alta Densidad (Componente <code>x-table</code>)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Contenedor con desplazamiento horizontal fluido, cabecera semi-transparente y protección para móviles.</p>
        </div>
        
        <x-table id="demo-table">
            <thead>
                <tr>
                    <th class="text-left py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Código</th>
                    <th class="text-left py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Artículo / Módulo</th>
                    <th class="text-left py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Categoría</th>
                    <th class="text-center py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Estado</th>
                    <th class="text-right py-3 px-4 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Precio Unitario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200/30 dark:divide-white/5 text-sm">
                <tr class="hover:bg-blue-500/5 transition-colors">
                    <td class="py-3 px-4 font-mono font-semibold text-blue-600 dark:text-blue-400">REP-001</td>
                    <td class="py-3 px-4 font-medium text-slate-800 dark:text-white">Pantalla IPS 15.6 FHD 144Hz</td>
                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Display</td>
                    <td class="py-3 px-4 text-center"><span class="pill pill-success text-xs font-bold px-2 py-0.5">DISPONIBLE</span></td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 dark:text-white">$320.000</td>
                </tr>
                <tr class="hover:bg-blue-500/5 transition-colors">
                    <td class="py-3 px-4 font-mono font-semibold text-blue-600 dark:text-blue-400">REP-002</td>
                    <td class="py-3 px-4 font-medium text-slate-800 dark:text-white">Batería Litio OEM 56Wh Dell</td>
                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Energía</td>
                    <td class="py-3 px-4 text-center"><span class="pill pill-warning text-xs font-bold px-2 py-0.5">STOCK BAJO</span></td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 dark:text-white">$185.000</td>
                </tr>
                <tr class="hover:bg-blue-500/5 transition-colors">
                    <td class="py-3 px-4 font-mono font-semibold text-blue-600 dark:text-blue-400">SRV-104</td>
                    <td class="py-3 px-4 font-medium text-slate-800 dark:text-white">Mantenimiento Preventivo y Pasta Térmica</td>
                    <td class="py-3 px-4 text-slate-600 dark:text-slate-400">Servicio Técnico</td>
                    <td class="py-3 px-4 text-center"><span class="pill pill-info text-xs font-bold px-2 py-0.5">ESTÁNDAR</span></td>
                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 dark:text-white">$65.000</td>
                </tr>
            </tbody>
        </x-table>
    </div>

    <!-- 6. Componente x-modal Reutilizable -->
    <div class="glass p-6 md:p-8 rounded-2xl border border-white/20 dark:border-white/5 space-y-4">
        <div class="border-b border-gray-200/40 dark:border-white/5 pb-3">
            <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <x-icon name="cierre" class="w-5 h-5 text-purple-500" />
                6. Modales Accesibles ARIA (Componente <code>x-modal</code>)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Overlay translúcido, foco controlado, cierre con Escape y compatibilidad con formularios.</p>
        </div>
        <div>
            <button type="button" onclick="openModal('demo-ds-modal')" class="btn-primary">
                <x-icon name="cierre" class="w-4 h-4" />
                <span>Abrir Modal de Demostración</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Demostrativa -->
<x-modal 
    id="demo-ds-modal" 
    title="Ejemplo de Modal Liquid Glass" 
    icon="✨" 
    iconColor="blue" 
    size="max-w-lg"
    :actions="[
        ['label' => 'Cerrar', 'onclick' => 'closeModal(\'demo-ds-modal\')', 'class' => 'btn-cancel'],
        ['label' => 'Confirmar', 'onclick' => 'closeModal(\'demo-ds-modal\'); showToast(\'¡Acción confirmada desde el Design System!\', \'success\')', 'class' => 'btn-primary']
    ]"
>
    <p class="text-sm text-slate-600 dark:text-slate-300">
        Esta modal está construida con el componente reutilizable <code>x-modal</code>. Cumple con los estándares WCAG de accesibilidad (atributos <code>role="dialog"</code>, <code>aria-modal="true"</code>) y mantiene el efecto desenfoque nativo.
    </p>
</x-modal>
@endsection
