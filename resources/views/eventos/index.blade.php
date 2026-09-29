@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                <span class="text-4xl">🕵🏻</span> Registro de Eventos
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">
                Auditoría del sistema y control de movimientos
            </p>
        </div>
    </div>

    {{-- Filtros (Liquid Glass) --}}
    <div class="glass-card p-4 sm:p-5 relative z-50">
        <form action="{{ route('eventos.index') }}" method="GET" class="flex flex-wrap lg:flex-nowrap items-center gap-2.5 sm:gap-3">
            <div class="flex items-center gap-1.5 shrink-0">
                <label class="font-semibold text-xs sm:text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">Buscar:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Módulo..." class="glass-input w-28 sm:w-32 lg:w-36 text-sm">
            </div>
            
            <div class="flex items-center gap-1.5 shrink-0">
                <label class="font-semibold text-xs sm:text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">Acción:</label>
                <select name="accion" class="glass-input w-40 text-sm" data-placeholder="Ver Todas">
                    <option value="todas" {{ request('accion', 'todas') == 'todas' || request('accion') === '' ? 'selected' : '' }}>👁️ Ver Todas</option>
                    <option value="login" {{ request('accion') == 'login' ? 'selected' : '' }}>🔑 Login</option>
                    <option value="logout" {{ request('accion') == 'logout' ? 'selected' : '' }}>🚪 Logout</option>
                    <option value="backup" {{ request('accion') == 'backup' ? 'selected' : '' }}>💾 Backup</option>
                    <option value="creado" {{ request('accion') == 'creado' ? 'selected' : '' }}>✨ Creado</option>
                    <option value="actualizado" {{ request('accion') == 'actualizado' ? 'selected' : '' }}>✏️ Actualizado</option>
                    <option value="eliminado" {{ request('accion') == 'eliminado' ? 'selected' : '' }}>🗑️ Eliminado</option>
                    <option value="anulado" {{ request('accion') == 'anulado' ? 'selected' : '' }}>🚫 Anulado</option>
                </select>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <label class="font-semibold text-xs sm:text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">Usuario:</label>
                <select name="user_id" class="glass-input w-40 text-sm" data-placeholder="Ver Todos">
                    <option value="todos" {{ request('user_id', 'todos') == 'todos' || request('user_id') === '' ? 'selected' : '' }}>Ver Todos</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-1.5 shrink-0 eventos-filter-date">
                <label class="font-semibold text-xs sm:text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">Desde:</label>
                <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="glass-input w-32 sm:w-36 text-sm">
            </div>

            <div class="flex items-center gap-1.5 shrink-0 eventos-filter-date">
                <label class="font-semibold text-xs sm:text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">Hasta:</label>
                <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="glass-input w-32 sm:w-36 text-sm">
            </div>

            <div class="flex gap-2 shrink-0">
                <button type="submit" class="btn-primary px-3 sm:px-4 py-2 font-bold text-xs sm:text-sm whitespace-nowrap">
                    🌪️ Filtrar
                </button>
                <a href="{{ route('eventos.index') }}" class="btn-clean px-3 sm:px-4 py-2 flex items-center justify-center font-bold text-xs sm:text-sm whitespace-nowrap">
                    🧹 Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla de Resultados --}}
    <div class="glass-card p-6">
        <div class="overflow-x-auto pb-2">
            <table class="ts-table responsive-table w-full">
                <thead>
                    <tr>
                        <th class="text-left w-48">Fecha</th>
                        <th class="text-center w-32">Acción</th>
                        <th class="text-left">Descripción / Módulo</th>
                        <th class="text-center w-40">Usuario</th>
                        <th class="text-center w-24">Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eventos as $evento)
                        @php
                            $badgeClass = 'badge-evento-default';
                            $icon = '📌';
                            switch($evento->accion) {
                                case 'creado': 
                                    $badgeClass = 'badge-evento-creado';
                                    $icon = '✨'; 
                                    break;
                                case 'actualizado': 
                                    $badgeClass = 'badge-evento-actualizado';
                                    $icon = '✏️'; 
                                    break;
                                case 'eliminado': 
                                    $badgeClass = 'badge-evento-eliminado';
                                    $icon = '🗑️'; 
                                    break;
                                case 'anulado': 
                                    $badgeClass = 'badge-evento-anulado';
                                    $icon = '🚫'; 
                                    break;
                                case 'login': 
                                    $badgeClass = 'badge-evento-login';
                                    $icon = '🔑'; 
                                    break;
                                case 'logout': 
                                    $badgeClass = 'badge-evento-logout';
                                    $icon = '🚪'; 
                                    break;
                                case 'backup': 
                                    $badgeClass = 'badge-evento-backup';
                                    $icon = '💾'; 
                                    break;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td data-label="Fecha:" class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                {{ $evento->created_at->format('d/m/Y h:i A') }}
                            </td>
                            <td data-label="Acción:" class="text-center">
                                <span class="badge-evento {{ $badgeClass }}">
                                    <span class="text-base mb-0.5">{{ $icon }}</span>
                                    <span>{{ $evento->accion }}</span>
                                </span>
                            </td>
                            <td data-label="Descripción:" class="font-medium text-sm">
                                <div class="text-slate-800 dark:text-slate-100">{{ $evento->descripcion }}</div>
                                @if($evento->modelo_tipo)
                                    <div class="text-[11px] text-gray-500 font-mono mt-0.5">{{ class_basename($evento->modelo_tipo) }} #{{ $evento->modelo_id }}</div>
                                @endif
                            </td>
                            <td data-label="Usuario:" class="text-center">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300 flex items-center justify-center h-full">
                                    {{ $evento->user->name ?? 'Sistema' }}
                                </span>
                            </td>
                            <td data-label="Detalles:" class="text-center">
                                @if($evento->valores_antiguos || $evento->valores_nuevos)
                                    <button type="button" onclick="openDetalle({{ $evento->id }}, '{{ $evento->accion }}')" class="btn-ghost btn-action-view w-8 h-8 flex items-center justify-center p-0 text-xs text-indigo-600 dark:text-indigo-400 hover:bg-indigo-500/10 mx-auto" title="Ver Detalles">
                                        👁️
                                    </button>
                                    
                                    {{-- Data escondida para el modal --}}
                                    <div id="data-ant-{{ $evento->id }}" class="hidden">@json($evento->valores_antiguos)</div>
                                    <div id="data-nue-{{ $evento->id }}" class="hidden">@json($evento->valores_nuevos)</div>
                                    <div id="data-model-{{ $evento->id }}" class="hidden" data-model-type="{{ class_basename($evento->modelo_tipo) }}" data-model-id="{{ $evento->modelo_id }}" data-desc="{{ $evento->descripcion }}"></div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="text-5xl">🕵🏻</span>
                                    <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No hay eventos</h3>
                                    <p class="text-gray-500 text-sm font-medium">No se encontraron registros de auditoría.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($eventos->hasPages())
        <div class="mt-5 flex justify-end">
            {{ $eventos->links() }}
        </div>
        @endif
    </div>
</div>

@push('modals')
{{-- Modal de Detalles de Cambios / Trazabilidad Liquid Glass --}}
<div id="detalle-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300 z-[200]" onclick="if(event.target === this) closeDetalle()">
    <div class="event-modal-card scale-95 opacity-0 mx-auto shadow-2xl relative" id="detalle-card">
        {{-- Encabezado Fijo del Modal con Identidad Visual --}}
        <div class="event-modal-header">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div id="detalle-icon-wrap" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-500/15 dark:bg-indigo-400/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl sm:text-2xl shrink-0 !border-0 !border-none !outline-none !shadow-none" style="border: none !important; outline: none !important; box-shadow: none !important;">
                    <span id="detalle-icon" class="inline-flex items-center justify-center leading-none">📋</span>
                </div>
                <div class="min-w-0">
                    <h3 id="detalle-title" class="text-base sm:text-lg font-black text-slate-800 dark:text-white tracking-tight leading-snug truncate">
                        Detalles del Cambio
                    </h3>
                    <p id="detalle-subtitle" class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Auditoría del sistema y trazabilidad de operaciones
                    </p>
                </div>
            </div>
        </div>
        
        {{-- Cuerpo con Scroll Interno Independiente --}}
        <div class="event-modal-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 min-h-0">
                {{-- Columna Izquierda (Anterior / Archivos / Intentos) --}}
                <div id="col-ant-card" class="event-col-card event-col-rose">
                    <div id="col-ant-header" class="event-col-header">
                        <span id="col-ant-title" class="event-col-title"><span class="event-col-emoji">➖</span><span>Valores Anteriores</span></span>
                    </div>
                    <div class="event-col-content">
                        <div id="body-ant" class="space-y-2 flex-1"></div>
                    </div>
                </div>

                {{-- Columna Derecha (Nuevo / Parámetros / Acceso) --}}
                <div id="col-nue-card" class="event-col-card event-col-emerald">
                    <div id="col-nue-header" class="event-col-header">
                        <span id="col-nue-title" class="event-col-title"><span class="event-col-emoji">➕</span><span>Valores Nuevos</span></span>
                    </div>
                    <div class="event-col-content">
                        <div id="body-nue" class="space-y-2 flex-1"></div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Pie Fijo del Modal (Siempre visible, nunca cortado) --}}
        <div class="event-modal-footer">
            <button type="button" onclick="closeDetalle()" class="btn-primary px-6 py-2.5 font-bold text-sm shadow-md">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    const entityLookups = @json($lookups ?? []);

    function renderEventDataHtml(jsonStr, type, accion) {
        if (!jsonStr || jsonStr === 'null' || jsonStr === '""') {
            return renderEmptyState(type, accion);
        }

        let obj;
        try {
            obj = (typeof jsonStr === 'object') ? jsonStr : JSON.parse(jsonStr);
        } catch (e) {
            return `<div class="event-prop-item"><div class="event-prop-path">${escapeHtml(jsonStr)}</div></div>`;
        }

        const keys = Object.keys(obj || {});
        if (keys.length === 0) {
            return renderEmptyState(type, accion);
        }

        const monetaryKeys = ['costo', 'monto', 'precio', 'total', 'abono', 'saldo', 'valor'];
        let html = '<div class="space-y-2 w-full">';

        for (let key of keys) {
            if (key === 'remember_token' || key === 'password') continue;

            let val = obj[key];
            let lowerKey = key.toLowerCase();

            // Detección de Directorio / Archivo / Ruta
            const isPath = lowerKey.includes('directorio') || lowerKey.includes('archivo') || lowerKey.includes('ruta') || lowerKey.includes('path');

            // Formateo de valores monetarios
            const isMonetary = monetaryKeys.some(mk => lowerKey.includes(mk));
            if (isMonetary && val !== null && val !== '') {
                let num = parseFloat(val);
                if (!isNaN(num)) {
                    val = '$ ' + new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(num);
                }
            }

            // Formateo de fechas ISO
            let isDate = false;
            if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/i.test(val)) {
                val = val.replace('T', ' ').replace(/\.\d+(?:Z|[+-]\d{2}:\d{2})?$/i, '').replace(/(?:Z|[+-]\d{2}:\d{2})$/i, '');
                isDate = true;
            }

            // Resolución inteligente de IDs a nombres legibles
            let resolvedName = null;
            if (val !== null && val !== undefined && val !== '') {
                if (['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by'].includes(lowerKey)) {
                    resolvedName = entityLookups?.users?.[val] || null;
                } else if (lowerKey === 'cliente_id') {
                    resolvedName = entityLookups?.clientes?.[val] || null;
                } else if (lowerKey === 'proveedor_id') {
                    resolvedName = entityLookups?.proveedores?.[val] || null;
                } else if (lowerKey === 'tecnico_id') {
                    resolvedName = entityLookups?.tecnicos?.[val] || null;
                } else if (['categoria_id', 'categoria_stock_id'].includes(lowerKey)) {
                    resolvedName = entityLookups?.categorias?.[val] || null;
                } else if (lowerKey === 'equipo_id') {
                    resolvedName = entityLookups?.equipos?.[val] || null;
                } else if (['stock_id', 'producto_id', 'repuesto_id'].includes(lowerKey)) {
                    resolvedName = entityLookups?.stocks?.[val] || null;
                } else if (['concepto_id', 'concepto_caja_id'].includes(lowerKey)) {
                    resolvedName = entityLookups?.conceptos?.[val] || null;
                } else if (lowerKey === 'id' && (obj.nombre || obj.name || obj.producto || obj.descripcion)) {
                    resolvedName = obj.nombre || obj.name || obj.producto || obj.descripcion;
                }
            }

            // Renderizado del valor
            let valueHtml = '';
            if (resolvedName) {
                valueHtml = `
                    <div class="event-prop-val flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-800 dark:text-white">${escapeHtml(resolvedName)}</span>
                        <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400">ID #${escapeHtml(String(val))}</span>
                    </div>
                `;
            } else if (isPath && typeof val === 'string') {
                let cleanPath = val;
                if (/^[a-zA-Z]:\\/i.test(cleanPath)) {
                    cleanPath = cleanPath.replace(/\//g, '\\');
                }
                valueHtml = `<div class="event-prop-path">${escapeHtml(cleanPath)}</div>`;
            } else if (typeof val === 'string' && val.startsWith('✅')) {
                valueHtml = `<span class="event-prop-success">${escapeHtml(val)}</span>`;
            } else if (typeof val === 'string' && val.startsWith('❌')) {
                valueHtml = `<span class="event-prop-error">${escapeHtml(val)}</span>`;
            } else if (val === true || val === 'true' || (val === 1 && ['bloqueado', 'active', 'activo', 'anulado'].includes(lowerKey))) {
                let text = (lowerKey === 'active' || lowerKey === 'activo') ? 'Activo' : 'Sí';
                valueHtml = `<div class="event-prop-val">${text}</div>`;
            } else if (val === false || val === 'false' || (val === 0 && ['bloqueado', 'active', 'activo', 'anulado'].includes(lowerKey))) {
                let text = (lowerKey === 'active' || lowerKey === 'activo') ? 'Inactivo' : 'No';
                valueHtml = `<div class="event-prop-val">${text}</div>`;
            } else if (val === null || val === undefined || val === '') {
                valueHtml = `<span class="text-xs text-slate-400 italic">No especificado</span>`;
            } else {
                valueHtml = `<div class="event-prop-val">${escapeHtml(String(val))}</div>`;
            }

            // Ícono contextual
            let icon = '🏷️';
            if (isPath) icon = '📁';
            else if (['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by', 'ejecutado', 'por'].some(k => lowerKey.includes(k))) icon = '👤';
            else if (lowerKey.includes('cliente')) icon = '👥';
            else if (lowerKey.includes('proveedor')) icon = '🏢';
            else if (lowerKey.includes('tecnico')) icon = '🔧';
            else if (lowerKey.includes('equipo')) icon = '💻';
            else if (lowerKey.includes('bloqueado')) icon = '🔒';
            else if (isDate || lowerKey.includes('fecha') || lowerKey.includes('hora')) icon = '🕒';
            else if (lowerKey.includes('resultado') || lowerKey.includes('estado')) icon = '⚡';
            else if (lowerKey.includes('nube') || lowerKey.includes('sincroniz')) icon = '☁️';
            else if (lowerKey.includes('retención') || lowerKey.includes('días')) icon = '⏳';
            else if (lowerKey.includes('tamaño') || lowerKey.includes('peso') || lowerKey.includes('kb')) icon = '⚖️';
            else if (lowerKey.includes('tipo') || lowerKey.includes('categoria')) icon = '📌';
            else if (lowerKey.includes('ip')) icon = '🌐';
            else if (lowerKey.includes('intento')) icon = '⚠️';
            else if (isMonetary) icon = '💵';

            html += `
                <div class="event-prop-item">
                    <div class="event-prop-key">
                        <span class="event-prop-key-icon">${icon}</span>
                        <span>${escapeHtml(key)}</span>
                    </div>
                    ${valueHtml}
                </div>
            `;
        }

        html += '</div>';
        return html;
    }

    function renderEmptyState(type, accion) {
        let icon = 'ℹ️';
        let title = 'Sin valores registrados';
        let msg = 'No hay información adicional registrada para este bloque.';

        if (type === 'ant') {
            if (accion === 'creado') {
                icon = '✨';
                title = 'Registro Inicial';
                msg = 'Este registro fue creado directamente en el sistema, por lo que no cuenta con valores anteriores.';
            } else if (accion === 'login') {
                icon = '🛡️';
                title = 'Sin Intentos Fallidos';
                msg = 'El inicio de sesión fue exitoso al primer intento sin bloqueos previos.';
            }
        } else {
            if (accion === 'eliminado') {
                icon = '🗑️';
                title = 'Registro Eliminado';
                msg = 'El registro fue dado de baja o removido permanentemente del sistema.';
            }
        }

        return `
            <div class="event-empty-box">
                <div class="event-empty-icon">
                    <span>${icon}</span>
                </div>
                <span class="event-empty-title">${title}</span>
                <p class="event-empty-desc">${msg}</p>
            </div>
        `;
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function openDetalle(id, accion) {
        const modal = document.getElementById('detalle-modal');
        const card = document.getElementById('detalle-card');
        
        const titleEl = document.getElementById('detalle-title');
        const subtitleEl = document.getElementById('detalle-subtitle');
        const iconEl = document.getElementById('detalle-icon');
        const iconWrap = document.getElementById('detalle-icon-wrap');
        const colAntTitle = document.getElementById('col-ant-title');
        const colNueTitle = document.getElementById('col-nue-title');

        const colAntCard = document.getElementById('col-ant-card');
        const colNueCard = document.getElementById('col-nue-card');

        const modelEl = document.getElementById('data-model-' + id);
        const modelType = modelEl?.getAttribute('data-model-type') || '';
        const modelId = modelEl?.getAttribute('data-model-id') || '';
        const modelDesc = modelEl?.getAttribute('data-desc') || '';

        if (accion === 'backup') {
            if (iconEl) iconEl.textContent = '💾';
            if (iconWrap) {
                iconWrap.className = 'w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-cyan-500/15 dark:bg-cyan-400/20 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xl sm:text-2xl shrink-0 !border-0 !border-none !outline-none !shadow-none';
                iconWrap.style.border = 'none';
                iconWrap.style.outline = 'none';
                iconWrap.style.boxShadow = 'none';
            }
            if (titleEl) titleEl.textContent = 'Trazabilidad de Respaldo / Copia de Seguridad';
            if (subtitleEl) subtitleEl.textContent = 'Detalle de la copia de seguridad, archivos generados y retención';
            
            if (colAntTitle) colAntTitle.innerHTML = '<span class="event-col-emoji">📦</span><span>Archivos y Almacenamiento</span>';
            if (colNueTitle) colNueTitle.innerHTML = '<span class="event-col-emoji">✅</span><span>Estado y Parámetros</span>';
            
            if (colAntCard) colAntCard.className = 'event-col-card event-col-cyan';
            if (colNueCard) colNueCard.className = 'event-col-card event-col-emerald';
        } else if (accion === 'login') {
            if (iconEl) iconEl.textContent = '🔐';
            if (iconWrap) {
                iconWrap.className = 'w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-amber-500/15 dark:bg-amber-400/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl sm:text-2xl shrink-0 !border-0 !border-none !outline-none !shadow-none';
                iconWrap.style.border = 'none';
                iconWrap.style.outline = 'none';
                iconWrap.style.boxShadow = 'none';
            }
            if (titleEl) titleEl.textContent = 'Trazabilidad de Acceso / Intentos de Login';
            if (subtitleEl) subtitleEl.textContent = 'Historial de verificación de seguridad e intentos registrados';
            
            if (colAntTitle) colAntTitle.innerHTML = '<span class="event-col-emoji">⚠️</span><span>Historial de Intentos Previos</span>';
            if (colNueTitle) colNueTitle.innerHTML = '<span class="event-col-emoji">✅</span><span>Acceso Logrado / Exitoso</span>';
            
            if (colAntCard) colAntCard.className = 'event-col-card event-col-amber';
            if (colNueCard) colNueCard.className = 'event-col-card event-col-emerald';
        } else {
            if (iconEl) iconEl.textContent = '📋';
            if (iconWrap) {
                iconWrap.className = 'w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-indigo-500/15 dark:bg-indigo-400/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl sm:text-2xl shrink-0 !border-0 !border-none !outline-none !shadow-none';
                iconWrap.style.border = 'none';
                iconWrap.style.outline = 'none';
                iconWrap.style.boxShadow = 'none';
            }
            
            if (modelType) {
                if (titleEl) titleEl.textContent = `Detalles del Cambio — ${modelType} ${modelId ? '#' + modelId : ''}`;
                if (subtitleEl) subtitleEl.textContent = modelDesc || 'Comparativa de datos entre el estado previo y el nuevo estado';
            } else {
                if (titleEl) titleEl.textContent = 'Detalles del Cambio';
                if (subtitleEl) subtitleEl.textContent = 'Comparativa de datos entre el estado previo y el nuevo estado';
            }
            
            if (colAntTitle) colAntTitle.innerHTML = '<span class="event-col-emoji">➖</span><span>Valores Anteriores</span>';
            if (colNueTitle) colNueTitle.innerHTML = '<span class="event-col-emoji">➕</span><span>Valores Nuevos</span>';
            
            if (colAntCard) colAntCard.className = 'event-col-card event-col-rose';
            if (colNueCard) colNueCard.className = 'event-col-card event-col-emerald';
        }

        const dataAnt = document.getElementById('data-ant-' + id)?.innerText || '';
        const dataNue = document.getElementById('data-nue-' + id)?.innerText || '';
        
        document.getElementById('body-ant').innerHTML = renderEventDataHtml(dataAnt, 'ant', accion);
        document.getElementById('body-nue').innerHTML = renderEventDataHtml(dataNue, 'nue', accion);
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
        }, 10);
    }

    function closeDetalle() {
        const modal = document.getElementById('detalle-modal');
        const card = document.getElementById('detalle-card');
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('detalle-modal');
            if (modal && !modal.classList.contains('hidden')) {
                closeDetalle();
            }
        }
    });
</script>
@endpush

@endsection
