@extends('layouts.app')

@section('content')
{{-- Modal de contraseña para eliminar --}}
<div id="pwd-modal" class="fixed inset-0 z-[300] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-red-300/40 dark:border-red-700/50 rounded-2xl shadow-2xl p-6 max-w-sm w-full">
        <div class="text-center mb-4">
            <div class="text-5xl mb-2">🔒</div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Confirmar eliminación</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tu contraseña o la del administrador para continuar.</p>
        </div>
        <form id="delete-pwd-form" method="POST" class="space-y-4">
            @csrf @method('DELETE')
            <div>
                <input type="password" name="password_confirm" id="pwd-input" required placeholder="Contraseña..."
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-red-500 transition-all">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closePwdModal()" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-all shadow-lg shadow-red-500/30">Eliminar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal de contraseña para anular --}}
<div id="pwd-anular-modal" class="fixed inset-0 z-[300] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-orange-300/40 dark:border-orange-700/50 rounded-2xl shadow-2xl p-6 max-w-sm w-full">
        <div class="text-center mb-4">
            <div class="text-5xl mb-2">🚫</div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Confirmar anulación</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ingresa tu contraseña para anular este registro.</p>
        </div>
        <form id="anular-pwd-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <input type="password" name="password_confirm" id="pwd-anular-input" required placeholder="Contraseña..."
                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-orange-500 transition-all">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeAnularModal()" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-orange-500 text-white rounded-xl font-semibold hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/30">Anular</button>
            </div>
        </form>
    </div>
</div>

