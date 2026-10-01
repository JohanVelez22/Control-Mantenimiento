@extends('layouts.app')
@section('title', 'Cierres de Caja')

@section('content')

@push('modals')
{{-- Modal de contraseña para eliminar cierre (Liquid Glass) --}}
<div id="pwd-cierre-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
    <div class="ts-modal-card scale-95 opacity-0" id="pwd-cierre-card">
        <div class="p-6">
            <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center text-3xl mx-auto mb-4">
                🔓
            </div>
            <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Eliminar Cierre</h3>
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                @if(auth()->check() && auth()->user()->isTecnico())
                    Esta acción desbloquea el día. Ingresa la contraseña de un administrador.
                @else
                    ¿Estás seguro de eliminar este cierre? Esta acción desbloqueará el día.
                @endif
            </p>
            <form id="delete-cierre-form" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                @if(auth()->check() && auth()->user()->isTecnico())
                <div>
                    <input type="password" name="password_confirm" id="pwd-cierre-input" required placeholder="Contraseña de Administrador..." class="glass-input text-center tracking-widest text-lg">
                </div>
                @endif
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCierrePwd()" class="flex-1 btn-ghost-amber">Cancelar</button>
                    <button type="submit" class="flex-1 btn-danger justify-center font-bold">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Calculadora de Billetes --}}
<div id="calc-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
    <div class="ts-modal-card scale-95 opacity-0 max-w-md w-full" id="calc-card">
        <div class="p-6">
            <div class="mb-4">
                <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">🧮 Calculadora de Dinero en Mano</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-1">Ingresa el conteo de billetes y monedas para sumar automáticamente.</p>
            </div>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 100.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="100000" placeholder="0">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 50.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="50000" placeholder="0">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 20.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="20000" placeholder="0">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 10.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="10000" placeholder="0">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 5.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="5000" placeholder="0">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 2.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="2000" placeholder="0">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 1.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="1000" placeholder="0">
                </div>
                <hr class="border-gray-200/50 dark:border-white/10 my-2">
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Monedas (Total $)</label>
                    <input type="number" min="0" class="glass-input calc-input-monedas font-bold" placeholder="0">
                </div>
            </div>
            
            <div class="mt-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/30 text-center">
                <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">Total Físico Contado</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white" id="calc-total">$0</p>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="button" onclick="resetCalc()" class="btn-cancel flex-1 justify-center font-bold" style="padding: 9px 18px; font-size: 14px; font-weight: 700;">Limpiar</button>
                <button type="button" onclick="closeCalc(true)" class="btn-primary flex-1 justify-center font-bold" style="padding: 9px 18px; font-size: 14px; font-weight: 700;">Aplicar al Cierre</button>
            </div>
        </div>
    </div>
</div>

<script>
    let calcTotalValue = 0;

    // Lógica Calculadora
    function openCalc() {
        const modal = document.getElementById('calc-modal');
        const card = document.getElementById('calc-card');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
        }, 10);
    }

    function closeCalc(apply = false) {
        if (apply) {
            const visual = document.getElementById('efectivo_real_visual');
            if (visual) {
                // El input es único y ya lleva el name del formulario; el
                // servidor sanea el formato monetario en CierreCajaController.
                visual.value = new Intl.NumberFormat('es-CO').format(calcTotalValue);
                if (typeof actualizarDiferencia === 'function') {
                    actualizarDiferencia();
                }
            }
        }
        const modal = document.getElementById('calc-modal');
        const card = document.getElementById('calc-card');
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.calc-input').forEach(input => {
            let val = parseInt(input.value) || 0;
            let mult = parseInt(input.dataset.val);
            total += (val * mult);
        });
        
        const monedas = document.querySelector('.calc-input-monedas');
        if (monedas && monedas.value) {
            total += parseInt(monedas.value) || 0;
        }

        calcTotalValue = total;
        document.getElementById('calc-total').innerText = '$' + new Intl.NumberFormat('es-CO').format(total);
    }

    function resetCalc() {
        document.querySelectorAll('.calc-input, .calc-input-monedas').forEach(input => input.value = '');
        calculateTotal();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.calc-input, .calc-input-monedas').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
    });
</script>
@endpush

<div class="space-y-6">

    {{-- Panel de Cierre del Día actual --}}
    @if(!$yaExiste && $preview)
    <div class="space-y-4">
        {{-- Tarjetas de resumen del Cierre del Día --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            <div class="glass-card hover-glow glass-card-emerald p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📈</span> Ingresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['total_ingresos'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-red p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📉</span> Egresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['total_egresos'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-blue p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">💵</span> Efectivo Sistema</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['efectivo'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow glass-card-purple p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🏦</span> Consignación</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['consignacion'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card hover-glow {{ $preview['saldo_final'] >= 0 ? 'glass-card-teal' : 'glass-card-orange' }} p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0 col-span-2 lg:col-span-1">
                <p class="text-xs font-bold {{ $preview['saldo_final'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">⚖️</span> Saldo Teórico</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['saldo_final'], 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tarjeta interactiva de Arqueo y Conciliación Física --}}
        <div class="glass-card p-6 md:p-8 border border-blue-500/20 shadow-xl">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-5 border-b border-gray-200/50 dark:border-white/10">
                <div>
                    <h2 class="text-xl md:text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                        <span>💵</span> Cierre del Día & Conciliación Física — {{ \Carbon\Carbon::parse($hoy)->format('d/m/Y') }}
                    </h2>
                    <p class="text-xs md:text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                        {{ $preview['num_movimientos'] }} movimiento(s) registrados hoy. Cuenta el efectivo real en caja para conciliar el arqueo.
                    </p>
                </div>
                <button type="button" onclick="openCalc()" class="btn-clean text-xs px-4 py-2 font-bold flex items-center gap-2 self-stretch md:self-auto justify-center">
                    <span>🧮</span> Abrir Desglose de Billetes
                </button>
            </div>

            <form action="{{ route('cierre.store') }}" method="POST" id="form-cierre-caja" class="space-y-6">
                @csrf
                <input type="hidden" name="fecha" value="{{ $hoy }}">
                <input type="hidden" id="efectivo_sistema_val" value="{{ $preview['efectivo'] }}">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-stretch">
                    {{-- Tarjeta Efectivo Teórico (Sistema) --}}
                    <div class="glass-card hover-glow glass-card-blue p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                        <div>
                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider block mb-1">
                                💻 Efectivo Teórico (Sistema)
                            </span>
                            <p class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white">
                                ${{ number_format($preview['efectivo'], 0, ',', '.') }}
                            </p>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-2">
                            Total calculado de entradas y salidas registradas en efectivo.
                        </p>
                    </div>

                    {{-- Input Efectivo Real Contado --}}
                    <div class="glass-card hover-glow glass-card-gray p-4 sm:p-5 flex flex-col justify-between items-center relative overflow-hidden group text-center min-w-0">
                        <div class="w-full">
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider block mb-1" for="efectivo_real_visual">
                                🖐🏻 Dinero Contado en Mano
                            </label>
                            <div class="relative max-w-[15rem] mx-auto w-full my-0.5">
                                <div class="relative flex items-center justify-center">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-black text-slate-400 dark:text-slate-500 text-xl md:text-2xl select-none pointer-events-none">$</span>
                                    <input type="text"
                                           id="efectivo_real_visual"
                                           name="efectivo_real_contado"
                                           value=""
                                           placeholder="Sin contar"
                                           autocomplete="off"
                                           class="glass-input w-full pl-8 pr-3 text-center font-black text-2xl md:text-3xl text-slate-800 dark:text-white tracking-tight h-[46px] md:h-[50px] placeholder:text-slate-400 placeholder:text-base md:placeholder:text-lg placeholder:font-bold focus:ring-2 focus:ring-blue-500/40">
                                </div>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium mt-2">
                            Déjalo vacío si no realizaste el conteo físico.
                        </p>
                    </div>

                    {{-- Indicador Reactivo de Diferencia --}}
                    <div id="card-diferencia" class="glass-card hover-glow glass-card-gray p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider block mb-1 text-slate-600 dark:text-slate-300" id="label-diferencia">
                                ⚖️ Resultado del Arqueo
                            </span>
                            <p class="text-2xl md:text-3xl font-black transition-colors text-slate-500 dark:text-slate-400" id="valor-diferencia">
                                —
                            </p>
                        </div>
                        <div class="mt-2 flex items-center">
                            <span id="badge-diferencia" class="pill pill-done text-xs font-bold py-1 px-3">
                                ⚪ Sin conciliar
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Campo para Justificación / Motivo de la Diferencia --}}
                <div id="wrapper-motivo-diferencia" class="space-y-1.5">
                    <label class="field-label flex items-center justify-between" for="motivo_diferencia">
                        <span class="flex items-center gap-1.5 font-bold">
                            <span>📝</span> Motivo / Justificación de la Diferencia
                        </span>
                        <span id="badge-aviso-diferencia" class="hidden text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                            Observación de Descuadre
                        </span>
                    </label>
                    <input type="text" 
                           name="motivo_diferencia" 
                           id="motivo_diferencia" 
                           placeholder="Opcional: Si hubo diferencia, describe aquí la causa (ej. cambio mal entregado, propina, etc.)..." 
                           class="glass-input text-xs sm:text-sm">
                </div>

                {{-- Campo Observaciones Generales --}}
                <div class="space-y-1.5">
                    <label class="field-label flex items-center gap-1.5 font-bold" for="cierre_observaciones">
                        <span>📋</span> Observaciones Generales de la Jornada (Opcional)
                    </label>
                    <textarea name="observaciones" 
                              id="cierre_observaciones" 
                              rows="2" 
                              placeholder="Notas generales sobre la jornada laboral o novedades..." 
                              class="glass-input text-xs"></textarea>
                </div>

                {{-- Botón de Cierre --}}
                <div class="flex flex-col sm:flex-row justify-end items-center gap-3 pt-4 border-t border-gray-200/50 dark:border-white/10">
                    <button type="submit" class="btn-primary w-full sm:w-auto text-sm px-6 py-2.5 font-bold shadow-lg shadow-indigo-500/20 flex items-center justify-center gap-2">
                        <span>🔒</span> Finalizar Cierre y Bloquear Día
                    </button>
                </div>
            </form>
        </div>
    </div>
    @elseif($yaExiste)
    <div class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-600 text-2xl shrink-0">✅</div>
        <div>
            <p class="font-bold text-emerald-700 dark:text-emerald-400">El día de hoy ya se encuentra cerrado y bloqueado.</p>
            <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80 font-medium">Todas las transacciones de esta jornada están protegidas contablemente. Para desbloquearlo, elimina el cierre usando la clave de administración.</p>
        </div>
    </div>
    @endif

    {{-- Historial de cierres --}}
    <div class="glass-card p-6 md:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                    <span class="text-3xl">📊</span> Historial de Cierres de Caja
                </h2>
                <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                    Registro de actas de cierre y arqueos de conciliación física
                </p>
            </div>
        </div>

        <div class="overflow-x-auto pb-2">
            <table class="ts-table responsive-table w-full">
                <thead>
                    <tr>
                        <th class="text-center">Fecha</th>
                        <th class="text-right">Ingresos</th>
                        <th class="text-right">Egresos</th>
                        <th class="text-right">Efectivo Sistema</th>
                        <th class="text-right">Contado en Mano</th>
                        <th class="text-center">Diferencia</th>
                        <th class="text-right">Consignación</th>
                        <th class="text-right">Saldo Final</th>
                        <th class="text-center">Mov.</th>
                        <th>Registró</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cierres as $c)
                    <tr>
                        <td data-label="Fecha:" class="text-center font-bold">{{ $c->fecha->format('d/m/Y') }}</td>
                        <td data-label="Ingresos:" class="text-right text-emerald-600 dark:text-emerald-400 font-bold">${{ number_format($c->total_ingresos, 0, ',', '.') }}</td>
                        <td data-label="Egresos:" class="text-right text-red-600 dark:text-red-400 font-bold">${{ number_format($c->total_egresos, 0, ',', '.') }}</td>
                        <td data-label="Efectivo Sist.:" class="text-right text-blue-600 dark:text-blue-400 font-semibold">${{ number_format($c->efectivo, 0, ',', '.') }}</td>
                        <td data-label="Contado en Mano:" class="text-right font-black text-slate-800 dark:text-white">
                            @if($c->efectivo_real_contado !== null)
                                ${{ number_format($c->efectivo_real_contado, 0, ',', '.') }}
                            @else
                                <span class="text-gray-400 font-normal">—</span>
                            @endif
                        </td>
                        <td data-label="Diferencia:" class="text-center">
                            @if($c->estado_diferencia === 'cuadrado')
                                <span class="pill pill-done text-[11px] font-bold py-0.5 px-2" title="El efectivo contado coincide con el teórico">
                                    🟢 Cuadrado
                                </span>
                            @elseif($c->estado_diferencia === 'faltante')
                                <span class="pill pill-anulado text-[11px] font-bold py-0.5 px-2" title="{{ $c->motivo_diferencia ?: 'Faltante de caja' }}">
                                    🔴 Faltante ${{ number_format(abs($c->diferencia), 0, ',', '.') }}
                                </span>
                            @elseif($c->estado_diferencia === 'sobrante')
                                <span class="pill pill-efectivo text-[11px] font-bold py-0.5 px-2" title="{{ $c->motivo_diferencia ?: 'Sobrante de caja' }}">
                                    🔵 Sobrante ${{ number_format($c->diferencia, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="pill pill-done text-[11px] font-bold py-0.5 px-2 opacity-60" title="No se registró conteo físico del efectivo">
                                    ⚪ Sin conciliar
                                </span>
                            @endif
                        </td>
                        <td data-label="Consignación:" class="text-right text-purple-600 dark:text-purple-400 font-semibold">${{ number_format($c->consignacion, 0, ',', '.') }}</td>
                        <td data-label="Saldo Final:" class="text-right font-black {{ $c->saldo_final >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }}">${{ number_format($c->saldo_final, 0, ',', '.') }}</td>
                        <td data-label="Mov.:" class="text-center text-sm font-semibold text-gray-500">{{ $c->num_movimientos }}</td>
                        <td data-label="Registró:" class="text-xs text-gray-500 font-medium">{{ $c->user->name }}</td>
                        <td data-label="Acciones:" class="text-center w-28">
                            <div class="actions-grid">
                                <a href="{{ route('cierre.show', $c->id) }}" class="btn-ghost btn-action-view w-8 h-8 flex items-center justify-center p-0 text-xs text-indigo-600 dark:text-indigo-400 hover:bg-indigo-500/10" title="Ver detalle">👁️</a>
                                @if(!auth()->user()->isInvitado())
                                    <a href="{{ route('cierre.edit', $c->id) }}" class="btn-ghost btn-action-edit w-8 h-8 flex items-center justify-center p-0 text-xs text-yellow-600 dark:text-yellow-400" title="Editar observaciones">✏️</a>
                                @endif
                                @if(auth()->user()->isAdmin())
                                    <button type="button" onclick="openCierrePwd('{{ route('cierre.destroy', $c->id) }}')" class="btn-ghost btn-action-anular w-8 h-8 flex items-center justify-center p-0 text-xs text-red-600 dark:text-red-400" title="Eliminar / Desbloquear">🗑️</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <span class="text-5xl">📊</span>
                                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">Sin cierres registrados</h3>
                                <p class="text-gray-500 text-sm font-medium">Realiza el primer cierre del día usando el panel superior de arqueo.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cierres->hasPages())
        <div class="mt-5 flex justify-end">
            {{ $cierres->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    const CARD_BASE = 'glass-card hover-glow p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0';
    const TEXTO_BASE = 'text-2xl md:text-3xl font-black transition-colors';

    function leerConteo(visualInput) {
        if (!visualInput) return null;
        const crudo = visualInput.value.replace(/\D/g, '');
        if (crudo === '') return null;
        return parseFloat(crudo);
    }

    function formatearPesos(n) {
        return '$' + new Intl.NumberFormat('es-CO').format(Math.round(n));
    }

    function actualizarDiferencia() {
        const sist = parseFloat(document.getElementById('efectivo_sistema_val')?.value || 0);
        const visualInput = document.getElementById('efectivo_real_visual');

        const valorDiffEl = document.getElementById('valor-diferencia');
        const badgeDiffEl = document.getElementById('badge-diferencia');
        const cardDiffEl = document.getElementById('card-diferencia');
        const labelDiffEl = document.getElementById('label-diferencia');
        const badgeAvisoEl = document.getElementById('badge-aviso-diferencia');

        if (!valorDiffEl || !badgeDiffEl || !cardDiffEl) return;

        const realVal = leerConteo(visualInput);

        // Sin conteo registrado: el arqueo queda honestamente pendiente.
        if (realVal === null) {
            valorDiffEl.className = TEXTO_BASE + ' text-slate-500 dark:text-slate-400';
            valorDiffEl.innerText = '—';
            badgeDiffEl.className = 'pill pill-done text-xs font-bold py-1 px-3';
            badgeDiffEl.innerHTML = '⚪ Sin conciliar';
            cardDiffEl.className = CARD_BASE + ' glass-card-gray';
            if (labelDiffEl) labelDiffEl.className = 'text-xs font-bold uppercase tracking-wider block mb-1 text-slate-600 dark:text-slate-300';
            if (badgeAvisoEl) badgeAvisoEl.classList.add('hidden');
            return;
        }

        const diff = realVal - sist;
        const signo = diff > 0 ? '+' : (diff < 0 ? '-' : '');
        valorDiffEl.innerText = signo + formatearPesos(Math.abs(diff));

        if (Math.abs(diff) < 1) {
            valorDiffEl.className = TEXTO_BASE + ' text-emerald-600 dark:text-emerald-400';
            badgeDiffEl.className = 'pill pill-done text-xs font-bold py-1 px-3';
            badgeDiffEl.innerHTML = '🟢 Cuadre Exacto';
            cardDiffEl.className = CARD_BASE + ' glass-card-emerald';
            if (labelDiffEl) labelDiffEl.className = 'text-xs font-bold uppercase tracking-wider block mb-1 text-emerald-600 dark:text-emerald-400';
            if (badgeAvisoEl) badgeAvisoEl.classList.add('hidden');
        } else if (diff < 0) {
            valorDiffEl.className = TEXTO_BASE + ' text-red-600 dark:text-red-400';
            badgeDiffEl.className = 'pill pill-anulado text-xs font-bold py-1 px-3';
            badgeDiffEl.innerHTML = '🔴 Faltante en Caja';
            cardDiffEl.className = CARD_BASE + ' glass-card-red';
            if (labelDiffEl) labelDiffEl.className = 'text-xs font-bold uppercase tracking-wider block mb-1 text-red-600 dark:text-red-400';
            if (badgeAvisoEl) badgeAvisoEl.classList.remove('hidden');
        } else {
            valorDiffEl.className = TEXTO_BASE + ' text-blue-600 dark:text-blue-400';
            badgeDiffEl.className = 'pill pill-efectivo text-xs font-bold py-1 px-3';
            badgeDiffEl.innerHTML = '🔵 Sobrante en Caja';
            cardDiffEl.className = CARD_BASE + ' glass-card-blue';
            if (labelDiffEl) labelDiffEl.className = 'text-xs font-bold uppercase tracking-wider block mb-1 text-blue-600 dark:text-blue-400';
            if (badgeAvisoEl) badgeAvisoEl.classList.remove('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const visual = document.getElementById('efectivo_real_visual');
        if (visual) {
            visual.addEventListener('input', function (e) {
                let digitos = e.target.value.replace(/\D/g, '').slice(0, 12);
                e.target.value = digitos === ''
                    ? ''
                    : new Intl.NumberFormat('es-CO').format(digitos);
                actualizarDiferencia();
            });
            visual.addEventListener('blur', actualizarDiferencia);
        }
        actualizarDiferencia();
    });

    function openCierrePwd(url) {
        const modal = document.getElementById('pwd-cierre-modal');
        const card = document.getElementById('pwd-cierre-card');
        const input = document.getElementById('pwd-cierre-input');
        document.getElementById('delete-cierre-form').action = url;
        if (input) input.value = '';
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
            if (input) input.focus();
        }, 10);
    }
    
    function closeCierrePwd() {
        const modal = document.getElementById('pwd-cierre-modal');
        const card = document.getElementById('pwd-cierre-card');
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    document.addEventListener('keydown', e => { 
        if (e.key === 'Escape') {
            closeCierrePwd(); 
            closeCalc();
        }
    });
</script>
@endsection
