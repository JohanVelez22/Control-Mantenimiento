@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3">
                <span class="text-4xl">🕵️</span> Registro de Eventos
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">
                Auditoría del sistema y control de movimientos
            </p>
        </div>
    </div>

    {{-- Filtros (Liquid Glass) --}}
    <div class="glass-card p-5">
        <form action="{{ route('eventos.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Módulo, observación..." class="glass-input">
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Acción</label>
                <select name="accion" class="glass-input no-search">
                    <option value="todas" {{ request('accion', 'todas') == 'todas' ? 'selected' : '' }}>Ver Todas</option>
                    <option value="login" {{ request('accion') == 'login' ? 'selected' : '' }}>Login</option>
                    <option value="logout" {{ request('accion') == 'logout' ? 'selected' : '' }}>Logout</option>
                    <option value="creado" {{ request('accion') == 'creado' ? 'selected' : '' }}>Creado</option>
                    <option value="actualizado" {{ request('accion') == 'actualizado' ? 'selected' : '' }}>Actualizado</option>
                    <option value="eliminado" {{ request('accion') == 'eliminado' ? 'selected' : '' }}>Eliminado</option>
                    <option value="anulado" {{ request('accion') == 'anulado' ? 'selected' : '' }}>Anulado</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Usuario</label>
                <select name="user_id" class="glass-input">
                    <option value="todos" {{ request('user_id', 'todos') == 'todos' ? 'selected' : '' }}>Ver Todos</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                    <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="glass-input">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                    <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="glass-input">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary px-4 py-2.5 font-bold">
                    🔍 Filtrar
                </button>
                <a href="{{ route('eventos.index') }}" class="btn-clean px-4 py-2.5 flex items-center justify-center font-bold text-sm">
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
                                    <button onclick="openDetalle({{ $evento->id }})" class="btn-clean px-2 py-1 text-xs font-bold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 rounded-md transition-colors">
                                        👁️ Ver
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
                                    <span class="text-5xl">🕵️</span>
                                    <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">No hay eventos</h3>
                                    <p class="text-gray-500 text-sm font-medium">No se encontraron registros de auditoría.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            {{ $eventos->links() }}
        </div>
    </div>
</div>

@push('modals')
{{-- Modal de Detalles de Cambios --}}
<div id="detalle-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
    <div class="ts-modal-card scale-95 opacity-0 max-w-4xl w-full" id="detalle-card">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b border-gray-200 dark:border-white/10 pb-4">
                <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <span class="text-2xl">📋</span> Detalles del Cambio
                </h3>
                <button type="button" onclick="closeDetalle()" class="text-gray-400 hover:text-red-500 transition-colors text-xl leading-none">✕</button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                {{-- Columna Anterior --}}
                <div class="bg-red-50/50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/30 overflow-hidden flex flex-col">
                    <div class="px-4 py-3 bg-red-100/50 dark:bg-red-900/30 border-b border-red-200 dark:border-red-900/50">
                        <h4 class="font-bold text-red-700 dark:text-red-400 text-sm uppercase tracking-wider flex items-center gap-2">
                            <span>➖</span> Antes
                        </h4>
                    </div>
                    <div class="p-4 flex-1">
                        <pre id="pre-ant" class="text-xs font-mono text-red-900 dark:text-red-300 whitespace-pre-wrap break-all"></pre>
                    </div>
                </div>

                {{-- Columna Nuevo --}}
                <div class="bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-900/30 overflow-hidden flex flex-col">
                    <div class="px-4 py-3 bg-emerald-100/50 dark:bg-emerald-900/30 border-b border-emerald-200 dark:border-emerald-900/50">
                        <h4 class="font-bold text-emerald-700 dark:text-emerald-400 text-sm uppercase tracking-wider flex items-center gap-2">
                            <span>➕</span> Después
                        </h4>
                    </div>
                    <div class="p-4 flex-1">
                        <pre id="pre-nue" class="text-xs font-mono text-emerald-900 dark:text-emerald-300 whitespace-pre-wrap break-all"></pre>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeDetalle()" class="btn-primary px-6 py-2">Cerrar</button>
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
            for(let key in obj) {
                // Ignore tokens or long hash strings if any
                if(key === 'remember_token' || key === 'password') continue;
                output += `<span class="font-bold text-gray-500 uppercase text-[10px] tracking-wider">${key}</span>\n`;
                output += `${obj[key]}\n\n`;
            }
            return output || 'Ninguno';
        } catch (e) {
            return jsonStr;
        }
    }

    function openDetalle(id) {
        const modal = document.getElementById('detalle-modal');
        const card = document.getElementById('detalle-card');
        
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