<div class="space-y-4">
    {{-- Tarjetas de totales --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="col-span-2 md:col-span-1 bg-green-500/10 border border-green-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-1">📈 Ingresos</p>
            <p class="text-xl font-black text-green-700 dark:text-green-300">${{ number_format($totales['ingresos'], 0, ',', '.') }}</p>
        </div>
        <div class="col-span-2 md:col-span-1 bg-red-500/10 border border-red-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-1">📉 Egresos</p>
            <p class="text-xl font-black text-red-700 dark:text-red-300">${{ number_format($totales['egresos'], 0, ',', '.') }}</p>
        </div>
        <div class="col-span-2 md:col-span-1 bg-blue-500/10 border border-blue-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">💵 Efectivo</p>
            <p class="text-xl font-black text-blue-700 dark:text-blue-300">${{ number_format($totales['efectivo'], 0, ',', '.') }}</p>
        </div>
        <div class="col-span-2 md:col-span-1 bg-purple-500/10 border border-purple-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-purple-700 dark:text-purple-400 mb-1">🏦 Consignación</p>
            <p class="text-xl font-black text-purple-700 dark:text-purple-300">${{ number_format($totales['consignacion'], 0, ',', '.') }}</p>
        </div>
        <div class="col-span-2 md:col-span-1 rounded-2xl p-4 text-center {{ $totales['saldo'] >= 0 ? 'bg-teal-500/10 border border-teal-500/30' : 'bg-orange-500/10 border border-orange-500/30' }}">
            <p class="text-xs font-semibold {{ $totales['saldo'] >= 0 ? 'text-teal-700 dark:text-teal-400' : 'text-orange-700 dark:text-orange-400' }} mb-1">⚖️ Saldo</p>
            <p class="text-xl font-black {{ $totales['saldo'] >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-orange-700 dark:text-orange-300' }}">${{ number_format($totales['saldo'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Panel principal --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
            <h2 class="text-2xl font-bold">💰 Movimientos de Caja</h2>
            <div class="flex flex-wrap items-center gap-2">
                @if(!auth()->user()->isInvitado())
                    <a href="{{ route('caja.create') }}" class="inline-flex items-center gap-2 bg-green-500/20 text-green-700 dark:text-green-300 border border-green-500/30 hover:bg-green-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all">
                        ➕ Nuevo Movimiento
                    </a>
                @endif
            </div>
        </div>

        {{-- Filtros --}}
        <form action="{{ route('caja.index') }}" method="GET" class="flex flex-wrap gap-2 mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Persona o empresa..." class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 w-40">
            <select name="tipo_movimiento" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-sm focus:outline-none">
                <option value="">Todos los tipos</option>
                <option value="ingreso" {{ request('tipo_movimiento') === 'ingreso' ? 'selected' : '' }}>📈 Ingreso</option>
                <option value="egreso"  {{ request('tipo_movimiento') === 'egreso'  ? 'selected' : '' }}>📉 Egreso</option>
            </select>
            <select name="tipo_pago" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-sm focus:outline-none">
                <option value="">Todos los pagos</option>
                <option value="efectivo"     {{ request('tipo_pago') === 'efectivo'     ? 'selected' : '' }}>💵 Efectivo</option>
                <option value="consignacion" {{ request('tipo_pago') === 'consignacion' ? 'selected' : '' }}>🏦 Consignación</option>
            </select>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-sm focus:outline-none">
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-1.5 text-sm focus:outline-none">
            <button type="submit" class="bg-blue-500 text-white px-4 py-1.5 rounded-xl text-sm font-semibold hover:bg-blue-600 transition-all">Filtrar</button>
            @if(request()->anyFilled(['search','tipo_movimiento','tipo_pago','fecha_desde','fecha_hasta']))
                <a href="{{ route('caja.index') }}" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-4 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition-all">✕ Limpiar</a>
            @endif
        </form>

        {{-- Tabla adaptable --}}
        <div class="w-full">
            <table class="w-full text-left border-collapse block md:table responsive-table">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-200 dark:bg-gray-700 text-center">
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Fecha</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Persona / Empresa</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Concepto</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Tipo</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Pago</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Monto</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="block md:table-row-group">
                    @forelse($movimientos as $m)
                    <tr class="bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-center transition-colors duration-500 md:border-none rounded-xl md:rounded-none mb-4 md:mb-0">
                        <td class="p-3 border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            
                            <span class="text-sm">{{ $m->fecha->format('d/m/Y') }}</span>
                        </td>
                        <td class="p-3 border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500">
                            
                            <span>
                                <span class="font-semibold">{{ $m->persona }}</span>
                                @if($m->empresa) <br><span class="text-xs text-gray-500">{{ $m->empresa }}</span> @endif
                            </span>
                        </td>
                        <td class="p-3 border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            
                            <span class="text-sm">{{ $m->concepto->nombre }}</span>
                        </td>
                        <td class="p-3 border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            
                            @if($m->estado === 'anulado')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400 border border-red-500/30 font-bold shadow-sm" title="Anulado">
                                    🚫
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold {{ $m->tipo_movimiento === 'ingreso' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300' }}">
                                    {{ $m->tipo_movimiento === 'ingreso' ? '📈 Ingreso' : '📉 Egreso' }}
                                </span>
                            @endif
                        </td>
                        <td class="p-3 border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-xs font-bold {{ $m->tipo_pago === 'efectivo' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' }}">
                                {{ $m->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Consignación' }}
                            </span>
                        </td>
                        <td class="p-3 border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            
                            <span class="font-black text-lg {{ $m->tipo_movimiento === 'ingreso' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $m->tipo_movimiento === 'ingreso' ? '+' : '-' }}${{ number_format($m->monto, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="p-3 block md:table-cell bg-gray-50 dark:bg-gray-800/50 md:bg-transparent md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <div class="flex justify-end md:justify-center gap-2 flex-wrap">
                                <a href="{{ route('caja.print', $m->id) }}" target="_blank"
                                   class="inline-flex items-center gap-1 bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">
                                    🖨️
                                </a>
                                @if(!auth()->user()->isInvitado())
                                    <a href="{{ route('caja.edit', $m->id) }}"
                                       class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">
                                        ✏️
                                    </a>
                                    <form action="{{ route('caja.duplicate', $m->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm" title="Duplicar movimiento">
                                            📋
                                        </button>
                                    </form>
                                    @if($m->estado !== 'anulado')
                                        <button type="button"
                                                onclick="openAnularModal('{{ route('caja.anular', $m->id) }}')"
                                                class="inline-flex items-center gap-1 bg-orange-500/20 text-orange-700 dark:text-orange-400 border border-orange-500/30 hover:bg-orange-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm" title="Anular movimiento">
                                            🚫
                                        </button>
                                    @endif
                                    <button type="button"
                                            onclick="openPwdModal('{{ route('caja.destroy', $m->id) }}')"
                                            class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">
                                        🗑️
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr class="block md:table-row">
                        <td colspan="7" class="p-12 text-center block md:table-cell">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <div class="text-6xl">💰</div>
                                <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Sin movimientos registrados</h3>
                                <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">Registra el primer ingreso o egreso de caja.</p>
                                @if(!auth()->user()->isInvitado())
                                    <a href="{{ route('caja.create') }}" class="inline-flex items-center gap-2 bg-green-500 text-white px-6 py-2 rounded-xl font-bold hover:bg-green-600 transition-all shadow-lg shadow-green-500/30">➕ Nuevo Movimiento</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $movimientos->appends(request()->query())->links() }}</div>
    </div>
</div>

<script>
    function openPwdModal(actionUrl) {
        const modal = document.getElementById('pwd-modal');
        document.getElementById('delete-pwd-form').action = actionUrl;
        document.getElementById('pwd-input').value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => document.getElementById('pwd-input').focus(), 100);
    }
    function closePwdModal() {
        const modal = document.getElementById('pwd-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openAnularModal(actionUrl) {
        const modal = document.getElementById('pwd-anular-modal');
        document.getElementById('anular-pwd-form').action = actionUrl;
        document.getElementById('pwd-anular-input').value = '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => document.getElementById('pwd-anular-input').focus(), 100);
    }
    function closeAnularModal() {
        const modal = document.getElementById('pwd-anular-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('keydown', e => { 
        if (e.key === 'Escape') {
            closePwdModal();
            closeAnularModal();
        }
    });
</script>
@endsection





