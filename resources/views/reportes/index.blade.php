@extends('layouts.app')

@section('title', 'Informes y Reportes')

@section('content')
<div class="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-2">
            📊 Informes y Reportes
        </h1>
        <p class="text-gray-500 dark:text-gray-400 font-semibold mt-1">Análisis financiero y de operaciones del mes.</p>
    </div>
    
    <form action="{{ route('reportes.index') }}" method="GET" class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-xl border border-gray-300 dark:border-gray-600 shadow-sm">
        <select name="mes" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
            @for($i=1; $i<=12; $i++)
                <option value="{{ $i }}" {{ $mes == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
            @endfor
        </select>
        <select name="anio" class="bg-transparent border-none focus:ring-0 text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
            @for($i=date('Y')-2; $i<=date('Y'); $i++)
                <option value="{{ $i }}" {{ $anio == $i ? 'selected' : '' }}>{{ $i }}</option>
            @endfor
        </select>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-lg transition-colors">
            🔍 Filtrar
        </button>
    </form>
</div>

<div class="space-y-6">

    {{-- INFORME ACUMULADO --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-4">📈 Informe Acumulado (Mes {{ $mes }}/{{ $anio }})</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-green-500/10 border border-green-500/30 p-4 rounded-xl text-center">
                <p class="text-xs font-bold text-green-600 dark:text-green-400 mb-1">Ingresos</p>
                <p class="text-2xl font-black text-green-700 dark:text-green-300">${{ number_format($acumulado['ingresos'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-red-500/10 border border-red-500/30 p-4 rounded-xl text-center">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 mb-1">Egresos / Gastos</p>
                <p class="text-2xl font-black text-red-700 dark:text-red-300">${{ number_format($acumulado['egresos'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-blue-500/10 border border-blue-500/30 p-4 rounded-xl text-center">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 mb-1">Costos de Mantenimiento</p>
                <p class="text-2xl font-black text-blue-700 dark:text-blue-300">${{ number_format($acumulado['costos_operativos'], 0, ',', '.') }}</p>
            </div>
            <div class="{{ $acumulado['utilidad_neta'] >= 0 ? 'bg-teal-500/10 border-teal-500/30' : 'bg-red-500/10 border-red-500/30' }} border p-4 rounded-xl text-center">
                <p class="text-xs font-bold {{ $acumulado['utilidad_neta'] >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-red-600 dark:text-red-400' }} mb-1">Utilidad / Saldo Neta</p>
                <p class="text-2xl font-black {{ $acumulado['utilidad_neta'] >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-red-700 dark:text-red-300' }}">${{ number_format($acumulado['utilidad_neta'], 0, ',', '.') }}</p>
            </div>
        </div>
        
        <div class="mt-4 border-t border-gray-200 dark:border-gray-700 pt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                <span class="font-bold text-gray-600 dark:text-gray-300 text-sm">Valorización de Inventario (Costo)</span>
                <span class="font-black text-gray-800 dark:text-white">${{ number_format($acumulado['inventario_costo'], 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                <span class="font-bold text-gray-600 dark:text-gray-300 text-sm">Utilidad Esperada del Inventario</span>
                <span class="font-black text-blue-600 dark:text-blue-400">${{ number_format($acumulado['inventario_utilidad_esperada'], 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- INFORME POR OPERACIONES --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-4">🧮 Informe por Operaciones (Tipos de Dinero)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Efectivo --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="bg-gray-100 dark:bg-gray-800 p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="font-bold">💵 Efectivo Global</span>
                    <span class="font-black text-lg">${{ number_format($operaciones['efectivo'], 0, ',', '.') }}</span>
                </div>
                <div class="p-3 space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-green-600 font-semibold">Ingresos en Efectivo</span>
                        <span class="font-bold text-green-700">+${{ number_format($operaciones['ingresos_efectivo'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-600 font-semibold">Egresos en Efectivo</span>
                        <span class="font-bold text-red-700">-${{ number_format($operaciones['egresos_efectivo'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Consignacion --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <div class="bg-gray-100 dark:bg-gray-800 p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <span class="font-bold">🏦 Consignación Global</span>
                    <span class="font-black text-lg">${{ number_format($operaciones['consignacion'], 0, ',', '.') }}</span>
                </div>
                <div class="p-3 space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-green-600 font-semibold">Ingresos por Banco</span>
                        <span class="font-bold text-green-700">+${{ number_format($operaciones['ingresos_consignacion'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-red-600 font-semibold">Egresos por Banco</span>
                        <span class="font-bold text-red-700">-${{ number_format($operaciones['egresos_consignacion'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- INFORME DETALLADO (Transacciones) --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-4">📝 Informe Detallado (Transacciones del Mes)</h3>
        
        @if($transacciones->isEmpty())
            <div class="text-center py-12">
                <div class="text-5xl mb-4 opacity-50">📂</div>
                <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Sin Movimientos</h3>
                <p class="text-gray-500 mt-2">No se encontraron transacciones activas en este período.</p>
            </div>
        @else
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <table class="w-full text-left border-collapse responsive-table">
                <thead class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                    <tr>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 text-center font-bold">Fecha</th>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 font-bold">Concepto</th>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 font-bold">Persona / Empresa</th>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 text-center font-bold">Tipo</th>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 text-center font-bold">Pago</th>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 text-center font-bold">Monto</th>
                        <th class="p-3 border-b border-gray-300 dark:border-gray-600 text-center font-bold">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transacciones as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <td class="p-3 text-center text-sm font-semibold whitespace-nowrap">{{ \Carbon\Carbon::parse($tx->fecha)->format('d/m/Y') }}</td>
                        <td class="p-3 text-sm">
                            <span class="font-bold">{{ $tx->concepto->nombre ?? 'N/A' }}</span>
                            @if($tx->descripcion)
                                <span class="block text-xs text-gray-500 italic mt-1">{{ $tx->descripcion }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-sm">
                            <span class="font-bold">{{ $tx->persona }}</span>
                            @if($tx->empresa)
                                <span class="block text-xs text-gray-500 italic mt-1">🏢 {{ $tx->empresa }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            @if($tx->tipo_movimiento === 'ingreso')
                                <span class="inline-block px-2 py-1 bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 rounded-md text-xs font-bold border border-green-500/20">Ingreso</span>
                            @else
                                <span class="inline-block px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 rounded-md text-xs font-bold border border-red-500/20">Egreso</span>
                            @endif
                        </td>
                        <td class="p-3 text-center capitalize text-sm font-semibold">{{ $tx->tipo_pago }}</td>
                        <td class="p-3 text-center text-sm">
                            <span class="font-black {{ $tx->tipo_movimiento === 'ingreso' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $tx->tipo_movimiento === 'ingreso' ? '+' : '-' }}${{ number_format($tx->monto, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="p-3 text-center text-xs text-gray-500">{{ $tx->user->name ?? 'Sistema' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $transacciones->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
