@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('inventario.facturas') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
        <div>
            <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
                ✏️ Editar Factura {{ $factura->numero_factura }}
            </h2>
        </div>
    </div>

    <form action="{{ route('inventario.facturas.update', $factura->id) }}" method="POST" class="glass-card p-6 md:p-8 space-y-6">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="field-label">Fecha de Factura *</label>
                <input type="date" name="fecha" required value="{{ old('fecha', $factura->fecha->format('Y-m-d')) }}" class="glass-input">
            </div>

            <div>
                <label class="field-label">Total Pagado ($) *</label>
                <input type="text" name="total_pagado" id="total_pagado" required value="{{ old('total_pagado', number_format($factura->total_pagado, 0, '', '.')) }}" oninput="window.formatCurrencyInput(this)" class="glass-input font-bold text-emerald-600 text-center">
                <p class="text-[11px] text-gray-400 mt-1">El monto total del documento es ${{ number_format($factura->total_documento, 0, ',', '.') }}. Modificar el pago ajustará el saldo y el estado automáticamente.</p>
            </div>

            <div class="md:col-span-2">
                <label class="field-label">Observaciones</label>
                <textarea name="observaciones" rows="3" class="glass-input">{{ old('observaciones', $factura->observaciones) }}</textarea>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-200/50 dark:border-white/10 flex justify-end gap-3">
            <a href="{{ route('inventario.facturas') }}" class="btn-ghost">Cancelar</a>
            <button type="submit" class="btn-primary">💾 Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection
