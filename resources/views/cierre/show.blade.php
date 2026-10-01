@extends('layouts.app')
@section('title', 'Detalle de Cierre de Caja — ' . $cierre->fecha->format('d/m/Y'))

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="glass-card p-6 md:p-8">
        
        {{-- Modal de contraseña para eliminar cierre --}}
        @push('modals')
        <div id="pwd-cierre-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
            <div class="ts-modal-card scale-95 opacity-0" id="pwd-cierre-card">
                <div class="p-6">
                    <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center text-3xl mx-auto mb-4">
                        🔓
                    </div>
                    <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Eliminar / Desbloquear Cierre</h3>
                    <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                        @if(auth()->check() && auth()->user()->isTecnico())
                            Esta acción desbloqueará el día {{ $cierre->fecha->format('d/m/Y') }}. Ingresa la contraseña de un administrador.
                        @else
                            ¿Estás seguro de eliminar este cierre? Esta acción desbloqueará el día {{ $cierre->fecha->format('d/m/Y') }}.
                        @endif
                    </p>
                    <form id="delete-cierre-form" action="{{ route('cierre.destroy', $cierre->id) }}" method="POST" class="space-y-4">
                        @csrf @method('DELETE')
                        @if(auth()->check() && auth()->user()->isTecnico())
                        <div>
                            <input type="password" name="password_confirm" id="pwd-cierre-input" required placeholder="Contraseña de Administrador..." class="glass-input text-center tracking-widest text-lg">
                        </div>
                        @endif
                        <div class="flex gap-3 pt-2">
                            <button type="button" onclick="closeCierrePwd()" class="flex-1 btn-ghost-amber">Cancelar</button>
                            <button type="submit" class="flex-1 btn-danger justify-center font-bold">Eliminar Cierre</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endpush

        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6 md:pb-8 no-print">
            <div class="flex items-center gap-4">
                <a href="{{ route('cierre.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver a la lista de cierres">⬅️</a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl md:text-3xl font-black text-slate-800 dark:text-white tracking-tight">
                            Cierre de Caja <span class="text-blue-600 dark:text-blue-400">#{{ $cierre->id }}</span>
                        </h2>
                        <span class="pill {{ $cierre->bloqueado ? 'pill-done' : 'pill-anulado' }} text-xs py-1 px-3 font-bold uppercase tracking-wider">
                            {{ $cierre->bloqueado ? '🔒 DÍA BLOQUEADO' : '🔓 ABIERTO' }}
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                        Correspondiente al día: <span class="font-bold text-slate-700 dark:text-slate-200">{{ $cierre->fecha->format('d/m/Y') }}</span>
                    </p>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3 shrink-0 ml-auto">
                <button type="button" onclick="window.print()" class="btn-ghost border-blue-500/20 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30">
                    🖨️ Imprimir Acta
                </button>
                
                @if(!auth()->user()->isInvitado())
                <a href="{{ route('cierre.edit', $cierre->id) }}" class="btn-ghost border-yellow-500/20 text-yellow-600">
                    ✏️ Editar Notas
                </a>
                @endif

                @if(auth()->user()->isAdmin())
                <button type="button" onclick="openCierrePwd()" class="btn-danger">
                    🗑️ Eliminar
                </button>
                @endif
            </div>
        </div>

        {{-- Datos Operativos del Cierre --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 text-sm p-6 rounded-2xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200/60 dark:border-blue-500/20 mb-8">
            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">📅 Fecha de Arqueo</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 break-words">
                    {{ $cierre->fecha->format('d/m/Y') }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">👤 Cerrado Por</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300 break-words">
                    {{ $cierre->user->name ?? 'Sistema' }}
                </span>
            </div>

            <div class="min-w-0">
                <span class="text-[13px] sm:text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider block mb-1">📊 Cantidad de Movimientos</span>
                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ $cierre->num_movimientos }} transacciones activas
                </span>
            </div>
        </div>

        {{-- Resumen Financiero en Tarjetas --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Resumen Financiero del Día</h3>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-emerald p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📈</span> Ingresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->total_ingresos, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-red p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">📉</span> Egresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->total_egresos, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-blue p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">💵</span> Efectivo Sistema</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->efectivo, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow glass-card-purple p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🏦</span> Consignación</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->consignacion, 0, ',', '.') }}</p>
            </div>

            <div class="glass-card hover-glow {{ $cierre->saldo_final >= 0 ? 'glass-card-teal' : 'glass-card-orange' }} p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0 col-span-2 lg:col-span-1">
                <p class="text-xs font-bold {{ $cierre->saldo_final >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">⚖️</span> Saldo Teórico</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->saldo_final, 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Conciliación Física / Arqueo en Mano --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <span>💵</span> Arqueo y Conciliación Física
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="glass-card hover-glow glass-card-blue p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">💻</span> Efectivo Teórico</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->efectivo, 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium z-10">Calculado por ingresos y egresos</p>
            </div>

            <div class="glass-card hover-glow glass-card-gray p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg no-print-emoji">🖐️</span> Contado en Mano</p>
                @if($cierre->efectivo_real_contado !== null)
                    <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($cierre->efectivo_real_contado, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium z-10">Billetes y monedas físicos en caja</p>
                @else
                    <p class="text-2xl font-black text-slate-400 dark:text-slate-500 z-10">—</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-medium z-10">No se registró conteo físico</p>
                @endif
            </div>

            <div class="glass-card hover-glow {{ $cierre->estado_diferencia_card }} p-4 sm:p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center
                          {{ match($cierre->estado_diferencia) {
                              'cuadrado' => 'text-emerald-600 dark:text-emerald-400',
                              'sobrante' => 'text-blue-600 dark:text-blue-400',
                              'faltante' => 'text-red-600 dark:text-red-400',
                              default    => 'text-slate-500 dark:text-slate-400',
                          } }}">
                    <span class="text-lg no-print-emoji">⚖️</span> Resultado del Arqueo
                </p>
                @if($cierre->estado_diferencia === 'sin_conciliar')
                    <p class="text-2xl font-black text-slate-400 dark:text-slate-500 z-10">—</p>
                @else
                    <p class="text-2xl font-black z-10
                              {{ match($cierre->estado_diferencia) {
                                  'cuadrado' => 'text-emerald-600 dark:text-emerald-400',
                                  'sobrante' => 'text-blue-600 dark:text-blue-400',
                                  default    => 'text-red-600 dark:text-red-400',
                              } }}">
                        {{ $cierre->diferencia > 0 ? '+' : ($cierre->diferencia < 0 ? '-' : '') }}${{ number_format(abs($cierre->diferencia), 0, ',', '.') }}
                    </p>
                @endif
                <div class="mt-1 z-10">
                    @if($cierre->estado_diferencia === 'cuadrado')
                        <span class="pill pill-done text-xs font-bold py-0.5 px-3">🟢 Cuadre Exacto</span>
                    @elseif($cierre->estado_diferencia === 'faltante')
                        <span class="pill pill-anulado text-xs font-bold py-0.5 px-3">🔴 Faltante en Caja</span>
                    @elseif($cierre->estado_diferencia === 'sobrante')
                        <span class="pill pill-efectivo text-xs font-bold py-0.5 px-3">🔵 Sobrante en Caja</span>
                    @else
                        <span class="pill pill-done text-xs font-bold py-0.5 px-3 opacity-60">⚪ Sin conciliar</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Motivo de la diferencia si existe --}}
        @if($cierre->motivo_diferencia)
        <div class="mb-8 glass-card hover-glow {{ $cierre->estado_diferencia === 'faltante' ? 'glass-card-red' : ($cierre->estado_diferencia === 'sobrante' ? 'glass-card-blue' : 'glass-card-gray') }} p-5">
            <h4 class="font-bold text-sm uppercase tracking-wider mb-1 z-10 flex items-center gap-2
                        {{ $cierre->estado_diferencia === 'faltante' ? 'text-red-700 dark:text-red-400' : ($cierre->estado_diferencia === 'sobrante' ? 'text-blue-700 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300') }}">
                <span>📝</span> Motivo / Justificación de la Diferencia
            </h4>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed z-10">
                {{ $cierre->motivo_diferencia }}
            </p>
        </div>
        @endif

        {{-- Observaciones del Cierre --}}
        @if($cierre->observaciones)
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Observaciones / Notas de Jornada</h3>
        <div class="p-5 bg-white/10 dark:bg-slate-900/25 border border-white/40 dark:border-white/5 backdrop-blur-md rounded-2xl mb-8 shadow-sm print:bg-white print:border-slate-300 print:shadow-none">
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300 print:text-black leading-relaxed whitespace-pre-line">{{ $cierre->observaciones }}</p>
        </div>
        @endif

        {{-- Desglose de Movimientos del Día --}}
        <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
            <span>📄</span> Detalle de Transacciones Registradas ({{ $movimientos->count() }})
        </h3>
        
        <div class="overflow-x-auto overflow-y-auto max-h-[450px] relative mb-8">
            <table class="ts-table responsive-table w-full mb-0">
                <thead>
                    <tr>
                        <th class="w-16 text-left">Código</th>
                        <th class="text-left">Tercero / Cliente</th>
                        <th class="text-left">Concepto</th>
                        <th class="text-center">Método</th>
                        <th class="text-center">Operación</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $m)
                    <tr class="{{ $m->anulado ? 'opacity-50 grayscale' : '' }}">
                        <td class="font-bold">
                            <a href="{{ route('caja.show', $m->id) }}" class="text-blue-600 dark:text-blue-400 hover:underline print:text-black print:no-underline" title="Ver detalle del movimiento #{{ $m->id }}">
                                #{{ $m->id }}
                            </a>
                        </td>
                        <td class="text-sm font-medium text-slate-800 dark:text-slate-200 break-words">
                            {{ $m->persona ?? ($m->empresa ?? 'Tercero') }}
                        </td>
                        <td class="text-sm font-medium text-slate-600 dark:text-slate-300 break-words">
                            {{ $m->concepto->nombre ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span class="pill {{ $m->tipo_pago === 'efectivo' ? 'pill-efectivo' : 'pill-banco' }} text-xs">
                                {{ $m->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}
                            </span>
                        </td>
                        <td class="text-center text-xs font-bold uppercase">
                            <span class="{{ $m->tipo_movimiento === 'ingreso' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $m->tipo_movimiento === 'ingreso' ? '📈 Ingreso' : '📉 Egreso' }}
                            </span>
                        </td>
                        <td class="text-right font-black text-sm {{ $m->tipo_movimiento === 'ingreso' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} pr-4">
                            ${{ number_format($m->monto, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-6 text-gray-500 font-medium">No se registraron movimientos en esta fecha.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-black border-t-2 border-slate-200 dark:border-white/10 bg-gray-50/50 dark:bg-gray-800/50">
                        <td colspan="5" class="text-right text-xs uppercase tracking-wider font-bold text-slate-800 dark:text-white print:text-black py-3 pr-3">TOTAL:</td>
                        <td class="text-right font-black text-base {{ $cierre->saldo_final >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} print:text-black py-3 pr-4">
                            ${{ number_format($cierre->saldo_final, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
            <span>Última actualización: {{ $cierre->updated_at->format('d/m/Y H:i:s') }}</span>
            <span>Fecha creación del cierre: {{ $cierre->created_at->format('d/m/Y H:i:s') }}</span>
        </div>

    </div>
</div>

<script>
    function openCierrePwd() {
        const modal = document.getElementById('pwd-cierre-modal');
        const card = document.getElementById('pwd-cierre-card');
        const input = document.getElementById('pwd-cierre-input');
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
</script>

<style>
@media print {
    .no-print { display: none !important; }
    body { color: #000000 !important; background: #ffffff !important; }
    *, *::before, *::after { 
        box-shadow: none !important; 
        text-shadow: none !important; 
        filter: none !important; 
        border-radius: 0 !important;
    }
    .glass-card, div, table, tr, td, th { 
        background: #ffffff !important; 
        background-color: #ffffff !important; 
        border-radius: 0 !important;
    }
    p, span, td, th, h2, h3, div, label { 
        color: #000000 !important; 
        opacity: 1 !important; 
        -webkit-print-color-adjust: exact !important; 
        print-color-adjust: exact !important; 
    }
    .text-teal-400, .text-teal-500, .text-teal-600,
    .text-emerald-400, .text-emerald-500, .text-emerald-600,
    .text-blue-400, .text-blue-500, .text-blue-600,
    .text-purple-400, .text-purple-500, .text-purple-600,
    .text-red-400, .text-red-500, .text-red-600,
    .text-gray-400, .text-slate-400, .text-gray-500, .text-slate-500, .text-slate-600, .text-slate-700 { color: #000000 !important; }
}
</style>
@endsection
