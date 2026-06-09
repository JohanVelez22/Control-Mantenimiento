@extends('layouts.app')
@section('content')
<div class="max-w-4xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <div class="flex flex-wrap justify-between items-start gap-3 mb-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('mantenimientos.index') }}" class="text-gray-500 hover:text-blue-500">⬅️ Volver</a>
                <h2 class="text-2xl font-bold">📋 {{ $mantenimiento->id_orden }}</h2>
                @if($mantenimiento->estado === 'anulado')
                    <span class="inline-flex px-3 py-1 rounded-xl text-sm font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        🚫 Anulado
                    </span>
                @else
                    <span class="inline-flex px-3 py-1 rounded-xl text-sm font-bold {{ $mantenimiento->estado === 'terminado' ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' }}">
                        {{ $mantenimiento->estado === 'terminado' ? '✅ Terminado' : '⏳ Pendiente' }}
                    </span>
                @endif
            </div>
            @if(!auth()->user()->isInvitado())
            <div class="flex gap-2 flex-wrap">
                <a href="{{ route('mantenimientos.edit', $mantenimiento) }}" class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border border-yellow-500/30 hover:bg-yellow-500/40 rounded-xl px-3 py-2 font-semibold transition-all text-sm">✏️ Editar</a>
                <form action="{{ route('mantenimientos.duplicate', $mantenimiento) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-500/30 hover:bg-blue-500/40 rounded-xl px-3 py-2 font-semibold transition-all text-sm">📋 Duplicar</button>
                </form>
                @if($mantenimiento->fecha_salida)
                <a href="{{ route('mantenimientos.factura', $mantenimiento) }}" target="_blank" class="inline-flex items-center gap-1 bg-gray-500/20 text-gray-700 dark:text-gray-300 border border-gray-500/30 hover:bg-gray-500/40 rounded-xl px-3 py-2 font-semibold transition-all text-sm">🖨️ Factura</a>
                @endif
            </div>
            @endif
        </div>

        {{-- Datos del mantenimiento --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">
            <div><span class="font-semibold text-gray-500 dark:text-gray-400">Cliente:</span> <span class="font-bold">{{ $mantenimiento->equipo->cliente->nombre ?? '-' }}</span></div>
            <div><span class="font-semibold text-gray-500 dark:text-gray-400">Equipo:</span> <span>{{ $mantenimiento->equipo->marca }} {{ $mantenimiento->equipo->modelo }} ({{ $mantenimiento->equipo->nombre }})</span></div>
            <div><span class="font-semibold text-gray-500 dark:text-gray-400">Técnico:</span> <span>{{ $mantenimiento->tecnico->nombre }}</span></div>
            <div><span class="font-semibold text-gray-500 dark:text-gray-400">Tipo:</span> <span>{{ ucfirst($mantenimiento->tipo) }} / {{ ucfirst($mantenimiento->reparacion) }}</span></div>
            <div><span class="font-semibold text-gray-500 dark:text-gray-400">Entrada:</span> <span>{{ $mantenimiento->fecha_entrada->format('d/m/Y') }}</span></div>
            <div><span class="font-semibold text-gray-500 dark:text-gray-400">Salida:</span> <span>{{ $mantenimiento->fecha_salida?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="md:col-span-2"><span class="font-semibold text-gray-500 dark:text-gray-400">Descripción:</span> <span>{{ $mantenimiento->descripcion }}</span></div>
        </div>
    </div>

    {{-- Resumen financiero --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1">💰 Costo Total</p>
            <p class="text-2xl font-black text-blue-700 dark:text-blue-300">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</p>
        </div>
        <div class="bg-green-500/10 border border-green-500/30 rounded-2xl p-4 text-center">
            <p class="text-xs font-semibold text-green-600 dark:text-green-400 mb-1">✅ Total Abonado</p>
            <p class="text-2xl font-black text-green-700 dark:text-green-300">${{ number_format($mantenimiento->total_abonado, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl p-4 text-center {{ $mantenimiento->saldo_pendiente > 0 ? 'bg-red-500/10 border border-red-500/30' : 'bg-teal-500/10 border border-teal-500/30' }}">
            <p class="text-xs font-semibold {{ $mantenimiento->saldo_pendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-teal-600 dark:text-teal-400' }} mb-1">⚖️ Saldo Pendiente</p>
            <p class="text-2xl font-black {{ $mantenimiento->saldo_pendiente > 0 ? 'text-red-700 dark:text-red-300' : 'text-teal-700 dark:text-teal-300' }}">${{ number_format($mantenimiento->saldo_pendiente, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Panel de Abonos --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-4">💳 Abonos / Pagos Parciales</h3>

        {{-- Formulario nuevo abono --}}
        @if(!auth()->user()->isInvitado() && $mantenimiento->estado !== 'anulado')
        <form action="{{ route('abonos.store', $mantenimiento) }}" method="POST" class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 mb-5 space-y-3">
            @csrf
            <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">Registrar nuevo abono</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Monto ($) *</label>
                    <input type="number" step="0.01" name="monto" required min="0.01" placeholder="0.00"
                           class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Fecha *</label>
                    <input type="date" name="fecha" required value="{{ date('Y-m-d') }}"
                           class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Tipo de pago *</label>
                    <select name="tipo_pago" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="consignacion">🏦 Consignación</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Descripción</label>
                    <input type="text" name="descripcion" placeholder="Opcional..."
                           class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
            </div>
            <button type="submit" class="px-5 py-2 bg-blue-500 text-white rounded-xl font-bold hover:bg-blue-600 shadow-lg shadow-blue-500/30 transition-all text-sm">
                ➕ Registrar Abono
            </button>
        </form>
        @elseif($mantenimiento->estado === 'anulado')
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-5">
            <p class="text-sm font-semibold text-red-600 dark:text-red-400 text-center">No se pueden agregar abonos a una orden anulada.</p>
        </div>
        @endif

        {{-- Listado de abonos --}}
        @if($mantenimiento->abonos->isEmpty())
            <p class="text-center text-gray-400 py-6">Sin abonos registrados aún.</p>
        @else
        <div class="space-y-2">
            @foreach($mantenimiento->abonos->sortByDesc('fecha') as $abono)
            <div class="flex flex-wrap items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ $abono->tipo_pago === 'efectivo' ? '💵' : '🏦' }}</span>
                    <div>
                        <p class="font-bold text-green-600 dark:text-green-400">${{ number_format($abono->monto, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500">{{ $abono->fecha->format('d/m/Y') }} · {{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Consignación' }} · por {{ $abono->user->name }}</p>
                        @if($abono->descripcion) <p class="text-xs text-gray-400">{{ $abono->descripcion }}</p> @endif
                    </div>
                </div>
                @if(!auth()->user()->isInvitado())
                <form action="{{ route('abonos.destroy', $abono) }}" method="POST" data-confirm-delete="¿Eliminar este abono de ${{ number_format($abono->monto, 0, ',', '.') }}?">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1 bg-red-500/20 text-red-700 dark:text-red-400 border border-red-500/30 hover:bg-red-500/40 rounded-xl px-2 py-1 font-semibold transition-all text-xs">🗑️</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
