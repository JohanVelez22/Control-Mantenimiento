@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3 mb-4">
        <a href="{{ route('caja.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
        <div>
            <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Movimiento: #{{ $movimiento->id }}</h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Modifica los datos del registro de caja o añade abonos</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Formulario principal --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6 md:p-8">
                <form action="{{ route('caja.update', $movimiento->id) }}" method="POST" class="space-y-6">
                    @csrf @method('PUT')
                    @include('caja._form', ['movimiento' => $movimiento])
                    
                    <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                        <a href="{{ route('caja.index') }}" class="btn-cancel">↩️ Cancelar</a>
                        <button type="submit" class="btn-save">
                            🔄 Actualizar Movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel de Abonos y Trazabilidad --}}
        <div class="lg:col-span-1 space-y-6">
            @if(!$movimiento->parent_id && $movimiento->monto_total > 0)
                {{-- Resumen de Saldos --}}
                <div class="glass-card p-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/20 rounded-full blur-2xl transition-all"></div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white mb-4 z-10 flex items-center gap-2">📊 Resumen de Saldos</h3>
                    
                    <div class="space-y-3 text-sm z-10 relative">
                        <div class="flex justify-between border-b border-gray-100 dark:border-white/5 pb-2">
                            <span class="text-gray-500">Monto Total:</span>
                            <span class="font-bold text-slate-800 dark:text-white">${{ number_format($movimiento->monto_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-white/5 pb-2">
                            <span class="text-gray-500">Monto Inicial Pagado:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($movimiento->monto, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 dark:border-white/5 pb-2">
                            <span class="text-gray-500">Total en Abonos:</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">${{ number_format($movimiento->childPayments->where('anulado', false)->sum('monto'), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between pt-1">
                            <span class="text-slate-800 dark:text-white font-bold">Saldo Pendiente:</span>
                            <span class="font-black text-lg text-orange-600 dark:text-orange-400">${{ number_format($movimiento->saldo_pendiente, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Registrar Nuevo Abono --}}
                @if($movimiento->saldo_pendiente > 0)
                    <div class="glass-card p-6">
                        <h3 class="text-lg font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2">💵 Registrar Pago / Abono</h3>
                        
                        <form action="{{ route('caja.abonos.store', $movimiento->id) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="field-label">Monto del Abono ($) *</label>
                                <input type="text" id="monto_abono_visual" required placeholder="Monto a pagar..." class="glass-input font-bold text-right py-2">
                                <input type="hidden" name="monto_abono" id="monto_abono_real">
                            </div>

                            <div>
                                <label class="field-label">Fecha del Pago *</label>
                                <input type="date" name="fecha" required value="{{ date('Y-m-d') }}" class="glass-input">
                            </div>

                            <div>
                                <label class="field-label">Tipo de Pago *</label>
                                <select name="tipo_pago" required class="glass-input">
                                    <option value="efectivo">💵 Efectivo</option>
                                    <option value="consignacion">🏦 Banco / Transferencia</option>
                                </select>
                            </div>

                            <div>
                                <label class="field-label">Descripción (Opcional)</label>
                                <textarea name="descripcion" rows="2" placeholder="Detalles de este abono..." class="glass-input text-xs"></textarea>
                            </div>

                            <button type="submit" class="btn-primary w-full py-2.5 flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/20">
                                ➕ Guardar Abono
                            </button>
                        </form>
                    </div>
                @else
                    <div class="glass-card p-5 bg-emerald-500/10 border border-emerald-500/20 text-center">
                        <span class="text-3xl">🎉</span>
                        <h4 class="font-black text-emerald-700 dark:text-emerald-400 mt-2">¡Totalmente Pagado!</h4>
                        <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">Este movimiento no tiene saldos pendientes.</p>
                    </div>
                @endif

                {{-- Historial de Abonos --}}
                <div class="glass-card p-6">
                    <h3 class="text-lg font-black text-slate-800 dark:text-white mb-4 flex items-center gap-2">📜 Historial de Abonos</h3>
                    
                    @if($movimiento->childPayments->count() > 0)
                        <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                            @foreach($movimiento->childPayments as $child)
                                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-gray-100 dark:border-white/5 relative {{ $child->anulado ? 'opacity-50 grayscale' : '' }}">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-xs font-bold text-slate-500">{{ $child->fecha->format('d/m/Y') }}</span>
                                        <span class="font-black text-sm text-blue-600 dark:text-blue-400">${{ number_format($child->monto, 0, ',', '.') }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-600 dark:text-gray-400 leading-snug">{{ $child->descripcion }}</p>
                                    <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-100 dark:border-white/5 text-[9px] text-gray-400">
                                        <span>Pago: {{ $child->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}</span>
                                        <span>Registró: {{ $child->user->name ?? 'Sistema' }}</span>
                                    </div>
                                    @if($child->anulado)
                                        <div class="absolute inset-0 flex items-center justify-center bg-white/10 backdrop-blur-[1px] rounded-xl">
                                            <span class="text-[10px] font-bold text-red-500 tracking-widest uppercase bg-red-100 dark:bg-red-900/40 px-2 py-0.5 rounded border border-red-200 dark:border-red-800">ANULADO</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 text-center py-4">No se han registrado abonos adicionales para este movimiento.</p>
                    @endif
                </div>
            @else
                {{-- Si es un movimiento hijo --}}
                @if($movimiento->parent_id)
                    <div class="glass-card p-6 bg-blue-500/5 border border-blue-500/10 text-center">
                        <span class="text-3xl">🔗</span>
                        <h4 class="font-black text-blue-700 dark:text-blue-400 mt-2">Registro de Abono Hijo</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Este movimiento es un abono para saldar el movimiento principal:</p>
                        <a href="{{ route('caja.edit', $movimiento->parent_id) }}" class="btn-ghost text-xs px-3 py-1.5 mt-3 inline-block font-bold">
                            👁️ Ver Movimiento Padre (#{{ $movimiento->parent_id }})
                        </a>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
    // Formateador de monto del abono en el panel derecho
    document.addEventListener('DOMContentLoaded', function() {
        const visual = document.getElementById('monto_abono_visual');
        const real = document.getElementById('monto_abono_real');
        if (visual && real) {
            visual.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, "");
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
