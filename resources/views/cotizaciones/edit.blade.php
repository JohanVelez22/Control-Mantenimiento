@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto">
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

            <div class="cotizacion-header-grid p-5 glass-card">
                <div>
                    <label class="field-label">Cliente / Proveedor *</label>
                    <select name="facturable_global" required class="glass-input focus:ring-blue-500" data-placeholder="Buscar cliente o proveedor...">
                        <option value="">Buscar cliente o proveedor...</option>
                        @foreach($clientes as $c)
                            <option value="Cliente:{{ $c->id }}" data-tipo="{{ $c->tipo_cliente }}" {{ $selFacturable == "Cliente:{$c->id}" ? 'selected' : '' }}>
                                👤 Cliente: {{ $c->nombre }} ({{ $c->identificacion }}){{ $c->tipo_cliente === 'tecnico' ? ' 🛠️ Técnico' : '' }}
                            </option>
                        @endforeach
                        @if(isset($proveedores) && $proveedores->isNotEmpty())
                            @foreach($proveedores as $prov)
                                <option value="Proveedor:{{ $prov->id }}" data-tipo="proveedor" {{ $selFacturable == "Proveedor:{$prov->id}" ? 'selected' : '' }}>
                                    🏢 Proveedor: {{ $prov->nombre_razon_social }} ({{ $prov->identificacion }})
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('facturable_global') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    @error('cliente_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">Fecha *</label>
                    <input type="date" name="fecha" required value="{{ old('fecha', \Carbon\Carbon::parse($cotizacion->fecha)->format('Y-m-d')) }}" class="glass-input reportes-date-input focus:ring-blue-500" style="width: 8.5rem !important;">
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
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-5">
                    <h3 class="font-bold text-lg text-slate-800 dark:text-white flex items-center gap-2">
                        <span>🛍️</span> Ítems a Cotizar
                    </h3>
                    <button type="button" onclick="agregarFila()" class="btn-add w-full sm:w-auto justify-center">
                        ➕ Agregar línea
                    </button>
                </div>

                <div class="overflow-x-auto pb-2 max-h-[500px] overflow-y-auto cotizacion-table-scroll min-h-[165px]">
                    <table class="ts-table w-full table-fixed" id="items-table">
                        <thead>
                            <tr>
                                <th class="col-tipo text-center">Tipo</th>
                                <th class="col-descripcion">Descripción / Producto</th>
                                <th class="col-cantidad text-center">Cant.</th>
                                <th class="col-precio text-right">Precio Un. ($)</th>
                                <th class="col-subtotal text-right">Subtotal</th>
                                <th class="col-accion"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            {{-- Filas renderizadas directamente desde el servidor (sin flash/salto visual) --}}
                            @forelse($cotizacion->items as $idx => $item)
                                @php
                                    $isStock = $item->tipo === 'stock';
                                    $cant = $item->cantidad;
                                    $precio = (float)$item->precio_unitario;
                                    $subtotal = $cant * $precio;
                                @endphp
                                <tr class="item-row bg-white/20 dark:bg-slate-900/20 border-t border-slate-200/50 dark:border-white/10 hover:bg-white/50 dark:hover:bg-slate-900/30 transition-colors">
                                    <td class="col-tipo align-middle">
                                        <select name="items[{{ $idx }}][tipo]" class="tipo-select glass-input py-1.5 px-2 font-bold w-full whitespace-nowrap">
                                            <option value="libre" {{ !$isStock ? 'selected' : '' }}>🛠️ Servicio / Libre</option>
                                            <option value="stock" {{ $isStock ? 'selected' : '' }}>📦 Producto Stock</option>
                                        </select>
                                    </td>
                                    <td class="desc-cell col-descripcion align-middle">
                                        @if($isStock)
                                            <select class="stock-select glass-input py-1.5" required data-placeholder="Seleccionar producto del stock...">
                                                <option value="">Seleccionar producto del stock...</option>
                                                @foreach($stocks as $s)
                                                    <option value="{{ $s->id }}" {{ $item->item_id == $s->id ? 'selected' : '' }} data-precio-venta="{{ $s->precio_venta }}" data-precio-tecnico="{{ $s->precio_tecnico }}" data-nombre="{{ $s->producto }}" data-cantidad="{{ $s->cantidad }}">
                                                        {{ $s->producto }} (Disp: {{ $s->cantidad }}) — P.Venta: ${{ number_format($s->precio_venta, 0, ',', '.') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="items[{{ $idx }}][item_id]" class="stock-id-input" value="{{ $item->item_id }}">
                                            <input type="hidden" name="items[{{ $idx }}][descripcion]" class="stock-desc-input" value="{{ $item->descripcion }}">
                                        @else
                                            <input type="text" name="items[{{ $idx }}][descripcion]" value="{{ $item->descripcion }}" class="desc-input glass-input py-1.5 focus:ring-blue-500 w-full min-w-0" placeholder="Descripción de mano de obra o servicio..." required>
                                        @endif
                                    </td>
                                    <td class="col-cantidad align-middle relative text-center">
                                        <input type="number" name="items[{{ $idx }}][cantidad]" min="1" max="999" value="{{ $cant }}" required class="cantidad-input glass-input py-1.5 text-center focus:ring-blue-500 w-full font-bold">
                                        <div class="stock-warning text-[10px] text-orange-500 font-bold absolute -bottom-3 left-0 w-full text-center hidden">Sin stock</div>
                                    </td>
                                    <td class="col-precio align-middle">
                                        <input type="text" name="items[{{ $idx }}][precio_unitario]" id="precio_unitario_real_{{ $idx }}" value="{{ $precio }}" required class="hidden">
                                        <input type="text" id="precio_unitario_visual_{{ $idx }}" value="{{ number_format($precio, 0, ',', '.') }}" placeholder="0" oninput="window.formatCurrencyDual(this, 'precio_unitario_real_{{ $idx }}'); actualizarSubtotal(this.closest('tr'))" required class="precio-input glass-input py-1.5 px-2 text-right focus:ring-blue-500 font-bold text-slate-800 dark:text-white w-full">
                                    </td>
                                    <td class="col-subtotal text-right font-black text-blue-600 dark:text-blue-400 text-base subtotal-cell align-middle">${{ number_format($subtotal, 0, ',', '.') }}</td>
                                    <td class="col-accion align-middle text-right">
                                        <button type="button" onclick="eliminarFila(this)" class="btn-danger btn-icon shadow-sm hover:scale-105 transition-all" title="Eliminar ítem">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="item-row bg-white/20 dark:bg-slate-900/20 border-t border-slate-200/50 dark:border-white/10 hover:bg-white/50 dark:hover:bg-slate-900/30 transition-colors">
                                    <td class="col-tipo align-middle">
                                        <select name="items[0][tipo]" class="tipo-select glass-input py-1.5 px-2 font-bold w-full whitespace-nowrap">
                                            <option value="libre" selected>🛠️ Servicio / Libre</option>
                                            <option value="stock">📦 Producto Stock</option>
                                        </select>
                                    </td>
                                    <td class="desc-cell col-descripcion align-middle">
                                        <input type="text" name="items[0][descripcion]" value="" class="desc-input glass-input py-1.5 focus:ring-blue-500 w-full min-w-0" placeholder="Descripción de mano de obra o servicio..." required>
                                    </td>
                                    <td class="col-cantidad align-middle relative text-center">
                                        <input type="number" name="items[0][cantidad]" min="1" max="999" value="1" required class="cantidad-input glass-input py-1.5 text-center focus:ring-blue-500 w-full font-bold">
                                        <div class="stock-warning text-[10px] text-orange-500 font-bold absolute -bottom-3 left-0 w-full text-center hidden">Sin stock</div>
                                    </td>
                                    <td class="col-precio align-middle">
                                        <input type="text" name="items[0][precio_unitario]" id="precio_unitario_real_0" value="0" required class="hidden">
                                        <input type="text" id="precio_unitario_visual_0" value="0" placeholder="0" oninput="window.formatCurrencyDual(this, 'precio_unitario_real_0'); actualizarSubtotal(this.closest('tr'))" required class="precio-input glass-input py-1.5 px-2 text-right focus:ring-blue-500 font-bold text-slate-800 dark:text-white w-full">
                                    </td>
                                    <td class="col-subtotal text-right font-black text-blue-600 dark:text-blue-400 text-base subtotal-cell align-middle">$0</td>
                                    <td class="col-accion align-middle text-right">
                                        <button type="button" onclick="eliminarFila(this)" class="btn-danger btn-icon shadow-sm hover:scale-105 transition-all" title="Eliminar ítem">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-300 dark:border-gray-600 bg-gray-50/50 dark:bg-gray-800/50">
                                <td colspan="3" class="py-4"></td>
                                <td class="col-precio py-4 text-center align-middle">
                                    <span class="font-bold text-slate-500 uppercase tracking-widest text-xs whitespace-nowrap">Total Cotización:</span>
                                </td>
                                <td colspan="2" class="py-4 text-right pr-3 align-middle">
                                    <span class="font-black text-2xl text-blue-600 dark:text-blue-400 whitespace-nowrap" id="total-display">${{ number_format($cotizacion->total, 0, ',', '.') }}</span>
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

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('cotizaciones.index') }}" class="btn-cancel w-full sm:w-auto justify-center text-center">✕<space>Cancelar</a>
                <button type="submit" class="btn-save w-full sm:w-auto justify-center">
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
function initCotizacionRows() {
    // Inicializar select de cliente principal si no fue tomado automáticamente
    const clienteSelect = document.querySelector('select[name="cliente_id"]');
    if (clienteSelect && !clienteSelect.classList.contains('tomselected') && typeof window.initGlassTomSelect === 'function') {
        window.initGlassTomSelect(clienteSelect);
    }
    // Conectar las filas existentes que vienen renderizadas desde el servidor
    document.querySelectorAll('.item-row').forEach(bindFilaCotizacion);
    filaIndex = document.querySelectorAll('.item-row').length || 1;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCotizacionRows);
} else {
    initCotizacionRows();
}
</script>
@endsection
