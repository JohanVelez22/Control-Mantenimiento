@extends('layouts.app')
@section('content')
<div class="space-y-5">

    {{-- Tabs de navegación --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-bold text-lg mr-2">📊 Reportes Financieros</span>
            <a href="{{ route('reportes.financiero.diario') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500 text-white shadow-lg shadow-blue-500/30">
                📅 Diario
            </a>
            <a href="{{ route('reportes.financiero.acumulado') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20">
                📈 Acumulado
            </a>
            <a href="{{ route('reportes.financiero.operaciones') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20">
                🔍 Operaciones
            </a>
        </div>
    </div>

    {{-- Filtro de fecha --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <label class="font-semibold text-sm">📅 Fecha:</label>
            <input type="date" name="fecha" value="{{ $fecha }}"
                   class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <button class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-5 py-2 rounded-xl text-sm transition-all shadow-lg shadow-blue-500/30">
                Ver Día
            </button>
            <a href="{{ route('reportes.financiero.diario') }}" class="text-gray-400 hover:text-gray-600 text-sm px-3 py-2">Hoy</a>
        </form>
        <p class="text-xs text-gray-500 mt-2">Mostrando todos los movimientos del <strong>{{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM [de] YYYY') }}</strong></p>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-green-500/10 border border-green-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-green-600 mb-1">📈 Ingresos</p>
            <p class="text-xl font-black text-green-700 dark:text-green-300">${{ number_format($resumen['total_ingresos'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-red-600 mb-1">📉 Egresos</p>
            <p class="text-xl font-black text-red-700 dark:text-red-300">${{ number_format($resumen['total_egresos'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-blue-600 mb-1">🔧 Mantenimientos</p>
            <p class="text-xl font-black text-blue-700 dark:text-blue-300">${{ number_format($resumen['total_mantenimientos'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-gray-500/10 border border-gray-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-gray-600 mb-1">🚫 Anulados</p>
            <p class="text-xl font-black text-gray-700 dark:text-gray-300">{{ $resumen['total_anulados'] }}</p>
        </div>
    </div>

    {{-- Tabla de movimientos del día --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Movimientos del Día ({{ $movimientos->count() }})</h3>
            <a href="{{ route('reportes.financiero.diario', array_merge(request()->query(), ['export' => 'excel'])) }}"
               class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/40 rounded-xl px-3 py-2 font-semibold text-sm transition-all">
                📊 Exportar Excel
            </a>
        </div>

        @if($movimientos->isEmpty())
            <div class="text-center py-12">
                <div class="text-5xl mb-3">📭</div>
                <p class="text-gray-500">No hubo movimientos en esta fecha.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700 font-semibold text-center">
                        <tr>
                            <th class="p-3">Tipo</th>
                            <th class="p-3 text-left">Descripción</th>
                            <th class="p-3">Estado</th>
                            <th class="p-3">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($movimientos as $mov)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-center {{ $mov['estado'] === 'anulado' || $mov['estado'] === 'anulada' ? 'opacity-50' : '' }}">
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-lg text-xs font-bold
                                    bg-{{ $mov['color'] }}-100 text-{{ $mov['color'] }}-800
                                    dark:bg-{{ $mov['color'] }}-900/40 dark:text-{{ $mov['color'] }}-300">
                                    {{ $mov['icono'] }} {{ ucfirst($mov['tipo']) }}
                                </span>
                            </td>
                            <td class="p-3 text-left text-gray-700 dark:text-gray-300">{{ $mov['descripcion'] }}</td>
                            <td class="p-3">
                                <span class="text-xs font-semibold text-gray-500">{{ ucfirst($mov['estado'] ?? '—') }}</span>
                            </td>
                            <td class="p-3 font-bold {{ in_array($mov['tipo'], ['ingreso','venta','mantenimiento','electronica']) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                ${{ number_format($mov['monto'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
