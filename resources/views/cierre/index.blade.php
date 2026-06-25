@extends('layouts.app')

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
                Esta acción desbloquea el día. Ingresa tu contraseña o la del administrador.
            </p>
            <form id="delete-cierre-form" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                <div>
                    <input type="password" name="password_confirm" id="pwd-cierre-input" required placeholder="Contraseña..." class="glass-input text-center tracking-widest text-lg">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeCierrePwd()" class="flex-1 btn-ghost justify-center">Cancelar</button>
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
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-black text-slate-800 dark:text-white flex items-center gap-2">🧮 Calculadora de Dinero</h3>
                <button type="button" onclick="closeCalc()" class="text-gray-400 hover:text-red-500 transition-colors text-xl leading-none">✕</button>
            </div>
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 100.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="100000" placeholder="Cantidad">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 50.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="50000" placeholder="Cantidad">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 20.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="20000" placeholder="Cantidad">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 10.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="10000" placeholder="Cantidad">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 5.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="5000" placeholder="Cantidad">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 2.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="2000" placeholder="Cantidad">
                </div>
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Billetes de 1.000 x</label>
                    <input type="number" min="0" class="glass-input calc-input font-bold" data-val="1000" placeholder="Cantidad">
                </div>
                <hr class="border-gray-200/50 dark:border-white/10 my-2">
                <div class="grid grid-cols-2 gap-3 items-center">
                    <label class="font-bold text-right text-gray-700 dark:text-gray-300 text-sm">Monedas (Valor Total)</label>
                    <input type="number" min="0" class="glass-input calc-input-monedas font-bold" placeholder="Suma de monedas">
                </div>
            </div>
            
            <div class="mt-4 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700/30 text-center">
                <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">Total Contado</p>
                <p class="text-3xl font-black text-slate-800 dark:text-white" id="calc-total">$0</p>
            </div>
            <div class="mt-4 flex gap-3">
                <button type="button" onclick="resetCalc()" class="btn-cancel flex-1 justify-center font-bold" style="padding: 9px 18px;">Limpiar</button>
                <button type="button" onclick="closeCalc()" class="btn-primary flex-1 justify-center font-bold" style="padding: 9px 18px;">Hecho</button>
            </div>
        </div>
    </div>
</div>

<script>
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

    function closeCalc() {
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

<div class="space-y-4">

    {{-- Preview del día actual (si no cerrado) --}}
    @if(!$yaExiste && $preview)
    <div class="glass-card p-6">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold flex items-center gap-2"><span class="text-2xl">📅</span> Cierre del Día — {{ \Carbon\Carbon::parse($hoy)->format('d/m/Y') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Vista previa — aún no cerrado</p>
            </div>
            <form action="{{ route('cierre.store') }}" method="POST">
                @csrf
                <input type="hidden" name="fecha" value="{{ $hoy }}">
                <div class="flex flex-wrap gap-2 items-center">
                    <button type="button" onclick="openCalc()" class="btn-clean text-sm px-4 py-2 font-bold flex items-center gap-2">
                        <span>🧮</span> Calcular Dinero
                    </button>
                    <button type="submit" class="btn-primary text-sm px-5 py-2 font-bold">
                        🔒 Cerrar Día
                    </button>
                </div>
            </form>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📈</span> Ingresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['total_ingresos'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">📉</span> Egresos</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['total_egresos'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">💵</span> Efectivo</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['efectivo'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
                <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
                <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center"><span class="text-lg">🏦</span> Consignación</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['consignacion'], 0, ',', '.') }}</p>
            </div>
            <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group text-center">
                <div class="absolute -right-6 -top-6 w-24 h-24 {{ $preview['saldo_final'] >= 0 ? 'bg-emerald-500/20 group-hover:bg-emerald-500/30' : 'bg-orange-500/20 group-hover:bg-orange-500/30' }} rounded-full blur-2xl transition-all"></div>
                <p class="text-xs font-bold {{ $preview['saldo_final'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5 justify-center">⚖️ Saldo Final</p>
                <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($preview['saldo_final'], 0, ',', '.') }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 font-semibold mt-4 text-right">{{ $preview['num_movimientos'] }} movimiento(s) registrados hoy</p>
    </div>
    @elseif($yaExiste)
    <div class="mb-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-600 text-2xl">✅</div>
        <div>
            <p class="font-bold text-emerald-700 dark:text-emerald-400">Día de hoy ya cerrado y bloqueado.</p>
            <p class="text-sm text-emerald-600/80 dark:text-emerald-400/80 font-medium">Para desbloquearlo, elimina el cierre de hoy usando la contraseña.</p>
        </div>
    </div>
    @endif

    {{-- Historial de cierres --}}
    <div class="glass-card p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-black text-slate-800 dark:text-white flex items-center gap-2">
                <span class="text-3xl">📊</span> Historial de Cierres de Caja
            </h2>
        </div>

        <div class="overflow-x-auto pb-2">
            <table class="ts-table responsive-table w-full">
                <thead>
                    <tr>
                        <th class="text-center">Fecha</th>
                        <th class="text-right">Ingresos</th>
                        <th class="text-right">Egresos</th>
                        <th class="text-right">Efectivo</th>
                        <th class="text-right">Consignación</th>
                        <th class="text-right">Saldo</th>
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
                        <td data-label="Efectivo:" class="text-right text-blue-600 dark:text-blue-400 font-semibold">${{ number_format($c->efectivo, 0, ',', '.') }}</td>
                        <td data-label="Consignación:" class="text-right text-purple-600 dark:text-purple-400 font-semibold">${{ number_format($c->consignacion, 0, ',', '.') }}</td>
                        <td data-label="Saldo:" class="text-right font-black {{ $c->saldo_final >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }}">${{ number_format($c->saldo_final, 0, ',', '.') }}</td>
                        <td data-label="Mov.:" class="text-center text-sm font-semibold text-gray-500">{{ $c->num_movimientos }}</td>
                        <td data-label="Registró:" class="text-xs text-gray-500 font-medium">{{ $c->user->name }}</td>
                        <td data-label="Acciones:" class="text-center">
                            <div class="flex justify-end md:justify-center">
                                @if(auth()->user()->isAdmin())
                                    <button type="button" onclick="openCierrePwd('{{ route('cierre.destroy', $c->id) }}')" class="btn-danger px-3 py-1.5 text-xs" title="Eliminar">🗑️</button>
                                @else
                                    <span class="text-xs text-gray-400 font-medium bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-md border border-gray-200 dark:border-gray-700">🔒 Bloqueado</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-12">
                            <div class="flex flex-col items-center gap-3">
                                <span class="text-5xl">📊</span>
                                <h3 class="text-lg font-bold text-slate-700 dark:text-slate-300">Sin cierres registrados</h3>
                                <p class="text-gray-500 text-sm font-medium">Realiza el primer cierre del día usando el panel de arriba.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            {{ $cierres->links() }}
        </div>
    </div>
</div>

<script>
    function openCierrePwd(url) {
        const modal = document.getElementById('pwd-cierre-modal');
        const card = document.getElementById('pwd-cierre-card');
        document.getElementById('delete-cierre-form').action = url;
        document.getElementById('pwd-cierre-input').value = '';
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
            document.getElementById('pwd-cierre-input').focus();
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



