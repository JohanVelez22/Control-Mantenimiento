@extends('layouts.app')
@section('content')
<div class="space-y-5">

    {{-- Tabs de navegación --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-bold text-lg mr-2">📊 Reportes Financieros</span>
            <a href="{{ route('reportes.financiero.diario') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-blue-500/10 text-blue-700 dark:text-blue-300 hover:bg-blue-500/20">
                📅 Diario
            </a>
            <a href="{{ route('reportes.financiero.acumulado') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500 text-white shadow-lg shadow-purple-500/30">
                📈 Acumulado
            </a>
            <a href="{{ route('reportes.financiero.operaciones') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500/10 text-teal-700 dark:text-teal-300 hover:bg-teal-500/20">
                🔍 Operaciones
            </a>
        </div>
    </div>

    {{-- Filtro de rango --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <label class="font-semibold text-sm">Desde:</label>
            <input type="date" name="desde" value="{{ $desde->toDateString() }}"
                   class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            <label class="font-semibold text-sm">Hasta:</label>
            <input type="date" name="hasta" value="{{ $hasta->toDateString() }}"
                   class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            <button class="bg-purple-500 hover:bg-purple-600 text-white font-bold px-5 py-2 rounded-xl text-sm transition-all shadow-lg shadow-purple-500/30">
                Ver Período
            </button>
        </form>
        <p class="text-xs text-gray-500 mt-2">
            Período: <strong>{{ $desde->format('d/m/Y') }}</strong> al <strong>{{ $hasta->format('d/m/Y') }}</strong>
        </p>
    </div>

    {{-- KPIs principales --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-2xl p-5 text-center">
            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-2">🔧 Mantenimientos</p>
            <p class="text-3xl font-black text-blue-700 dark:text-blue-300">{{ $acumulado['total_mantenimientos'] }}</p>
            <p class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-1">${{ number_format($acumulado['facturado_mant'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-purple-500/10 border border-purple-500/30 rounded-2xl p-5 text-center">
            <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-2">⚡ Electrónica</p>
            <p class="text-3xl font-black text-purple-700 dark:text-purple-300">{{ $acumulado['total_electronicas'] }}</p>
            <p class="text-sm font-semibold text-purple-600 dark:text-purple-400 mt-1">${{ number_format($acumulado['facturado_elec'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-orange-500/10 border border-orange-500/30 rounded-2xl p-5 text-center">
            <p class="text-xs font-semibold text-orange-600 dark:text-orange-400 mb-2">📦 Compras</p>
            <p class="text-3xl font-black text-orange-700 dark:text-orange-300">{{ $acumulado['total_compras'] }}</p>
            <p class="text-sm font-semibold text-orange-600 dark:text-orange-400 mt-1">${{ number_format($acumulado['compras_inventario'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-green-500/10 border border-green-500/30 rounded-2xl p-5 text-center">
            <p class="text-xs font-semibold text-green-600 dark:text-green-400 mb-2">🛒 Ventas Inv.</p>
            <p class="text-3xl font-black text-green-700 dark:text-green-300">{{ $acumulado['total_ventas'] }}</p>
            <p class="text-sm font-semibold text-green-600 dark:text-green-400 mt-1">${{ number_format($acumulado['ventas_inventario'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Balance consolidado --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5 text-center">
            <p class="text-xs font-semibold text-emerald-600 mb-2">💵 Total Ingresos Reales (Caja)</p>
            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">${{ number_format($acumulado['ingresos_caja'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-5 text-center">
            <p class="text-xs font-semibold text-red-600 mb-2">💸 Total Egresos Reales (Caja)</p>
            <p class="text-2xl font-black text-red-700 dark:text-red-300">${{ number_format($acumulado['egresos_caja'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl p-5 text-center {{ $acumulado['balance_neto'] >= 0 ? 'bg-teal-500/10 border border-teal-500/30' : 'bg-red-500/10 border border-red-500/30' }}">
            <p class="text-xs font-semibold {{ $acumulado['balance_neto'] >= 0 ? 'text-teal-600' : 'text-red-600' }} mb-2">⚖️ Balance Neto</p>
            <p class="text-2xl font-black {{ $acumulado['balance_neto'] >= 0 ? 'text-teal-700 dark:text-teal-300' : 'text-red-700 dark:text-red-300' }}">
                ${{ number_format($acumulado['balance_neto'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- Saldos pendientes --}}
    @if($acumulado['saldo_pendiente_venta'] > 0 || $acumulado['saldo_pendiente_compra'] > 0)
    <div class="bg-yellow-500/10 border border-yellow-400/40 rounded-2xl p-5">
        <h3 class="font-bold text-yellow-700 dark:text-yellow-300 mb-3">⚠️ Saldos Pendientes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($acumulado['saldo_pendiente_venta'] > 0)
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-yellow-300">
                <span class="text-2xl">🛒</span>
                <div>
                    <p class="text-xs text-gray-500">Por cobrar (Ventas)</p>
                    <p class="font-black text-yellow-700 dark:text-yellow-300 text-lg">${{ number_format($acumulado['saldo_pendiente_venta'], 0, ',', '.') }}</p>
                </div>
            </div>
            @endif
            @if($acumulado['saldo_pendiente_compra'] > 0)
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-3 rounded-xl border border-yellow-300">
                <span class="text-2xl">📦</span>
                <div>
                    <p class="text-xs text-gray-500">Por pagar (Compras)</p>
                    <p class="font-black text-yellow-700 dark:text-yellow-300 text-lg">${{ number_format($acumulado['saldo_pendiente_compra'], 0, ',', '.') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
