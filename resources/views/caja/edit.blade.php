@extends('layouts.app')
@section('title', 'Editar Movimiento de Caja #' . $movimiento->id)

@section('content')
@php
    $numFactura = null;
    if (preg_match('/#([A-Za-z0-9-]+)/', $movimiento->descripcion ?? '', $matches)) {
        $numFactura = $matches[1];
    }
    $facturaRel = $numFactura ? \App\Models\Factura::where('numero_factura', $numFactura)->first() : null;
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="glass-card p-6 md:p-8">
        
        {{-- Encabezado unificado dentro de la tarjeta, tal como caja.show --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6 md:pb-8 relative z-20">
            <div class="flex items-center gap-4">
                <a href="{{ route('caja.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver a la lista de caja">⬅️</a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white tracking-tight whitespace-nowrap">
                            ✏️ Editar Movimiento <span class="text-blue-600 dark:text-blue-400">#{{ $movimiento->id }}</span>
                        </h2>
                        <span class="pill {{ $movimiento->anulado ? 'pill-anulado' : 'pill-done' }} text-xs py-1 px-3 font-bold uppercase tracking-wider whitespace-nowrap">
                            {{ $movimiento->anulado ? 'ANULADO' : 'ACTIVO' }}
                        </span>
                        <span class="pill {{ $movimiento->tipo_movimiento === 'ingreso' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' }} text-xs py-1 px-3 font-bold uppercase tracking-wider whitespace-nowrap">
                            {{ $movimiento->tipo_movimiento === 'ingreso' ? '📈 INGRESO' : '📉 EGRESO' }}
                        </span>
                        @if($movimiento->monto_total > 0 && $movimiento->saldo_pendiente > 0)
                            <span class="pill pill-pending text-xs py-1 px-3 font-bold whitespace-nowrap">
                                Saldo: ${{ number_format($movimiento->saldo_pendiente, 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-2">
                        Modifica los datos del registro de caja o añade abonos
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                @if($facturaRel)
                <a href="{{ route('inventario.facturas.show', $facturaRel->id) }}" class="btn-ghost border-indigo-500/20 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30" title="Ver factura {{ $facturaRel->numero_factura }}">
                    📄 Ver Factura #{{ $facturaRel->numero_factura }}
                </a>
                @endif
                <a href="{{ route('caja.show', $movimiento->id) }}" class="btn-ghost border-blue-500/20 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30" title="Ver detalle completo">
                    👁️ Ver Movimiento
                </a>
            </div>
        </div>

        {{-- Formulario Principal en Ancho Completo Homogéneo --}}
        <form action="{{ route('caja.update', $movimiento->id) }}" method="POST">
            @csrf @method('PUT')
            @include('caja._form', ['movimiento' => $movimiento])
            
            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('caja.index') }}" class="btn-cancel">✕ Cancelar</a>
                <button type="submit" class="btn-save">
                    🔄 Actualizar Movimiento
                </button>
            </div>
        </form>

        {{-- Sección de Saldos y Abonos si aplica --}}
        @if(!$movimiento->parent_id && $movimiento->monto_total > 0)
            <div class="mt-10 pt-8 border-t border-gray-200/50 dark:border-white/10 space-y-6">
                {{-- Resumen Financiero y Saldos --}}
                <div>
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <span>📊</span> Resumen Financiero y Saldos
                    </h3>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="glass-card hover-glow glass-card-indigo p-4 flex flex-col justify-center items-center text-center">
                            <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-1">Monto Total</p>
                            <p class="text-xl font-black text-slate-800 dark:text-white">${{ number_format($movimiento->monto_total, 0, ',', '.') }}</p>
                        </div>
                        <div class="glass-card hover-glow glass-card-emerald p-4 flex flex-col justify-center items-center text-center">
                            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">Monto Inicial Pagado</p>
                            <p class="text-xl font-black text-emerald-600 dark:text-emerald-400">${{ number_format($movimiento->monto, 0, ',', '.') }}</p>
                        </div>
                        <div class="glass-card hover-glow glass-card-blue p-4 flex flex-col justify-center items-center text-center">
                            <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">Total en Abonos</p>
                            <p class="text-xl font-black text-blue-600 dark:text-blue-400">${{ number_format($movimiento->childPayments->where('anulado', false)->sum('monto'), 0, ',', '.') }}</p>
                        </div>
                        <div class="glass-card hover-glow {{ $movimiento->saldo_pendiente > 0 ? 'glass-card-orange' : 'glass-card-teal' }} p-4 flex flex-col justify-center items-center text-center">
                            <p class="text-xs font-bold {{ $movimiento->saldo_pendiente > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-teal-600 dark:text-teal-400' }} uppercase tracking-widest mb-1">Saldo Pendiente</p>
                            <p class="text-xl font-black {{ $movimiento->saldo_pendiente > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-teal-600 dark:text-teal-400' }}">${{ number_format($movimiento->saldo_pendiente, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Registrar Nuevo Abono si hay saldo pendiente --}}
                @if($movimiento->saldo_pendiente > 0)
                <div class="p-6 rounded-2xl bg-blue-50/40 dark:bg-blue-900/10 border border-blue-200/50 dark:border-blue-500/20">
                    <h4 class="font-bold text-base text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <span>💵</span> Registrar Pago / Abono a este Movimiento
                    </h4>
                    <form action="{{ route('caja.abonos.store', $movimiento->id) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="field-label">Monto del Abono ($) *</label>
                                <input type="text" id="monto_abono_visual" required placeholder="Ej: 50.000" class="glass-input font-bold text-right py-2">
                                <input type="hidden" name="monto_abono" id="monto_abono_real">
                            </div>
                            <div>
                                <label class="field-label">Fecha del Pago *</label>
                                <input type="date" name="fecha" required value="{{ date('Y-m-d') }}" class="glass-input py-2">
                            </div>
                            <div>
                                <label class="field-label">Tipo de Pago *</label>
                                <select name="tipo_pago" required class="glass-input py-2">
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="consignacion">🏦 Banco / Transferencia</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="field-label">Descripción del Abono (Opcional)</label>
                            <input type="text" name="descripcion" placeholder="Detalles u observaciones de este abono..." class="glass-input text-xs py-2">
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="btn-primary py-2.5 px-6 flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/20 font-bold">
                                ➕ Guardar Abono
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-3">
                    <span class="text-2xl">🎉</span>
                    <div>
                        <h4 class="font-black text-sm text-emerald-700 dark:text-emerald-400">¡Totalmente Pagado!</h4>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500">Este movimiento no tiene saldos pendientes por saldar.</p>
                    </div>
                </div>
                @endif

                {{-- Historial de Abonos / Pagos Relacionados en Tabla Homogénea --}}
                <div>
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
                        <span>📜</span> Historial de Abonos Registrados ({{ $movimiento->childPayments->count() }})
                    </h3>
                    @if($movimiento->childPayments->isNotEmpty())
                    <div class="overflow-x-auto overflow-y-auto max-h-[350px] relative">
                        <table class="ts-table responsive-table w-full mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 70px;">Código</th>
                                    <th class="text-center" style="width: 110px;">Fecha</th>
                                    <th class="text-left">Descripción</th>
                                    <th class="text-center" style="width: 140px;">Método Pago</th>
                                    <th class="text-center" style="width: 140px;">Registrado Por</th>
                                    <th class="text-right" style="width: 120px;">Monto</th>
                                    <th class="text-center" style="width: 90px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($movimiento->childPayments as $child)
                                <tr class="{{ $child->anulado ? 'opacity-50 grayscale' : '' }}">
                                    <td data-label="Código:" class="text-center font-bold text-slate-600 dark:text-slate-300">#{{ $child->id }}</td>
                                    <td data-label="Fecha:" class="text-center text-sm font-medium whitespace-nowrap">{{ $child->fecha->format('d/m/Y') }}</td>
                                    <td data-label="Descripción:" class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $child->descripcion ?? '—' }}</td>
                                    <td data-label="Método:" class="text-center whitespace-nowrap">
                                        <span class="pill {{ $child->tipo_pago === 'efectivo' ? 'pill-efectivo' : 'pill-banco' }} text-xs">
                                            {{ $child->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}
                                        </span>
                                    </td>
                                    <td data-label="Registró:" class="text-center text-xs font-bold text-slate-700 dark:text-slate-300">
                                        {{ $child->user->name ?? 'Sistema' }}
                                    </td>
                                    <td data-label="Monto:" class="text-right font-black text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                        ${{ number_format($child->monto, 0, ',', '.') }}
                                    </td>
                                    <td data-label="Acciones:" class="text-center whitespace-nowrap">
                                        <a href="{{ route('caja.show', $child->id) }}" class="btn-ghost px-2 py-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30" title="Ver detalle de este abono">
                                            👁️
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-xs text-gray-400 text-center py-4 bg-white/10 dark:bg-slate-900/20 rounded-xl border border-white/20 dark:border-white/5">No se han registrado abonos adicionales para este movimiento.</p>
                    @endif
                </div>
            </div>
        @elseif($movimiento->parent_id)
            <div class="mt-8 p-5 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">🔗</span>
                    <div>
                        <h4 class="font-black text-blue-700 dark:text-blue-400">Registro de Abono Hijo</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-300">Este movimiento es un abono subordinado para saldar el movimiento principal #{{ $movimiento->parent_id }}.</p>
                    </div>
                </div>
                <a href="{{ route('caja.edit', $movimiento->parent_id) }}" class="btn-ghost border-blue-500/30 text-blue-600 dark:text-blue-400 text-xs px-4 py-2 font-bold whitespace-nowrap">
                    👁️ Ver Movimiento Principal (#{{ $movimiento->parent_id }})
                </a>
            </div>
        @endif

    </div>
</div>

<script>
    // Formateador de monto del abono en el formulario
    document.addEventListener('DOMContentLoaded', function() {
        const visual = document.getElementById('monto_abono_visual');
        const real = document.getElementById('monto_abono_real');
        if (visual && real) {
            visual.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value.length > 12) {
                    value = value.substring(0, 12);
                }
                if (value !== "") {
                    real.value = value;
                    e.target.value = new Intl.NumberFormat('es-CO').format(value);
                } else {
                    real.value = "";
                }
            });
        }
    });
</script>
@endsection
