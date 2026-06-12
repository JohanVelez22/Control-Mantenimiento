@extends('layouts.app')
@section('content')
<div class="glass-card p-6 md:p-8">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-2">
                <span class="text-3xl">🧾</span> Facturas de Inventario
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Historial de compras y ventas de artículos</p>
        </div>
        <div class="flex flex-wrap gap-3">
            @if(!auth()->user()->isInvitado())
            <a href="{{ route('inventario.compra.create') }}" class="btn-primary shadow-orange-500/30 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border-none">
                📦 Nueva Compra
            </a>
            <a href="{{ route('inventario.venta.create') }}" class="btn-primary shadow-emerald-500/30 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 border-none">
                🛒 Nueva Venta
            </a>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="flex flex-wrap items-center gap-3 mb-6 p-4 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm">
        <select name="tipo" class="glass-input w-auto font-semibold">
            <option value="">Todos los tipos</option>
            <option value="compra" {{ request('tipo') === 'compra' ? 'selected' : '' }}>📦 Compras</option>
            <option value="venta"  {{ request('tipo') === 'venta'  ? 'selected' : '' }}>🛒 Ventas</option>
        </select>
        <select name="estado" class="glass-input w-auto font-semibold">
            <option value="">Todos los estados</option>
            <option value="emitida"        {{ request('estado') === 'emitida'        ? 'selected' : '' }}>✅ Emitida</option>
            <option value="pendiente_pago" {{ request('estado') === 'pendiente_pago' ? 'selected' : '' }}>⏳ Pendiente</option>
            <option value="anulada"        {{ request('estado') === 'anulada'        ? 'selected' : '' }}>🚫 Anulada</option>
        </select>
        <div class="flex items-center gap-2">
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="glass-input w-auto text-sm">
            <span class="text-gray-400 text-sm">a</span>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="glass-input w-auto text-sm">
        </div>
        <button type="submit" class="btn-primary py-2 px-4 shadow-blue-500/20 text-sm">Filtrar</button>
        <a href="{{ route('inventario.facturas') }}" class="btn-ghost text-sm">Limpiar</a>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
        <table class="ts-table">
            <thead>
                <tr>
                    <th class="text-center">Número</th>
                    <th class="text-center">Tipo</th>
                    <th>Entidad (Cliente/Prov.)</th>
                    <th class="text-center">Fecha</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Pagado</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facturas as $f)
                <tr class="{{ $f->estado === 'anulada' ? 'row-anulado' : '' }}">
                    <td class="text-center font-mono font-bold text-sm text-slate-700 dark:text-slate-300">{{ $f->numero_factura }}</td>
                    <td class="text-center">
                        <span class="pill {{ $f->tipo_movimiento === 'compra' ? 'bg-orange-100 text-orange-800 border-orange-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200' }} dark:bg-opacity-20 dark:border-opacity-30">
                            {{ $f->tipo_movimiento === 'compra' ? '📦 Compra' : '🛒 Venta' }}
                        </span>
                    </td>
                    <td class="font-bold text-slate-800 dark:text-white">
                        {{ $f->facturable->nombre_razon_social ?? $f->facturable->nombre ?? '—' }}
                    </td>
                    <td class="text-center font-medium">{{ $f->fecha->format('d/m/Y') }}</td>
                    <td class="text-right font-black text-slate-800 dark:text-white text-base">
                        ${{ number_format($f->total_documento, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        <span class="font-bold text-sm {{ $f->saldo_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            ${{ number_format($f->total_pagado, 0, ',', '.') }}
                        </span>
                        @if($f->saldo_pendiente > 0 && $f->estado !== 'anulada')
                            <div class="text-[10px] text-red-500 uppercase tracking-tight mt-0.5 font-bold">Saldo: ${{ number_format($f->saldo_pendiente, 0, ',', '.') }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $stClass = 'pill-pending';
                            if($f->estado === 'emitida') $stClass = 'pill-done';
                            if($f->estado === 'anulada') $stClass = 'pill-anulado';
                            
                            $label = ucfirst(str_replace('_', ' ', $f->estado));
                            if($f->estado === 'pendiente_pago') $label = 'Pendiente';
                        @endphp
                        <span class="pill {{ $stClass }}">
                            {{ $label }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('inventario.facturas.show', $f->id) }}" class="btn-ghost px-3 py-1.5 text-xs" title="Ver Detalles">👁️ Ver</a>
                            <a href="{{ route('inventario.facturas.print', $f->id) }}" target="_blank" class="btn-ghost px-3 py-1.5 text-xs" title="Imprimir">🖨️</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="text-6xl drop-shadow-md mb-2">🧾</div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white">Sin facturas registradas</h3>
                            <p class="text-gray-500 font-medium max-w-sm mb-4">No se han realizado compras ni ventas de inventario.</p>
                            @if(!auth()->user()->isInvitado())
                            <div class="flex gap-3 justify-center">
                                <a href="{{ route('inventario.compra.create') }}" class="btn-primary bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border-none shadow-orange-500/30">📦 Comprar</a>
                                <a href="{{ route('inventario.venta.create') }}"  class="btn-primary bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 border-none shadow-emerald-500/30">🛒 Vender</a>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-6 flex justify-end">
        {{ $facturas->appends(request()->query())->links() }}
    </div>
</div>
@endsection
