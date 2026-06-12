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
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-purple-500/10 text-purple-700 dark:text-purple-300 hover:bg-purple-500/20">
                📈 Acumulado
            </a>
            <a href="{{ route('reportes.financiero.operaciones') }}"
               class="px-4 py-2 rounded-xl font-semibold text-sm transition-all bg-teal-500 text-white shadow-lg shadow-teal-500/30">
                🔍 Operaciones
            </a>
        </div>
    </div>

    {{-- Selector de tipo + rango --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-5">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="tipo"
                    class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500 font-semibold">
                @foreach($tipoLabels as $val => $label)
                    <option value="{{ $val }}" {{ $tipo === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="desde" value="{{ $desde->toDateString() }}"
                   class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500">
            <input type="date" name="hasta" value="{{ $hasta->toDateString() }}"
                   class="bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-teal-500">
            <button class="bg-teal-500 hover:bg-teal-600 text-white font-bold px-5 py-2 rounded-xl text-sm transition-all shadow-lg shadow-teal-500/30">
                Filtrar
            </button>
        </form>
    </div>

    {{-- Resultados --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">{{ $tipoLabels[$tipo] }} <span class="text-sm font-normal text-gray-500">({{ $registros->total() }} registros)</span></h3>
        </div>

        @if($registros->isEmpty())
            <div class="text-center py-12">
                <div class="text-5xl mb-3">📭</div>
                <p class="text-gray-500">No hay registros para este filtro en el período seleccionado.</p>
            </div>
        @else

        {{-- Tabla Mantenimientos --}}
        @if($tipo === 'solo_mantenimientos')
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center">
                <thead class="bg-gray-100 dark:bg-gray-700 font-semibold">
                    <tr>
                        <th class="p-2">Orden</th><th class="p-2 text-left">Equipo / Cliente</th>
                        <th class="p-2">Técnico</th><th class="p-2">Entrada</th>
                        <th class="p-2">Costo</th><th class="p-2">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($registros as $m)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="p-2 font-mono font-bold">{{ $m->id_orden }}</td>
                        <td class="p-2 text-left">{{ $m->equipo->nombre ?? '—' }} <span class="text-xs text-gray-500">({{ $m->equipo->cliente->nombre ?? '—' }})</span></td>
                        <td class="p-2">{{ $m->tecnico->nombre ?? '—' }}</td>
                        <td class="p-2">{{ $m->fecha_entrada->format('d/m/Y') }}</td>
                        <td class="p-2 font-bold text-blue-600">${{ number_format($m->costo, 0, ',', '.') }}</td>
                        <td class="p-2"><span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-blue-100 text-blue-800">{{ ucfirst($m->estado) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tabla Electrónica --}}
        @elseif($tipo === 'solo_electronica')
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center">
                <thead class="bg-gray-100 dark:bg-gray-700 font-semibold">
                    <tr>
                        <th class="p-2">Orden</th><th class="p-2 text-left">Dispositivo / Cliente</th>
                        <th class="p-2">Técnico</th><th class="p-2">Entrada</th>
                        <th class="p-2">Costo</th><th class="p-2">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($registros as $e)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="p-2 font-mono font-bold">{{ $e->id_orden }}</td>
                        <td class="p-2 text-left">{{ $e->dispositivo }} <span class="text-xs text-gray-500">({{ $e->cliente }})</span></td>
                        <td class="p-2">{{ $e->tecnico->nombre ?? '—' }}</td>
                        <td class="p-2">{{ $e->fecha_entrada->format('d/m/Y') }}</td>
                        <td class="p-2 font-bold text-purple-600">${{ number_format($e->costo, 0, ',', '.') }}</td>
                        <td class="p-2"><span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-purple-100 text-purple-800">{{ ucfirst($e->estado) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tabla Ingresos / Egresos --}}
        @elseif(in_array($tipo, ['solo_ingresos', 'solo_egresos']))
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center">
                <thead class="bg-gray-100 dark:bg-gray-700 font-semibold">
                    <tr>
                        <th class="p-2">Fecha</th><th class="p-2 text-left">Persona / Empresa</th>
                        <th class="p-2">Concepto</th><th class="p-2">Tipo Pago</th>
                        <th class="p-2">Monto</th><th class="p-2">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($registros as $c)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="p-2">{{ $c->fecha->format('d/m/Y') }}</td>
                        <td class="p-2 text-left">{{ $c->persona ?? $c->empresa ?? '—' }}</td>
                        <td class="p-2 text-xs">{{ $c->concepto->nombre ?? '—' }}</td>
                        <td class="p-2 text-xs capitalize">{{ $c->tipo_pago }}</td>
                        <td class="p-2 font-bold {{ $tipo === 'solo_ingresos' ? 'text-green-600' : 'text-red-600' }}">${{ number_format($c->monto, 0, ',', '.') }}</td>
                        <td class="p-2"><span class="text-xs font-semibold capitalize">{{ $c->estado }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Tabla Compras / Ventas --}}
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-center">
                <thead class="bg-gray-100 dark:bg-gray-700 font-semibold">
                    <tr>
                        <th class="p-2">Número</th><th class="p-2 text-left">Entidad</th>
                        <th class="p-2">Fecha</th><th class="p-2">Ítems</th>
                        <th class="p-2">Total</th><th class="p-2">Pagado</th><th class="p-2">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($registros as $f)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="p-2 font-mono font-bold"><a href="{{ route('inventario.facturas.show', $f->id) }}" class="text-blue-600 hover:underline">{{ $f->numero_factura }}</a></td>
                        <td class="p-2 text-left">{{ $f->facturable->nombre_razon_social ?? $f->facturable->nombre ?? '—' }}</td>
                        <td class="p-2">{{ $f->fecha->format('d/m/Y') }}</td>
                        <td class="p-2">{{ $f->items->count() }}</td>
                        <td class="p-2 font-bold">${{ number_format($f->total_documento, 0, ',', '.') }}</td>
                        <td class="p-2 {{ $f->saldo_pendiente > 0 ? 'text-red-600 font-bold' : 'text-green-600' }}">${{ number_format($f->total_pagado, 0, ',', '.') }}</td>
                        <td class="p-2"><span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-gray-100 text-gray-700">{{ ucfirst(str_replace('_',' ',$f->estado)) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="mt-4">{{ $registros->appends(request()->query())->links() }}</div>
        @endif
    </div>

</div>
@endsection
