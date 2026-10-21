@extends('layouts.app')
@section('title', 'Detalles de Producto')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="glass-card p-6 md:p-8">

        {{-- Encabezado unificado: Flecha Volver + Imagen + Título con Auto-Redimensión + Botones de Acción --}}
        <div class="detail-header flex items-center justify-between gap-3 sm:gap-4 mb-6 border-b border-gray-200/50 dark:border-white/10 pb-6 md:pb-8">
            <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                <a href="{{ route('stocks.index') }}" class="btn-ghost text-xl shrink-0" title="Volver">⬅️</a>

                @if($stock->photo)
                    <img src="{{ asset('storage/' . $stock->photo) }}" alt="{{ $stock->producto }}"
                         data-lightbox-src="{{ asset('storage/' . $stock->photo) }}" data-lightbox-alt="{{ $stock->producto }}"
                         class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl object-cover cursor-pointer border border-white/40 shadow-sm shrink-0 hover:scale-105 transition-transform"
                         title="Ver foto del producto">
                @else
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/40 dark:bg-slate-900/50 border border-white/40 dark:border-white/10 flex items-center justify-center text-2xl shadow-sm shrink-0 backdrop-blur-sm">
                        📦
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <h2 class="stock-detail-title font-black text-slate-800 dark:text-white tracking-tight break-words" title="{{ $stock->producto }}">
                        <span>{{ $stock->producto }}</span>
                        <span class="pill {{ $stock->active ? 'pill-done' : 'pill-anulado' }} text-[10px] py-0.5 px-2 font-bold uppercase tracking-wider align-middle inline-flex ml-2 whitespace-nowrap">
                            {{ $stock->active ? 'ACTIVO' : 'INACTIVO' }}
                        </span>
                    </h2>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1 truncate">
                        <span>Código: <strong class="font-mono text-indigo-500 dark:text-indigo-400 font-bold">{{ $stock->codigo ?? 'N/A' }}</strong></span>
                        <span class="mx-1 text-gray-300 dark:text-gray-600">|</span>
                        <span>Categoría: <strong class="text-slate-700 dark:text-slate-300 font-semibold">{{ $stock->categoria ?? 'General' }}{{ $stock->subcategoria ? ' / ' . $stock->subcategoria : '' }}</strong></span>
                    </p>
                </div>
            </div>

            {{-- Botones de Acción a la derecha (posición original conservada para modo web) --}}
            <div class="stock-detail-actions flex items-center gap-3 shrink-0">
                <a href="{{ route('stocks.print', $stock->id) }}" target="_blank" class="btn-ghost border-blue-500/20 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30">
                    🖨️ Imprimir
                </a>
                
                @if(!auth()->user()->isInvitado())
                @if($stock->active)
                <a href="{{ route('stocks.edit', $stock->id) }}" class="btn-ghost border-yellow-500/20 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-500/10">
                    ✏️ Editar
                </a>
                @if($stock->cantidad > 0)
                <button type="button" onclick="openBajaStockModal('{{ route('stocks.dar-de-baja', $stock->id) }}', '{{ addslashes($stock->producto) }}', {{ $stock->cantidad }}, {{ $stock->precio_compra }})" class="btn-ghost border-purple-500/30 text-purple-600 dark:text-purple-400 hover:bg-purple-500/10 font-bold">
                    📉 Dar de Baja
                </button>
                @endif
                @endif
                <button type="button" onclick="openAnularModal('{{ route('stocks.anular', $stock->id) }}', {{ !$stock->active ? 'true' : 'false' }})" class="btn-danger">
                    {{ $stock->active ? '🚫 Anular' : '✅ Reactivar' }}
                </button>
                @endif
            </div>
        </div>

        {{-- Proveedor y Cantidad --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-indigo p-5 flex items-center gap-4 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-xl shadow-lg shrink-0">
                    🏭
                </div>
                <div class="min-w-0">
                    <p class="text-base font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-1">Proveedor Predeterminado</p>
                    <p class="font-medium text-base text-slate-800 dark:text-slate-200 leading-tight break-words">
                        {{ $proveedor->nombre_razon_social ?? '—' }}
                    </p>
                    <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-1 flex flex-wrap items-center gap-2">
                        <span>ID: {{ $proveedor->identificacion ?? 'N/A' }}</span>
                        @if($proveedor && $proveedor->telefono)
                            <span class="text-gray-300 dark:text-gray-600"></span>
                            <span class="inline-flex items-center gap-1">
                                <x-whatsapp-link :telefono="$proveedor->telefono" class="text-xs font-medium text-slate-700 dark:text-slate-200" />
                            </span>
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="glass-card hover-glow glass-card-blue p-5 flex items-center justify-between min-w-0">
                <div class="min-w-0">
                    <p class="text-base font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-1">Existencia Actual</p>
                    <p class="font-bold text-4xl text-slate-800 dark:text-white leading-tight">
                        {{ $stock->cantidad }} <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Unidades</span>
                    </p>
                </div>
                <div class="text-5xl opacity-80 drop-shadow-md">📦</div>
            </div>
        </div>

        {{-- Estructura de Precios --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Estructura de Precios</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-orange p-4 sm:p-5 flex flex-col justify-center items-center text-center relative overflow-hidden group min-w-0">
                <p class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-base no-print-emoji">📦</span> Costo de Compra</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">$ {{ number_format($stock->precio_compra, 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-purple p-4 sm:p-5 flex flex-col justify-center items-center text-center relative overflow-hidden group min-w-0">
                <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-base no-print-emoji">⚡</span> Precio a Técnico</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">$ {{ number_format($stock->precio_tecnico, 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-emerald p-4 sm:p-5 flex flex-col justify-center items-center text-center relative overflow-hidden group min-w-0">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center justify-center gap-1.5"><span class="text-base no-print-emoji">🛒</span> Precio Público Venta</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">$ {{ number_format($stock->precio_venta, 0, ',', '.') }}</p>
                @php
                    $utilidadPesos = $stock->precio_venta - $stock->precio_compra;
                    $utilidadPct = $stock->utilidad ?? 0;
                @endphp
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mt-1 z-10" title="Margen: {{ $utilidadPct }}%">+$ {{ number_format($utilidadPesos, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Historial de Operaciones --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3">
            <div class="flex items-center gap-2">
                <span class="text-lg">💳</span>
                <h3 class="font-bold text-base sm:text-lg text-slate-800 dark:text-white">Historial de Operaciones (Compras y Ventas)</h3>
            </div>
            @if(isset($historial) && $historial->count() > 0)
            @php
                $totalCompradas = $historial->where('factura.tipo_movimiento', 'compra')->where('factura.estado', '!=', 'anulada')->sum('cantidad');
                $totalVendidas = $historial->where('factura.tipo_movimiento', 'venta')->where('factura.estado', '!=', 'anulada')->sum('cantidad');
            @endphp
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs font-bold">
                <span class="px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 whitespace-nowrap">
                    Compras: {{ $totalCompradas }} unidades
                </span>
                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 whitespace-nowrap">
                    Ventas: {{ $totalVendidas }} unidades
                </span>
            </div>
            @endif
        </div>

        <style>
        .table-compact-header thead th,
        .ts-table.table-compact-header thead th {
            padding: 8px 12px !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            letter-spacing: 0.5px !important;
            line-height: 1.3 !important;
            height: 35px !important;
            box-sizing: border-box !important;
            white-space: nowrap !important;
        }
        .table-compact-header tbody td,
        .ts-table.table-compact-header tbody td {
            padding: 10px 12px !important;
        }

        /* En modo oscuro, aplicar gradiente profundo con transparencia de cristal */
        html.dark .ts-table.table-compact-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.70) 0%, rgba(10, 18, 36, 0.78) 100%) !important;
            backdrop-filter: blur(24px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(24px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            box-shadow: 0 12px 35px -8px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.14) !important;
        }

        html.dark .ts-table.table-compact-header thead th {
            background: rgba(15, 23, 42, 0.82) !important;
            color: #F8FAFC !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.09) !important;
        }

        /* Badge de Motivo en Historial de Bajas y Mermas (idéntico en bordes y estética a 'Total descartado') */
        .badge-motivo-baja {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 8px !important; /* rounded-lg idéntico a Total descartado */
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
            white-space: nowrap;
            background: rgba(245, 158, 11, 0.10);
            color: #d97706; /* amber-600 */
            border: 1px solid rgba(245, 158, 11, 0.20); /* border-amber-500/20 */
        }

        html.dark .badge-motivo-baja {
            background: rgba(245, 158, 11, 0.10) !important;
            color: #fbbf24 !important; /* amber-400 */
            border: 1px solid rgba(245, 158, 11, 0.20) !important;
        }
        </style>

        <div class="overflow-x-auto overflow-y-auto max-h-[400px] relative mb-8" style="overflow-y: auto !important;">
            <table class="ts-table table-dense table-compact-header mb-0">
                <thead class="sticky top-0 z-20 shadow-sm">
                    <tr>
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Factura</th>
                        <th class="text-center">Entidad</th>
                        <th class="text-center w-20">Cant.</th>
                        <th class="text-center">Precio U.</th>
                        <th class="text-center">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historial as $item)
                    <tr class="{{ $item->factura->estado === 'anulada' ? 'opacity-50 grayscale' : '' }}">
                        <td class="text-sm font-medium text-center whitespace-nowrap">{{ \Carbon\Carbon::parse($item->factura->fecha ?? $item->created_at)->format('d/m/Y') }}</td>
                        <td class="text-center whitespace-nowrap">
                            <span class="pill {{ $item->factura->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done' }} text-xs whitespace-nowrap">
                                {{ $item->factura->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta' }}
                            </span>
                        </td>
                        <td class="font-bold text-center whitespace-nowrap">
                            <a href="{{ route('inventario.facturas.show', $item->factura_id) }}" class="text-blue-600 hover:underline">
                                {{ $item->factura->numero_factura }}
                            </a>
                        </td>
                        <td class="text-sm font-bold text-slate-700 dark:text-slate-300">
                            {{ $item->factura->facturable->nombre_razon_social ?? $item->factura->facturable->nombre ?? '—' }}
                        </td>
                        <td class="text-center font-bold whitespace-nowrap">{{ $item->cantidad }}</td>
                        <td class="text-right font-bold text-slate-800 dark:text-white whitespace-nowrap">$&nbsp;{{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                        <td class="text-right font-black whitespace-nowrap {{ $item->factura->tipo_movimiento === 'compra' ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ $item->factura->tipo_movimiento === 'compra' ? '-' : '+' }}$&nbsp;{{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center p-8 text-gray-500 font-medium">
                            No hay compras ni ventas registradas para este producto.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Historial de Bajas y Mermas --}}
        <div class="mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📉</span>
                    <h3 class="font-bold text-base sm:text-lg text-slate-800 dark:text-white">Historial de Bajas y Mermas</h3>
                </div>
                @if(isset($bajas) && $bajas->count() > 0)
                <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs font-bold">
                    <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 whitespace-nowrap">
                        Total descartado: {{ $stock->total_unidades_baja }} unidades
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 whitespace-nowrap">
                        Pérdida: $&nbsp;{{ number_format($stock->total_perdida_bajas, 0, ',', '.') }}
                    </span>
                </div>
                @endif
            </div>

            <div class="overflow-x-auto overflow-y-auto max-h-[400px] relative mb-6" style="overflow-y: auto !important;">
                <table class="ts-table table-dense table-compact-header mb-0">
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <tr>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-center">P. Compra</th>
                            <th class="text-center">Pérdida Total</th>
                            <th class="text-center">Motivo</th>
                            <th class="text-center">Observación</th>
                            <th class="text-center">Autorizado Por</th>
                            @if(!auth()->user()->isInvitado())
                            <th class="text-center w-24">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bajas ?? [] as $baja)
                        <tr>
                            <td class="text-center text-xs font-semibold text-slate-700 dark:text-slate-100 whitespace-nowrap">
                                {{ $baja->created_at->format('d/m/Y') }}
                            </td>
                            <td class="text-center font-black text-xs whitespace-nowrap text-amber-600 dark:text-amber-400">
                                -{{ $baja->cantidad }}
                            </td>
                            <td class="text-center font-bold text-slate-800 dark:text-white text-xs whitespace-nowrap">
                                $&nbsp;{{ number_format($baja->precio_compra_unitario, 0, ',', '.') }}
                            </td>
                            <td class="text-center font-black text-xs whitespace-nowrap text-red-600 dark:text-red-400">
                                $&nbsp;{{ number_format($baja->costo_total_perdida, 0, ',', '.') }}
                            </td>
                            <td class="text-center whitespace-nowrap">
                                <span class="badge-motivo-baja px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 whitespace-nowrap">
                                    {{ $baja->motivo_label }}
                                </span>
                            </td>
                            <td class="text-center text-xs text-slate-600 dark:text-slate-200 max-w-xs break-words [overflow-wrap:anywhere]" title="{{ $baja->observacion }}">
                                {{ $baja->observacion ?: '—' }}
                            </td>
                            <td class="text-center text-xs font-bold text-slate-700 dark:text-white whitespace-nowrap">
                                {{ $baja->user->name ?? 'Sistema' }}
                            </td>
                            @if(!auth()->user()->isInvitado())
                            <td class="text-center whitespace-nowrap">
                                <div class="flex items-center justify-center">
                                    <button type="button" 
                                        onclick="openRevertirBajaModal('{{ route('stocks.bajas.revertir', $baja->id) }}', {{ $baja->cantidad }}, '{{ addslashes($stock->producto) }}')"
                                        class="btn-ghost btn-action-revertir w-8 h-8 flex items-center justify-center p-0 text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 hover:bg-emerald-500/10 rounded-lg transition" 
                                        title="Revertir baja (reintegrar {{ $baja->cantidad }} unidad(es) al stock)">
                                        ↩️
                                    </button>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ !auth()->user()->isInvitado() ? '8' : '7' }}" class="text-center p-8 text-gray-500 font-medium">
                                No se registran bajas ni mermas para este artículo. Todas las unidades ingresadas están en existencias o fueron vendidas/consumidas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-4 flex justify-between items-center text-xs font-semibold text-gray-400">
            <span>Última actualización: {{ $stock->updated_at->format('d/m/Y H:i:s') }}</span>
            <span>Registro inicial: {{ $stock->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>
</div>

<!-- MODAL DAR DE BAJA UNIDADES (Simétrico al Modal de Notificaciones) -->
<div id="baja-stock-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="baja-stock-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4" style="max-width: 550px;">
        
        {{-- Header simétrico a notificaciones --}}
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl shrink-0 select-none">📉</span>
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">Dar de Baja Unidades</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Merma de inventario por daño o defecto</p>
            </div>
        </div>

        <form id="baja-stock-form" method="POST" action="">
            @csrf
            {{-- Contenedor Central a ancho completo alineado con el borde horizontal --}}
            <div class="w-full flex flex-col flex-1">
                
                {{-- Bloque resumen del artículo con barra de acento --}}
                <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800 relative overflow-hidden mb-4">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-amber-500 rounded-l-xl"></div>
                    <div class="pl-2.5 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">Artículo de Inventario</span>
                            <span id="baja-stock-disponible" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-lg"></span>
                        </div>
                        <p id="baja-stock-producto" class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate"></p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cantidad a dar de baja:</label>
                        <input type="number" name="cantidad" id="baja-stock-cantidad" min="1" value="1" required class="glass-input text-center font-black text-base w-full">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Motivo de la baja:</label>
                        <select name="motivo" id="baja-stock-motivo" required class="glass-input text-sm w-full" data-placeholder="Seleccione el motivo de la baja...">
                            <option value="">Seleccione el motivo de la baja...</option>
                            <option value="defectuoso_fabrica" selected>🏭 Defectuoso de fábrica (Garantía proveedor)</option>
                            <option value="dano_taller">⚠️ Daño accidental en taller</option>
                            <option value="obsoleto">⏳ Obsoleto / Deterioro</option>
                            <option value="perdida_merma">📉 Pérdida / Merma de inventario</option>
                            <option value="otro">📝 Otro motivo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Observación o justificación:</label>
                        <textarea name="observacion" id="baja-stock-observacion" rows="2" placeholder="Detalla la falla o circunstancia..." class="glass-input text-xs w-full"></textarea>
                    </div>

                    @if(auth()->user()->isTecnico())
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Contraseña de Administrador:
                        </label>
                        <input type="password" name="password_confirm" required placeholder="••••••••" class="glass-input text-center tracking-widest text-sm w-full">
                    </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4 w-full">
                <button type="button" onclick="closeBajaStockModal()" class="flex-1 btn-ghost-red justify-center py-2.5 rounded-xl font-bold text-sm">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 btn-ghost-blue justify-center py-2.5 rounded-xl font-bold text-sm">
                    Confirmar Baja
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REVERTIR BAJA DE UNIDADES (Simétrico al Modal de Notificaciones) -->
<div id="revertir-baja-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="revertir-baja-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4" style="max-width: 550px;">
        
        {{-- Header simétrico a notificaciones --}}
        <div class="flex items-center gap-3 mb-4">
            <span class="text-3xl shrink-0 select-none">↩️</span>
            <div>
                <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">Revertir Baja de Unidades</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Reintegrar unidades al inventario activo y retirar la pérdida</p>
            </div>
        </div>

        <form id="revertir-baja-form" method="POST" action="">
            @csrf
            {{-- Contenedor Central a ancho completo alineado con el borde horizontal --}}
            <div class="w-full flex flex-col flex-1">
                
                {{-- Bloque resumen del reintegro con barra de acento --}}
                <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 relative overflow-hidden mb-4">
                    <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500 rounded-l-xl"></div>
                    <div class="pl-2.5 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Restitución de Stock</span>
                            <span id="revertir-baja-unidades-badge" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/40 px-2 py-0.5 rounded-md"></span>
                        </div>
                        <p id="revertir-baja-producto" class="text-sm font-bold text-slate-800 dark:text-gray-100 truncate"></p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">
                            ✅ Las unidades se sumarán de inmediato al stock disponible y el registro de merma se retirará del historial.
                        </p>
                    </div>
                </div>

                @if(auth()->user()->isTecnico())
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Contraseña de Administrador:
                        </label>
                        <input type="password" name="password_confirm" id="revertir-baja-password" required placeholder="••••••••" class="glass-input text-center tracking-widest text-sm w-full">
                    </div>
                </div>
                @endif
            </div>

            <div class="flex items-center gap-3 mt-4 w-full">
                <button type="button" onclick="closeRevertirBajaModal()" class="flex-1 btn-ghost-red justify-center py-2.5 rounded-xl font-bold text-sm">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 btn-ghost-emerald justify-center py-2.5 rounded-xl font-bold text-sm">
                    Confirmar Reversión
                </button>
            </div>
        </form>
    </div>
</div>

<script>


function openBajaStockModal(actionUrl, producto, maxCantidad, precioCompra) {
    const modal = document.getElementById('baja-stock-modal');
    const card  = document.getElementById('baja-stock-card');
    const form  = document.getElementById('baja-stock-form');
    const prodEl = document.getElementById('baja-stock-producto');
    const dispEl = document.getElementById('baja-stock-disponible');
    const cantInput = document.getElementById('baja-stock-cantidad');
    const motivoSel = document.getElementById('baja-stock-motivo');

    form.action = actionUrl;
    prodEl.textContent = producto;
    dispEl.textContent = maxCantidad + ' unidades';
    cantInput.max = maxCantidad;
    cantInput.value = 1;

    if (motivoSel && motivoSel.tomselect) {
        motivoSel.tomselect.setValue('defectuoso_fabrica');
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95', 'opacity-0');
        cantInput.focus();
    }, 10);
}

function closeBajaStockModal() {
    const modal = document.getElementById('baja-stock-modal');
    const card  = document.getElementById('baja-stock-card');
    const motivoSel = document.getElementById('baja-stock-motivo');
    if (motivoSel && motivoSel.tomselect && motivoSel.tomselect.isOpen) {
        motivoSel.tomselect.close();
    }
    modal.classList.add('opacity-0');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function openRevertirBajaModal(actionUrl, cantidad, producto) {
    const modal = document.getElementById('revertir-baja-modal');
    const card  = document.getElementById('revertir-baja-card');
    const form  = document.getElementById('revertir-baja-form');
    const prodEl = document.getElementById('revertir-baja-producto');
    const badgeEl = document.getElementById('revertir-baja-unidades-badge');
    const passInput = document.getElementById('revertir-baja-password');

    form.action = actionUrl;
    prodEl.textContent = producto;
    badgeEl.textContent = '+' + cantidad + (cantidad === 1 ? ' unidad' : ' unidades');
    if (passInput) passInput.value = '';

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95', 'opacity-0');
        if (passInput) passInput.focus();
    }, 10);
}

function closeRevertirBajaModal() {
    const modal = document.getElementById('revertir-baja-modal');
    const card  = document.getElementById('revertir-baja-card');
    modal.classList.add('opacity-0');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
@endsection
