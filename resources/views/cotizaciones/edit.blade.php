@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('cotizaciones.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">✏️ Editar Cotización {{ $cotizacion->codigo }}</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Modifica los ítems o detalles de la cotización antes de facturar.</p>
            </div>
        </div>

        <form action="{{ route('cotizaciones.update', $cotizacion) }}" method="POST" id="cotizacion-form" class="space-y-5">
            @csrf
            @method('PUT')

            @php
                $defaultFacturable = '';
                if ($cotizacion->proveedor_id) {
                    $defaultFacturable = 'Proveedor:' . $cotizacion->proveedor_id;
                } elseif ($cotizacion->cliente_id) {
                    $defaultFacturable = 'Cliente:' . $cotizacion->cliente_id;
                }
                $selFacturable = old('facturable_global', $defaultFacturable);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 p-5 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl">
                <div class="md:col-span-2">
                    <label class="field-label">Cliente / Proveedor *</label>
                    <select name="facturable_global" required class="glass-input no-search focus:ring-blue-500" data-placeholder="Seleccionar cliente o proveedor...">
                        <option value="">Seleccionar destinatario...</option>
                        <optgroup label="👤 Clientes">
                            @foreach($clientes as $c)
                                <option value="Cliente:{{ $c->id }}" data-tipo="{{ $c->tipo_cliente }}" {{ $selFacturable == "Cliente:{$c->id}" ? 'selected' : '' }}>
                                    👤 Cliente: {{ $c->nombre }} ({{ $c->identificacion }}){{ $c->tipo_cliente === 'tecnico' ? ' — 🔧 Técnico' : '' }}
                                </option>
                            @endforeach
                        </optgroup>
                        @if(isset($proveedores) && $proveedores->isNotEmpty())
                            <optgroup label="🏢 Proveedores">
                                @foreach($proveedores as $prov)
                                    <option value="Proveedor:{{ $prov->id }}" data-tipo="proveedor" {{ $selFacturable == "Proveedor:{$prov->id}" ? 'selected' : '' }}>
                                        🏢 Proveedor: {{ $prov->nombre_razon_social }} ({{ $prov->identificacion }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('facturable_global') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    @error('cliente_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Fecha *</label>
                    <input type="date" name="fecha" required value="{{ old('fecha', \Carbon\Carbon::parse($cotizacion->fecha)->format('Y-m-d')) }}" class="glass-input focus:ring-blue-500">
                    @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Validez (Días) *</label>
                    <input type="number" name="validez_dias" required min="1" value="{{ old('validez_dias', $cotizacion->validez_dias) }}" class="glass-input focus:ring-blue-500 text-center">
                    @error('validez_dias') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Tabla de ítems --}}
            <div>
                <div class="flex justify-between items-center mb-5">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                        <span>🛍️</span> Ítems a Cotizar
                    </h3>
                    <button type="button" onclick="agregarFila()" class="btn-clean">
                        ➕ Agregar línea
                    </button>
                </div>

                <div class="overflow-x-auto pb-2 max-h-[500px] overflow-y-auto">
                    <table class="ts-table w-full table-fixed" id="items-table">
                        <thead class="bg-white/30 dark:bg-slate-800/40 backdrop-blur-sm text-slate-700 dark:text-slate-200 font-semibold border-b border-slate-200/50 dark:border-slate-700/50">
                            <tr>
                                <th class="col-tipo">Tipo</th>
                                <th class="col-descripcion">Descripción / Producto</th>
                                <th class="col-cantidad text-center">Cant.</th>
                                <th class="col-precio text-right">Precio Un. ($)</th>
                                <th class="col-subtotal text-right">Subtotal</th>
                                <th class="col-accion"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-200/50 dark:divide-slate-700/50 bg-white/20 dark:bg-slate-900/20">
                            <!-- La primera fila se inserta por JS -->
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200/50 dark:border-slate-700/50 bg-white/30 dark:bg-slate-800/30">
                                <td colspan="6" class="px-4 py-4">
                                    <div class="flex justify-end items-center gap-4">
                                        <span class="font-bold text-slate-500 uppercase tracking-widest text-xs whitespace-nowrap">Total Cotización:</span>
                                        <span class="font-black text-2xl text-blue-600 dark:text-blue-400 whitespace-nowrap" id="total-display">$0</span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div>
                <label class="field-label">Notas para el cliente</label>
                <textarea name="notas" rows="3" class="glass-input resize-y focus:ring-blue-500" placeholder="Ej: Precios sujetos a cambio sin previo aviso. Tiempo estimado de entrega: 3 días hábiles.">{{ old('notas', $cotizacion->notas) }}</textarea>
            </div>

            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('cotizaciones.index') }}" class="btn-cancel">↩️ Cancelar</a>
                <button type="submit" class="btn-save">
                    💾 Actualizar Cotización
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $stocksJson = $stocks->map(fn($s) => [
        'id'             => $s->id,
        'nombre'         => $s->producto,
        'precio_venta'   => (float)$s->precio_venta,
        'precio_tecnico' => (float)($s->precio_tecnico > 0 ? $s->precio_tecnico : $s->precio_venta),
        'cantidad'       => $s->cantidad,
    ])->values()->all();
@endphp
@include('cotizaciones._scripts')

@php
    $existingItems = $cotizacion->items->map(function($i) {
        return [
            'tipo' => $i->tipo,
            'item_id' => $i->item_id,
            'descripcion' => $i->descripcion,
            'cantidad' => $i->cantidad,
            'precio_unitario' => (float)$i->precio_unitario,
        ];
    })->values()->all();
@endphp

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar select de cliente principal si no fue tomado automáticamente
    const clienteSelect = document.querySelector('select[name="cliente_id"]');
    if (clienteSelect && !clienteSelect.classList.contains('tomselected') && typeof window.initGlassTomSelect === 'function') {
        window.initGlassTomSelect(clienteSelect);
    }

    const itemsExistentes = @json($existingItems);
    if (!itemsExistentes || itemsExistentes.length === 0) {
        if (typeof agregarFila === 'function') agregarFila();
    } else {
        itemsExistentes.forEach(item => {
            if (typeof agregarFila === 'function') agregarFila(item);
        });
    }
});
</script>
@endsection
