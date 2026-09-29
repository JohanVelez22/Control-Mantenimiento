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

            <div class="flex items-center gap-1.5 shrink-0">
                <label class="font-semibold text-xs sm:text-sm whitespace-nowrap text-gray-700 dark:text-gray-300">Desde:</label>
                <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="glass-input w-32 sm:w-36 text-sm">
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
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
                            $badgeClass = 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
                            $icon = '📌';
                            switch($evento->accion) {
                                case 'creado': 
                                    $badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300';
                                    $icon = '✨'; 
                                    break;
                                case 'actualizado': 
                                    $badgeClass = 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/40 dark:text-blue-300';
                                    $icon = '✏️'; 
                                    break;
                                case 'eliminado': 
                                    $badgeClass = 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/40 dark:text-red-300';
                                    $icon = '🗑️'; 
                                    break;
                                case 'anulado': 
                                    $badgeClass = 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/40 dark:text-orange-300';
                                    $icon = '🚫'; 
                                    break;
                                case 'login': 
                                    $badgeClass = 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/40 dark:text-purple-300';
                                    $icon = '🔑'; 
                                    break;
                                case 'logout': 
                                    $badgeClass = 'bg-slate-200 text-slate-800 border-slate-300 dark:bg-slate-800 dark:text-slate-300';
                                    $icon = '🚪'; 
                                    break;
                                case 'backup': 
                                    $badgeClass = 'bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-900/40 dark:text-teal-300';
                                    $icon = '💾'; 
                                    break;
                            }
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td data-label="Fecha:" class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                {{ $evento->created_at->format('d/m/Y h:i A') }}
                            </td>
                            <td data-label="Acción:" class="text-center">
                                <span class="inline-flex flex-col items-center justify-center px-2 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider border w-24 leading-tight {{ $badgeClass }}">
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
{{-- Modal de Detalles de Cambios / Trazabilidad --}}
<div id="detalle-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300 z-[200]" onclick="if(event.target === this) closeDetalle()">
    <div class="ts-modal-card scale-95 opacity-0 max-w-4xl w-full" id="detalle-card">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b border-gray-200 dark:border-white/10 pb-4">
                <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <span id="detalle-icon" class="text-2xl">📋</span>
                    <span id="detalle-title">Detalles del Cambio</span>
                </h3>
                <button type="button" onclick="closeDetalle()" class="text-gray-400 hover:text-red-500 transition-colors text-xl leading-none">✕</button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                {{-- Columna Anterior / Intentos Fallidos / Archivos --}}
                <div id="col-ant-card" class="bg-red-50/50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/30 overflow-hidden flex flex-col transition-colors">
                    <div id="col-ant-header" class="px-4 py-3 bg-red-100/50 dark:bg-red-900/30 border-b border-red-200 dark:border-red-900/50">
                        <h4 id="col-ant-title" class="font-bold text-red-700 dark:text-red-400 text-sm uppercase tracking-wider flex items-center gap-2">
                            <span>➖</span> Antes
                        </h4>
                    </div>
                    <div class="p-4 flex-1">
                        <pre id="pre-ant" class="text-xs font-mono text-slate-800 dark:text-slate-200 whitespace-pre-wrap break-words leading-relaxed"></pre>
                    </div>
                </div>

                {{-- Columna Nuevo / Acceso Exitoso / Parámetros --}}
                <div id="col-nue-card" class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-900/30 overflow-hidden flex flex-col transition-colors">
                    <div id="col-nue-header" class="px-4 py-3 bg-emerald-100/50 dark:bg-emerald-900/30 border-b border-emerald-200 dark:border-emerald-900/50">
                        <h4 id="col-nue-title" class="font-bold text-emerald-700 dark:text-emerald-400 text-sm uppercase tracking-wider flex items-center gap-2">
                            <span>➕</span> Después
                        </h4>
                    </div>
                    <div class="p-4 flex-1">
                        <pre id="pre-nue" class="text-xs font-mono text-slate-800 dark:text-slate-200 whitespace-pre-wrap break-words leading-relaxed"></pre>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeDetalle()" class="btn-primary px-6 py-2 font-bold text-sm">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function formatJsonToReadable(jsonStr) {
        if (!jsonStr || jsonStr === 'null') return 'Ninguno';
        try {
            const obj = JSON.parse(jsonStr);
            if(Object.keys(obj).length === 0) return 'Sin cambios relevantes';
            
            let output = '';
            const monetaryKeys = ['costo', 'monto', 'precio', 'total', 'abono', 'saldo', 'valor'];
            
            for(let key in obj) {
                // Ignora tokens o cadenas hash largas si existen
                if(key === 'remember_token' || key === 'password') continue;
                
                let val = obj[key];
                
                // Formatear valores monetarios (sin decimales, con puntos)
                const isMonetary = monetaryKeys.some(mk => key.toLowerCase().includes(mk));
                if (isMonetary && val !== null && val !== '') {
                    let num = parseFloat(val);
                    if (!isNaN(num)) {
                        val = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(num);
                    }
                }

                // Formatear fechas y horas ISO (eliminar microsegundos / .000000Z y 'T' para que quede horizontal)
                let isDate = false;
                if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/i.test(val)) {
                    val = val.replace('T', ' ').replace(/\.\d+(?:Z|[+-]\d{2}:\d{2})?$/i, '').replace(/(?:Z|[+-]\d{2}:\d{2})$/i, '');
                    isDate = true;
                }
                
                output += `<span class="font-bold text-gray-500 uppercase text-[10px] tracking-wider">${key}</span>\n`;
                if (isDate) {
                    output += `<span class="inline-block whitespace-nowrap font-mono">${val}</span>\n\n`;
                } else {
                    output += `${val}\n\n`;
                }
            }
            return output || 'Ninguno';
        } catch (e) {
            return jsonStr;
        }
    }

    function openDetalle(id, accion) {
        const modal = document.getElementById('detalle-modal');
        const card = document.getElementById('detalle-card');
        
        const titleEl = document.getElementById('detalle-title');
        const iconEl = document.getElementById('detalle-icon');
        const colAntTitle = document.getElementById('col-ant-title');
        const colNueTitle = document.getElementById('col-nue-title');

        const colAntCard = document.getElementById('col-ant-card');
        const colAntHeader = document.getElementById('col-ant-header');
        const colNueCard = document.getElementById('col-nue-card');
        const colNueHeader = document.getElementById('col-nue-header');

        if (accion === 'login') {
            if (iconEl) iconEl.textContent = '🔐';
            if (titleEl) titleEl.textContent = 'Trazabilidad de Acceso / Intentos de Login';
            if (colAntTitle) colAntTitle.innerHTML = '<span>⚠️</span> Historial de Intentos Previos';
            if (colNueTitle) colNueTitle.innerHTML = '<span>✅</span> Acceso Logrado / Exitoso';
            if (colAntCard) colAntCard.className = 'bg-amber-50/50 dark:bg-amber-900/10 rounded-xl border border-amber-200 dark:border-amber-900/30 overflow-hidden flex flex-col transition-colors';
            if (colAntHeader) colAntHeader.className = 'px-4 py-3 bg-amber-100/60 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-900/50';
            if (colAntTitle) colAntTitle.className = 'font-bold text-amber-800 dark:text-amber-400 text-sm uppercase tracking-wider flex items-center gap-2';
            if (colNueCard) colNueCard.className = 'bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-900/30 overflow-hidden flex flex-col transition-colors';
            if (colNueHeader) colNueHeader.className = 'px-4 py-3 bg-emerald-100/50 dark:bg-emerald-900/30 border-b border-emerald-200 dark:border-emerald-900/50';
        } else if (accion === 'backup') {
            if (iconEl) iconEl.textContent = '💾';
            if (titleEl) titleEl.textContent = 'Trazabilidad de Respaldo / Copia de Seguridad';
            if (colAntTitle) colAntTitle.innerHTML = '<span>📦</span> Archivos y Almacenamiento';
            if (colNueTitle) colNueTitle.innerHTML = '<span>✅</span> Estado y Parámetros';
            if (colAntCard) colAntCard.className = 'bg-slate-50/60 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col transition-colors';
            if (colAntHeader) colAntHeader.className = 'px-4 py-3 bg-slate-100 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700';
            if (colAntTitle) colAntTitle.className = 'font-bold text-slate-700 dark:text-slate-300 text-sm uppercase tracking-wider flex items-center gap-2';
            if (colNueCard) colNueCard.className = 'bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-900/30 overflow-hidden flex flex-col transition-colors';
            if (colNueHeader) colNueHeader.className = 'px-4 py-3 bg-emerald-100/50 dark:bg-emerald-900/30 border-b border-emerald-200 dark:border-emerald-900/50';
        } else {
            if (iconEl) iconEl.textContent = '📋';
            if (titleEl) titleEl.textContent = 'Detalles del Cambio';
            if (colAntTitle) colAntTitle.innerHTML = '<span>➖</span> Antes';
            if (colNueTitle) colNueTitle.innerHTML = '<span>➕</span> Después';
            if (colAntCard) colAntCard.className = 'bg-red-50/50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/30 overflow-hidden flex flex-col transition-colors';
            if (colAntHeader) colAntHeader.className = 'px-4 py-3 bg-red-100/50 dark:bg-red-900/30 border-b border-red-200 dark:border-red-900/50';
            if (colAntTitle) colAntTitle.className = 'font-bold text-red-700 dark:text-red-400 text-sm uppercase tracking-wider flex items-center gap-2';
            if (colNueCard) colNueCard.className = 'bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-900/30 overflow-hidden flex flex-col transition-colors';
            if (colNueHeader) colNueHeader.className = 'px-4 py-3 bg-emerald-100/50 dark:bg-emerald-900/30 border-b border-emerald-200 dark:border-emerald-900/50';
        }

        const dataAnt = document.getElementById('data-ant-' + id).innerText;
        const dataNue = document.getElementById('data-nue-' + id).innerText;
        
        document.getElementById('pre-ant').innerHTML = formatJsonToReadable(dataAnt);
        document.getElementById('pre-nue').innerHTML = formatJsonToReadable(dataNue);
        
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
</script>
@endpush

@endsection
