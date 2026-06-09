@extends('layouts.app')

@section('content')

{{-- Modal de contraseña para eliminar cierre --}}
<div id="pwd-cierre-modal" class="fixed inset-0 z-[300] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    <div class="relative bg-white/95 dark:bg-gray-800/95 backdrop-blur-md border border-red-300/40 dark:border-red-700/50 rounded-2xl shadow-2xl p-6 max-w-sm w-full">
        <div class="text-center mb-4">
            <div class="text-5xl mb-2">🔓</div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Eliminar Cierre</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Esta acción desbloquea el día. Ingresa tu contraseña o la del administrador.</p>
        </div>
        <form id="delete-cierre-form" method="POST" class="space-y-4">
            @csrf @method('DELETE')
            <input type="password" name="password_confirm" id="pwd-cierre-input" required placeholder="Contraseña..."
                   class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-red-500 transition-all">
            <div class="flex gap-3">
                <button type="button" onclick="closeCierrePwd()" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-300 transition-all">Cancelar</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-all shadow-lg shadow-red-500/30">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<div class="space-y-4">

    {{-- Preview del día actual (si no cerrado) --}}
    @if(!$yaExiste && $preview)
    <div class="bg-gradient-to-br from-blue-500/10 to-indigo-500/10 border border-blue-400/30 rounded-2xl p-6">
        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
            <div>
                <h2 class="text-xl font-bold">📅 Cierre del Día — {{ \Carbon\Carbon::parse($hoy)->format('d/m/Y') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Vista previa — aún no cerrado</p>
            </div>
            <form action="{{ route('cierre.store') }}" method="POST">
                @csrf
                <input type="hidden" name="fecha" value="{{ $hoy }}">
                <div class="flex gap-2 items-center">
                    <input type="text" name="observaciones" placeholder="Observaciones (opcional)..."
                           class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2 text-sm focus:outline-none w-56">
                    <button type="submit" class="px-5 py-2 bg-indigo-500 text-white rounded-xl font-bold hover:bg-indigo-600 shadow-lg shadow-indigo-500/30 transition-all">
                        🔒 Cerrar Día
                    </button>
                </div>
            </form>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-3 text-center">
                <p class="text-xs font-semibold text-green-700 dark:text-green-400">Ingresos</p>
                <p class="text-lg font-black text-green-700 dark:text-green-300">${{ number_format($preview['total_ingresos'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 text-center">
                <p class="text-xs font-semibold text-red-700 dark:text-red-400">Egresos</p>
                <p class="text-lg font-black text-red-700 dark:text-red-300">${{ number_format($preview['total_egresos'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-3 text-center">
                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400">Efectivo</p>
                <p class="text-lg font-black text-blue-700 dark:text-blue-300">${{ number_format($preview['efectivo'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-3 text-center">
                <p class="text-xs font-semibold text-purple-700 dark:text-purple-400">Consignación</p>
                <p class="text-lg font-black text-purple-700 dark:text-purple-300">${{ number_format($preview['consignacion'], 0, ',', '.') }}</p>
            </div>
            <div class="{{ $preview['saldo_final'] >= 0 ? 'bg-teal-500/10 border border-teal-500/30' : 'bg-orange-500/10 border border-orange-500/30' }} rounded-xl p-3 text-center">
                <p class="text-xs font-semibold {{ $preview['saldo_final'] >= 0 ? 'text-teal-700 dark:text-teal-400' : 'text-orange-700 dark:text-orange-400' }}">Saldo Final</p>
                <p class="text-lg font-black {{ $preview['saldo_final'] >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-orange-700 dark:text-orange-300' }}">${{ number_format($preview['saldo_final'], 0, ',', '.') }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-2">{{ $preview['num_movimientos'] }} movimiento(s) registrados hoy</p>
    </div>
    @elseif($yaExiste)
    <div class="bg-green-500/10 border border-green-500/30 rounded-2xl p-4 flex items-center gap-3">
        <span class="text-3xl">✅</span>
        <div>
            <p class="font-bold text-green-700 dark:text-green-300">Día de hoy ya cerrado y bloqueado.</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Para desbloquearlo, elimina el cierre de hoy usando la contraseña.</p>
        </div>
    </div>
    @endif

    {{-- Historial de cierres --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <h2 class="text-2xl font-bold mb-4">📊 Historial de Cierres de Caja</h2>

        <div class="w-full">
            <table class="w-full text-left border-collapse block md:table responsive-table">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-200 dark:bg-gray-700 text-center">
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Fecha</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Ingresos</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Egresos</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Efectivo</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Consignación</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Saldo</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Mov.</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Registró</th>
                        <th class="p-3 border border-gray-300 dark:border-gray-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="block md:table-row-group">
                    @forelse($cierres as $c)
                    <tr class="bg-white dark:bg-gray-800 md:bg-transparent md:hover:bg-gray-100 md:dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 md:border-none rounded-xl md:rounded-none mb-4 md:mb-0 block md:table-row shadow-sm md:shadow-none transition-colors">
                        <td class="p-3 block md:table-cell flex justify-between border-b border-gray-100 dark:border-gray-700 md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Fecha:</span>
                            <span class="font-bold">{{ $c->fecha->format('d/m/Y') }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Ingresos:</span>
                            <span class="text-green-600 dark:text-green-400 font-semibold">${{ number_format($c->total_ingresos, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Egresos:</span>
                            <span class="text-red-600 dark:text-red-400 font-semibold">${{ number_format($c->total_egresos, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Efectivo:</span>
                            <span class="text-blue-600 dark:text-blue-400">${{ number_format($c->efectivo, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Consignación:</span>
                            <span class="text-purple-600 dark:text-purple-400">${{ number_format($c->consignacion, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Saldo:</span>
                            <span class="font-black {{ $c->saldo_final >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-orange-600 dark:text-orange-400' }}">${{ number_format($c->saldo_final, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Mov.:</span>
                            <span class="text-sm">{{ $c->num_movimientos }}</span>
                        </td>
                        <td class="p-3 block md:table-cell flex justify-between border-b md:border border-gray-100 dark:border-gray-700 md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            <span class="md:hidden font-bold text-gray-500">Registró:</span>
                            <span class="text-xs text-gray-500">{{ $c->user->name }}</span>
                        </td>
                        <td class="p-3 block md:table-cell bg-gray-50 dark:bg-gray-800/50 md:bg-transparent md:border md:border-gray-300 md:dark:border-gray-500 md:text-center">
                            @if(auth()->user()->isAdmin())
                                <button type="button" onclick="openCierrePwd('{{ route('cierre.destroy', $c->id) }}')"
                                        class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 rounded-xl px-3 py-1 font-semibold transition-all text-sm">
                                    🗑️ Eliminar
                                </button>
                            @else
                                <span class="text-xs text-gray-400">🔒 Bloqueado</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr class="block md:table-row">
                        <td colspan="9" class="p-12 text-center block md:table-cell">
                            <div class="flex flex-col items-center gap-4">
                                <div class="text-6xl">📊</div>
                                <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Sin cierres registrados</h3>
                                <p class="text-gray-500 dark:text-gray-400">Realiza el primer cierre del día usando el panel de arriba.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $cierres->links() }}</div>
    </div>
</div>

<script>
    function openCierrePwd(url) {
        document.getElementById('delete-cierre-form').action = url;
        document.getElementById('pwd-cierre-input').value = '';
        const m = document.getElementById('pwd-cierre-modal');
        m.classList.remove('hidden'); m.classList.add('flex');
        setTimeout(() => document.getElementById('pwd-cierre-input').focus(), 100);
    }
    function closeCierrePwd() {
        const m = document.getElementById('pwd-cierre-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCierrePwd(); });
</script>
@endsection



