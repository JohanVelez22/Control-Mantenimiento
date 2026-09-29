@extends('layouts.app')

@section('title', 'Detalle de Evento #' . $evento->id)

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    {{-- Encabezado con Botón de Regreso --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center gap-3.5">
                <a href="{{ route('eventos.index') }}" class="btn-ghost w-9 h-9 p-0 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:hover:text-white" title="Volver a Eventos">
                    ←
                </a>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white flex items-center gap-3.5">
                    <span class="text-2xl sm:text-3xl">🕵🏻</span> Detalle de Evento #{{ $evento->id }}
                </h1>
            </div>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium ml-12">
                Registro de auditoría y trazabilidad en {{ $evento->created_at?->format('d/m/Y h:i A') }}
            </p>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            @php
                $badgeClass = 'badge-evento-' . $evento->accion;
                $icon = match($evento->accion) {
                    'creado' => '✨',
                    'actualizado' => '✏️',
                    'eliminado' => '🗑️',
                    'anulado' => '🚫',
                    'login' => '🔑',
                    'logout' => '🚪',
                    'backup' => '💾',
                    default => '📌',
                };
            @endphp
            <span class="badge-evento {{ $badgeClass }}">
                <span class="badge-evento-icon">{{ $icon }}</span>
                <span>{{ $evento->accion }}</span>
            </span>
        </div>
    </div>

    {{-- Tarjeta Principal Informativa --}}
    <div class="glass-card p-6 space-y-6">
        {{-- Metadata Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/60 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200/60 dark:border-white/5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mb-1">
                    <span>👤</span> Usuario Responsable
                </span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">
                    {{ $evento->user->name ?? 'Sistema' }}
                </span>
            </div>

            <div class="bg-white/60 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200/60 dark:border-white/5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mb-1">
                    <span>📦</span> Módulo / Modelo
                </span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-mono">
                    {{ class_basename($evento->modelo_tipo) ?: 'General / Sistema' }}
                    @if($evento->modelo_id)
                        #{{ $evento->modelo_id }}
                    @endif
                </span>
            </div>

            <div class="bg-white/60 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200/60 dark:border-white/5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mb-1">
                    <span>🌐</span> Dirección IP
                </span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-mono">
                    {{ $evento->ip_direccion ?: '127.0.0.1' }}
                </span>
            </div>

            <div class="bg-white/60 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200/60 dark:border-white/5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mb-1">
                    <span>🕒</span> Fecha y Hora
                </span>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100">
                    {{ $evento->created_at?->format('d/m/Y h:i:s A') }}
                </span>
            </div>
        </div>

        {{-- Descripción del Movimiento --}}
        <div class="bg-white/80 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-200/80 dark:border-white/10">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mb-1">
                <span>📝</span> Descripción del Evento
            </span>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                {{ $evento->descripcion }}
            </p>
        </div>

        {{-- Grid Comparativo de Valores (Simétrico a Modal) --}}
        @php
            $isBackup = $evento->accion === 'backup';
            $isLogin = $evento->accion === 'login';
            
            $colAntEmoji = $isBackup ? '📦' : ($isLogin ? '⚠️' : '➖');
            $colAntText = $isBackup ? 'Archivos y Almacenamiento' : ($isLogin ? 'Historial de Intentos Previos' : 'Valores Anteriores');

            $colNueEmoji = $isBackup ? '✅' : ($isLogin ? '✅' : '➕');
            $colNueText = $isBackup ? 'Estado y Parámetros' : ($isLogin ? 'Acceso Logrado / Exitoso' : 'Valores Nuevos');

            $colAntClass = $isBackup ? 'event-col-cyan' : ($isLogin ? 'event-col-amber' : 'event-col-rose');
            $colNueClass = 'event-col-emerald';
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Columna Anterior --}}
            <div class="event-col-card {{ $colAntClass }}">
                <div class="event-col-header">
                    <span class="event-col-title"><span class="event-col-emoji">{{ $colAntEmoji }}</span><span>{{ $colAntText }}</span></span>
                </div>
                <div class="event-col-content">
                    @if(!empty($viejos) && is_array($viejos))
                        @foreach($viejos as $k => $v)
                            @php
                                $lowerK = strtolower($k);
                                $isPath = str_contains($lowerK, 'directorio') || str_contains($lowerK, 'archivo') || str_contains($lowerK, 'ruta') || str_contains($lowerK, 'path');
                                if ($isPath && is_string($v) && preg_match('/^[a-zA-Z]:\\\\/i', $v)) {
                                    $v = str_replace('/', '\\', $v);
                                }
                                
                                $resolvedName = null;
                                if ($v !== null && $v !== '' && !is_array($v)) {
                                    if (in_array($lowerK, ['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by'])) {
                                        $resolvedName = $lookups['users'][$v] ?? null;
                                    } elseif ($lowerK === 'cliente_id') {
                                        $resolvedName = $lookups['clientes'][$v] ?? null;
                                    } elseif ($lowerK === 'proveedor_id') {
                                        $resolvedName = $lookups['proveedores'][$v] ?? null;
                                    } elseif ($lowerK === 'tecnico_id') {
                                        $resolvedName = $lookups['tecnicos'][$v] ?? null;
                                    } elseif (in_array($lowerK, ['categoria_id', 'categoria_stock_id'])) {
                                        $resolvedName = $lookups['categorias'][$v] ?? null;
                                    } elseif ($lowerK === 'equipo_id') {
                                        $resolvedName = $lookups['equipos'][$v] ?? null;
                                    } elseif (in_array($lowerK, ['stock_id', 'producto_id', 'repuesto_id'])) {
                                        $resolvedName = $lookups['stocks'][$v] ?? null;
                                    } elseif (in_array($lowerK, ['concepto_id', 'concepto_caja_id'])) {
                                        $resolvedName = $lookups['conceptos'][$v] ?? null;
                                    } elseif ($lowerK === 'id' && (isset($viejos['nombre']) || isset($viejos['name']) || isset($viejos['producto']))) {
                                        $resolvedName = $viejos['nombre'] ?? $viejos['name'] ?? $viejos['producto'] ?? null;
                                    }
                                }

                                $icon = '🏷️';
                                if ($isPath) $icon = '📁';
                                elseif (in_array($lowerK, ['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by'])) $icon = '👤';
                                elseif (str_contains($lowerK, 'cliente')) $icon = '👥';
                                elseif (str_contains($lowerK, 'proveedor')) $icon = '🏢';
                                elseif (str_contains($lowerK, 'tecnico')) $icon = '🔧';
                                elseif (str_contains($lowerK, 'equipo')) $icon = '💻';
                                elseif (str_contains($lowerK, 'bloqueado')) $icon = '🔒';
                                elseif (str_contains($lowerK, 'fecha') || str_contains($lowerK, 'hora')) $icon = '🕒';

                                $isBlocked = str_contains($lowerK, 'bloqueado');
                            @endphp
                            <div class="event-prop-item">
                                <div class="event-prop-key">
                                    <span class="event-prop-key-icon">{{ $icon }}</span>
                                    <span>{{ $k }}</span>
                                </div>
                                @if($resolvedName)
                                    <div class="event-prop-val flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-800 dark:text-white">{{ $resolvedName }}</span>
                                        <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400">ID #{{ $v }}</span>
                                    </div>
                                @elseif($isPath)
                                    <div class="event-prop-path">
                                        {{ $v }}
                                    </div>
                                @elseif($isBlocked && ($v === true || $v === 1 || $v === '1' || $v === 'true'))
                                    <div class="event-prop-val">Sí</div>
                                @elseif($isBlocked && ($v === false || $v === 0 || $v === '0' || $v === 'false'))
                                    <div class="event-prop-val">No</div>
                                @else
                                    <div class="event-prop-val">
                                        {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="event-empty-box">
                            <div class="event-empty-icon">
                                <span>✨</span>
                            </div>
                            <span class="event-empty-title">Sin valores anteriores</span>
                            <p class="event-empty-desc">
                                {{ $evento->accion === 'creado' ? 'Este registro fue creado directamente en el sistema.' : 'No se registraron cambios previos.' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Columna Nueva --}}
            <div class="event-col-card {{ $colNueClass }}">
                <div class="event-col-header">
                    <span class="event-col-title"><span class="event-col-emoji">{{ $colNueEmoji }}</span><span>{{ $colNueText }}</span></span>
                </div>
                <div class="event-col-content">
                    @if(!empty($nuevos) && is_array($nuevos))
                        @foreach($nuevos as $k => $v)
                            @php
                                $lowerK = strtolower($k);
                                $isPath = str_contains($lowerK, 'directorio') || str_contains($lowerK, 'archivo') || str_contains($lowerK, 'ruta') || str_contains($lowerK, 'path');
                                if ($isPath && is_string($v) && preg_match('/^[a-zA-Z]:\\\\/i', $v)) {
                                    $v = str_replace('/', '\\', $v);
                                }

                                $resolvedName = null;
                                if ($v !== null && $v !== '' && !is_array($v)) {
                                    if (in_array($lowerK, ['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by'])) {
                                        $resolvedName = $lookups['users'][$v] ?? null;
                                    } elseif ($lowerK === 'cliente_id') {
                                        $resolvedName = $lookups['clientes'][$v] ?? null;
                                    } elseif ($lowerK === 'proveedor_id') {
                                        $resolvedName = $lookups['proveedores'][$v] ?? null;
                                    } elseif ($lowerK === 'tecnico_id') {
                                        $resolvedName = $lookups['tecnicos'][$v] ?? null;
                                    } elseif (in_array($lowerK, ['categoria_id', 'categoria_stock_id'])) {
                                        $resolvedName = $lookups['categorias'][$v] ?? null;
                                    } elseif ($lowerK === 'equipo_id') {
                                        $resolvedName = $lookups['equipos'][$v] ?? null;
                                    } elseif (in_array($lowerK, ['stock_id', 'producto_id', 'repuesto_id'])) {
                                        $resolvedName = $lookups['stocks'][$v] ?? null;
                                    } elseif (in_array($lowerK, ['concepto_id', 'concepto_caja_id'])) {
                                        $resolvedName = $lookups['conceptos'][$v] ?? null;
                                    } elseif ($lowerK === 'id' && (isset($nuevos['nombre']) || isset($nuevos['name']) || isset($nuevos['producto']))) {
                                        $resolvedName = $nuevos['nombre'] ?? $nuevos['name'] ?? $nuevos['producto'] ?? null;
                                    }
                                }

                                $valStr = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string)$v;
                                $isSuccess = str_starts_with($valStr, '✅');
                                $isError = str_starts_with($valStr, '❌');
                                $isBlocked = str_contains($lowerK, 'bloqueado');

                                $icon = '🏷️';
                                if ($isPath) $icon = '📁';
                                elseif (in_array($lowerK, ['user_id', 'usuario_id', 'baja_user_id', 'created_by', 'updated_by'])) $icon = '👤';
                                elseif (str_contains($lowerK, 'cliente')) $icon = '👥';
                                elseif (str_contains($lowerK, 'proveedor')) $icon = '🏢';
                                elseif (str_contains($lowerK, 'tecnico')) $icon = '🔧';
                                elseif (str_contains($lowerK, 'equipo')) $icon = '💻';
                                elseif ($isBlocked) $icon = '🔒';
                                elseif (str_contains($lowerK, 'fecha') || str_contains($lowerK, 'hora')) $icon = '🕒';
                            @endphp
                            <div class="event-prop-item">
                                <div class="event-prop-key">
                                    <span class="event-prop-key-icon">{{ $icon }}</span>
                                    <span>{{ $k }}</span>
                                </div>
                                @if($resolvedName)
                                    <div class="event-prop-val flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-800 dark:text-white">{{ $resolvedName }}</span>
                                        <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400">ID #{{ $v }}</span>
                                    </div>
                                @elseif($isPath)
                                    <div class="event-prop-path">
                                        {{ $v }}
                                    </div>
                                @elseif($isSuccess)
                                    <span class="event-prop-success">
                                        {{ $valStr }}
                                    </span>
                                @elseif($isError)
                                    <span class="event-prop-error">
                                        {{ $valStr }}
                                    </span>
                                @elseif($isBlocked && ($v === true || $v === 1 || $v === '1' || $v === 'true'))
                                    <div class="event-prop-val">Sí</div>
                                @elseif($isBlocked && ($v === false || $v === 0 || $v === '0' || $v === 'false'))
                                    <div class="event-prop-val">No</div>
                                @else
                                    <div class="event-prop-val">
                                        {{ $valStr }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="event-empty-box">
                            <span class="text-3xl mb-1.5 opacity-60">🗑️</span>
                            <span class="event-empty-title">Sin valores nuevos</span>
                            <p class="event-empty-desc">
                                {{ $evento->accion === 'eliminado' ? 'El registro fue eliminado del sistema.' : 'No se registraron nuevos valores.' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Botón Volver --}}
        <div class="pt-4 border-t border-slate-200/60 dark:border-white/10 flex justify-between items-center">
            <a href="{{ route('eventos.index') }}" class="btn-clean px-5 py-2 font-bold text-xs flex items-center gap-2">
                ← Volver al Listado
            </a>
        </div>
    </div>
</div>
@endsection
