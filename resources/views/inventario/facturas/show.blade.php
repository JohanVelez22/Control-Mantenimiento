@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-card p-6 md:p-8">

        {{-- Alertas de estado especiales --}}
        @if($factura->estado === 'pendiente_pago')
        <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-4 p-4 rounded-2xl bg-yellow-500/10 border border-yellow-500/30">
            <div class="flex items-center gap-4">
                <div class="text-3xl">⏳</div>
                <div>
                    <h3 class="font-black text-yellow-700 dark:text-yellow-400 uppercase tracking-tight">Pago Pendiente</h3>
                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                        Saldo por {{ $factura->tipo_movimiento === 'compra' ? 'pagar al proveedor' : 'cobrar al cliente' }}.
                    </p>
                </div>
            </div>
            <div class="text-center md:text-right">
                <p class="text-[10px] font-bold text-yellow-600/70 uppercase tracking-widest">Saldo Actual</p>
                <p class="text-2xl font-black text-yellow-700 dark:text-yellow-400">${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</p>
            </div>
        </div>
        @endif
        
        @if($factura->estado === 'anulada')
        <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-center shadow-[0_4px_20px_rgba(239,68,68,0.15)]">
            <span class="text-2xl drop-shadow-md">🚫</span>
            <h3 class="font-black text-red-600 dark:text-red-400 mt-1 uppercase tracking-widest">Factura Anulada</h3>
            <p class="text-sm font-medium text-red-500/80 mt-1">Este documento carece de validez comercial y contable.</p>
        </div>
        @endif

        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6">
            <div>
                <a href="{{ route('inventario.facturas') }}" class="btn-ghost px-3 py-1.5 text-xs mb-3 inline-flex">⬅️ Volver a facturas</a>
                <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                    {{ $factura->numero_factura }}
                    <span class="pill {{ $factura->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done' }} text-sm py-1 px-3">
                        {{ $factura->tipo_movimiento === 'compra' ? '📦 COMPRA' : '🛒 VENTA' }}
                    </span>
                </h2>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-2">{{ $factura->fecha->format('d \d\e F \d\e Y') }}</p>
            </div>
            
            <div class="flex items-center gap-3 w-full md:w-auto">
                <a href="{{ route('inventario.facturas.print', $factura->id) }}" target="_blank" class="btn-ghost flex-1 md:flex-none justify-center border-blue-500/20 text-blue-600">
                    🖨️ Imprimir
                </a>
                
                @if($factura->estado !== 'anulada' && !auth()->user()->isInvitado())
                <form action="{{ route('inventario.facturas.anular', $factura->id) }}" method="POST" class="flex-1 md:flex-none" data-confirm-delete="¿Estás seguro de anular la factura {{ $factura->numero_factura }}? El inventario y los movimientos de caja se revertirán.">
                    @csrf
                    <button type="submit" class="btn-danger w-full justify-center">
                        🚫 Anular
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Entidad (cliente o proveedor) --}}
        <div class="mb-8 p-5 rounded-2xl bg-white/40 dark:bg-slate-800/40 border border-gray-200/50 dark:border-white/5 backdrop-blur-sm flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-xl shadow-lg shrink-0">
                {{ $factura->tipo_movimiento === 'compra' ? '🏭' : '👤' }}
            </div>
            <div>
                <p class="text-[10px] font-black text-indigo-500 tracking-widest uppercase mb-1">{{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}</p>
                <p class="font-black text-xl text-slate-800 dark:text-white leading-tight">
                    {{ $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? '—' }}
                </p>
                <p class="text-sm font-semibold text-gray-500 mt-1">
                    ID: {{ $factura->facturable->identificacion ?? 'N/A' }} 
                    @if(isset($factura->facturable->email)) <span class="mx-2">•</span> Correo: {{ $factura->facturable->email }} @endif
                </p>
            </div>
        </div>

        {{-- Tabla de ítems --}}
        <div class="mb-8">
            <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Detalle del Documento</h3>
            <div class="overflow-x-auto rounded-2xl border border-gray-200/50 dark:border-white/5 bg-white/30 dark:bg-slate-900/30 backdrop-blur-md">
                <table class="ts-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center w-24">Cant.</th>
                            <th class="text-right w-36">Precio Unitario</th>
                            <th class="text-right w-36">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($factura->items as $item)
                        <tr>
                            <td class="font-bold text-slate-800 dark:text-white">{{ $item->stock->producto }}</td>
                            <td class="text-center font-bold">{{ $item->cantidad }}</td>
                            <td class="text-right font-mono">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                            <td class="text-right font-black text-blue-600 dark:text-cyan-400">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Totales --}}
        <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-6">
            {{-- Notas --}}
            <div class="w-full md:w-1/2">
                @if($factura->observaciones)
                <div class="p-4 bg-gray-50/50 dark:bg-gray-800/30 rounded-xl border border-gray-200/50 dark:border-gray-700/50">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Observaciones</p>
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $factura->observaciones }}</p>
                </div>
                @endif
            </div>
            
            {{-- Resumen --}}
            <div class="w-full md:w-1/2 bg-white/50 dark:bg-slate-800/50 rounded-2xl p-5 border border-gray-200/50 dark:border-white/5 backdrop-blur-md">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Documento</span>
                    <span class="text-2xl font-black text-slate-800 dark:text-white">${{ number_format($factura->total_documento, 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-t border-gray-200/50 dark:border-white/10">
                    <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Total Pagado</span>
                    <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">${{ number_format($factura->total_pagado, 0, ',', '.') }}</span>
                </div>
                
                @if($factura->saldo_pendiente > 0)
                <div class="flex justify-between items-center py-2 border-t border-gray-200/50 dark:border-white/10">
                    <span class="text-sm font-bold text-red-500">Saldo Pendiente</span>
                    <span class="text-lg font-black text-red-500">${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
            <span>Usuario: {{ $factura->user->name ?? '—' }}</span>
            <span>Registro: {{ $factura->created_at->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>
</div>
@endsection
