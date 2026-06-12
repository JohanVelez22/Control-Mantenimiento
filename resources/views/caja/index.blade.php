@extends('layouts.app')

@section('content')
{{-- Modal de contraseña para eliminar (Liquid Glass) --}}
<div id="pwd-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
    <div class="ts-modal-card scale-95 opacity-0" id="pwd-modal-card">
        <div class="p-6">
            <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center justify-center text-3xl mx-auto mb-4">
                🔒
            </div>
            <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Confirmar Eliminación</h3>
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                Ingresa tu contraseña de administrador para continuar. Esta acción borrará el registro de la base de datos.
            </p>
            <form id="delete-pwd-form" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                <div>
                    <input type="password" name="password_confirm" id="pwd-input" required placeholder="Contraseña..." class="glass-input text-center tracking-widest text-lg">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closePwdModal()" class="flex-1 btn-ghost justify-center">Cancelar</button>
                    <button type="submit" class="flex-1 btn-danger justify-center font-bold">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal de contraseña para anular (Liquid Glass) --}}
<div id="pwd-anular-modal" class="ts-modal-overlay hidden opacity-0 transition-opacity duration-300">
    <div class="ts-modal-card scale-95 opacity-0" id="pwd-anular-card">
        <div class="p-6">
            <div class="w-16 h-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-orange-500 flex items-center justify-center text-3xl mx-auto mb-4">
                🚫
            </div>
            <h3 class="text-xl font-black text-center text-slate-800 dark:text-white mb-2">Confirmar Anulación</h3>
            <p class="text-center text-gray-500 dark:text-gray-400 text-sm font-medium mb-6">
                Ingresa tu contraseña para anular este registro. Se mantendrá el historial pero no afectará saldos.
            </p>
            <form id="anular-pwd-form" method="POST" class="space-y-4">
                @csrf
                <div>
                    <input type="password" name="password_confirm" id="pwd-anular-input" required placeholder="Contraseña..." class="glass-input text-center tracking-widest text-lg focus:ring-orange-500">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeAnularModal()" class="flex-1 btn-ghost justify-center">Cancelar</button>
                    <button type="submit" class="flex-1 text-center justify-center font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 py-2.5 rounded-xl shadow-[0_4px_14px_rgba(249,115,22,0.4)] transition-all">Anular</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="space-y-6">
    {{-- Tarjetas de totales Glassmorphism --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl group-hover:bg-emerald-500/30 transition-all"></div>
            <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5"><span class="text-lg">📈</span> Ingresos</p>
            <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['ingresos'], 0, ',', '.') }}</p>
        </div>
        
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/20 rounded-full blur-2xl group-hover:bg-red-500/30 transition-all"></div>
            <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5"><span class="text-lg">📉</span> Egresos</p>
            <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['egresos'], 0, ',', '.') }}</p>
        </div>
        
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/20 rounded-full blur-2xl group-hover:bg-blue-500/30 transition-all"></div>
            <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5"><span class="text-lg">💵</span> Efectivo</p>
            <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['efectivo'], 0, ',', '.') }}</p>
        </div>
        
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group">
            <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/20 rounded-full blur-2xl group-hover:bg-purple-500/30 transition-all"></div>
            <p class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5"><span class="text-lg">🏦</span> Banco/Consig.</p>
            <p class="text-2xl font-black text-slate-800 dark:text-white z-10">${{ number_format($totales['consignacion'], 0, ',', '.') }}</p>
        </div>
        
        <div class="glass-card p-5 flex flex-col justify-center items-center relative overflow-hidden group border-2 {{ $totales['saldo'] >= 0 ? 'border-teal-500/40 bg-teal-500/5 dark:bg-teal-900/10' : 'border-orange-500/40 bg-orange-500/5 dark:bg-orange-900/10' }} col-span-2 lg:col-span-1">
            <div class="absolute -right-6 -top-6 w-32 h-32 {{ $totales['saldo'] >= 0 ? 'bg-teal-500/30' : 'bg-orange-500/30' }} rounded-full blur-3xl group-hover:scale-110 transition-all"></div>
            <p class="text-xs font-bold {{ $totales['saldo'] >= 0 ? 'text-teal-700 dark:text-teal-400' : 'text-orange-700 dark:text-orange-400' }} uppercase tracking-widest mb-1 z-10 flex items-center gap-1.5"><span class="text-lg">⚖️</span> Saldo General</p>
            <p class="text-3xl font-black {{ $totales['saldo'] >= 0 ? 'text-teal-700 dark:text-teal-400' : 'text-orange-700 dark:text-orange-400' }} z-10">${{ number_format($totales['saldo'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Panel principal --}}
    <div class="glass-card p-6 md:p-8">
        <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                    <span class="text-3xl">💰</span> Movimientos de Caja
                </h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Gestión de ingresos, egresos y flujo de efectivo</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if(!auth()->user()->isInvitado())
                    <a href="{{ route('caja.create') }}" class="btn-primary shadow-emerald-500/30 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 border-none text-base px-5">
                        ➕ Nuevo Movimiento
                    </a>
                @endif
            </div>
        </div>

        {{-- Filtros --}}
        <form action="{{ route('caja.index') }}" method="GET" class="flex flex-wrap items-center gap-3 mb-6 p-4 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm">
            <div class="relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">🔍</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Persona o empresa..." class="glass-input pl-9 w-40 sm:w-48 text-sm">
            </div>
            <select name="tipo_movimiento" class="glass-input w-auto text-sm font-semibold">
                <option value="">Todos los tipos</option>
                <option value="ingreso" {{ request('tipo_movimiento') === 'ingreso' ? 'selected' : '' }}>📈 Ingreso</option>
                <option value="egreso"  {{ request('tipo_movimiento') === 'egreso'  ? 'selected' : '' }}>📉 Egreso</option>
            </select>
            <select name="tipo_pago" class="glass-input w-auto text-sm font-semibold">
                <option value="">Todos los pagos</option>
                <option value="efectivo"     {{ request('tipo_pago') === 'efectivo'     ? 'selected' : '' }}>💵 Efectivo</option>
                <option value="consignacion" {{ request('tipo_pago') === 'consignacion' ? 'selected' : '' }}>🏦 Consignación</option>
            </select>
            <div class="flex items-center gap-2">
                <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="glass-input w-auto text-sm">
                <span class="text-gray-400 text-sm">a</span>
                <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="glass-input w-auto text-sm">
            </div>
            <button type="submit" class="btn-primary py-2 px-4 shadow-blue-500/20 text-sm">Filtrar</button>
            @if(request()->anyFilled(['search','tipo_movimiento','tipo_pago','fecha_desde','fecha_hasta']))
                <a href="{{ route('caja.index') }}" class="btn-ghost text-sm">Limpiar</a>
            @endif
        </form>

        {{-- Tabla adaptable --}}
        <div class="overflow-x-auto rounded-2xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
            <table class="ts-table responsive-table w-full">
                <thead>
                    <tr>
                        <th class="text-center">Fecha</th>
                        <th>Persona / Empresa</th>
                        <th>Concepto</th>
                        <th class="text-center">Tipo</th>
                        <th class="text-center">Pago</th>
                        <th class="text-right">Monto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $m)
                    <tr class="{{ $m->estado === 'anulado' ? 'row-anulado' : '' }}">
                        <td data-label="Fecha:" class="text-center font-medium">{{ $m->fecha->format('d/m/Y') }}</td>
                        <td data-label="Entidad:" class="font-bold text-slate-800 dark:text-white leading-tight">
                            {{ $m->persona }}
                            @if($m->empresa) <div class="text-xs text-gray-500 font-semibold mt-0.5">{{ $m->empresa }}</div> @endif
                        </td>
                        <td data-label="Concepto:" class="font-medium">
                            {{ $m->concepto->nombre }}
                        </td>
                        <td data-label="Tipo:" class="text-center">
                            @if($m->estado === 'anulado')
                                <span class="pill pill-anulado" title="Anulado">🚫 Anulado</span>
                            @else
                                <span class="pill {{ $m->tipo_movimiento === 'ingreso' ? 'pill-done' : 'pill-egreso' }}">
                                    {{ $m->tipo_movimiento === 'ingreso' ? '📈 Ingreso' : '📉 Egreso' }}
                                </span>
                            @endif
                        </td>
                        <td data-label="Pago:" class="text-center">
                            <span class="pill {{ $m->tipo_pago === 'efectivo' ? 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-opacity-20' : 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-opacity-20' }}">
                                {{ $m->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}
                            </span>
                        </td>
                        <td data-label="Monto:" class="text-right font-black text-lg {{ $m->tipo_movimiento === 'ingreso' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $m->tipo_movimiento === 'ingreso' ? '+' : '-' }}${{ number_format($m->monto, 0, ',', '.') }}
                        </td>
                        <td data-label="Acciones:" class="text-center">
                            <div class="flex justify-end md:justify-center gap-2">
                                <a href="{{ route('caja.print', $m->id) }}" target="_blank" class="btn-ghost px-3 py-1.5 text-xs" title="Imprimir">🖨️</a>
                                @if(!auth()->user()->isInvitado())
                                    <a href="{{ route('caja.edit', $m->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Editar">✏️</a>

                                    @if($m->estado !== 'anulado')
                                        <button type="button" onclick="openAnularModal('{{ route('caja.anular', $m->id) }}')" class="btn-ghost px-3 py-1.5 text-xs text-orange-600 border-orange-500/20 hover:bg-orange-500/10" title="Anular movimiento">🚫</button>
                                    @endif
                                    <button type="button" onclick="openPwdModal('{{ route('caja.destroy', $m->id) }}')" class="btn-danger px-3 py-1.5 text-xs" title="Eliminar definitivamente">🗑️</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-16 text-center">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="text-6xl drop-shadow-md mb-2">💰</div>
                                <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin movimientos registrados</h3>
                                <p class="text-gray-500 font-medium max-w-sm mb-4">Registra el primer ingreso o egreso de caja para comenzar el control de flujo.</p>
                                @if(!auth()->user()->isInvitado())
                                    <a href="{{ route('caja.create') }}" class="btn-primary bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 border-none shadow-emerald-500/30">
                                        ➕ Nuevo Movimiento
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            {{ $movimientos->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<script>
    function openPwdModal(actionUrl) {
        const modal = document.getElementById('pwd-modal');
        const card = document.getElementById('pwd-modal-card');
        document.getElementById('delete-pwd-form').action = actionUrl;
        document.getElementById('pwd-input').value = '';
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
            document.getElementById('pwd-input').focus();
        }, 10);
    }
    
    function closePwdModal() {
        const modal = document.getElementById('pwd-modal');
        const card = document.getElementById('pwd-modal-card');
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function openAnularModal(actionUrl) {
        const modal = document.getElementById('pwd-anular-modal');
        const card = document.getElementById('pwd-anular-card');
        document.getElementById('anular-pwd-form').action = actionUrl;
        document.getElementById('pwd-anular-input').value = '';
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            card.classList.remove('scale-95', 'opacity-0');
            document.getElementById('pwd-anular-input').focus();
        }, 10);
    }
    
    function closeAnularModal() {
        const modal = document.getElementById('pwd-anular-modal');
        const card = document.getElementById('pwd-anular-card');
        modal.classList.add('opacity-0');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.addEventListener('keydown', e => { 
        if (e.key === 'Escape') {
            closePwdModal();
            closeAnularModal();
        }
    });
</script>
@endsection
