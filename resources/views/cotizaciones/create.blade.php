@extends('layouts.app')
@section('content')
<div class="max-w-6xl mx-auto">
    <div class="glass-card p-6 md:p-8">
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('cotizaciones.index') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">📝 Crear Nueva Cotización</h2>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Arma un presupuesto sin afectar el inventario o la caja</p>
            </div>
        </div>

        <form action="{{ route('cotizaciones.store') }}" method="POST" id="cotizacion-form" class="space-y-5">
            @csrf

            <div class="flex flex-col md:flex-row gap-5 p-5 bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-500/20 rounded-2xl">
                <div class="w-full flex-1 min-w-0">
                    <label class="field-label">Cliente *</label>
                    <select name="cliente_id" required class="glass-input focus:ring-blue-500" data-tomselect>
                        <option value="">Buscar cliente...</option>
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}" {{ old('cliente_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->nombre }} ({{ $c->identificacion }})
                            </option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="w-full md:w-48 flex-shrink-0">
                    <label class="field-label">Fecha *</label>
                    <input type="date" name="fecha" required value="{{ old('fecha', date('Y-m-d')) }}" class="glass-input focus:ring-blue-500">
                    @error('fecha') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div class="w-full md:w-48 flex-shrink-0">
                    <label class="field-label">Validez (Días) *</label>
                    <input type="number" name="validez_dias" required min="1" value="{{ old('validez_dias', 15) }}" class="glass-input focus:ring-blue-500 text-center">
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
                    <table class="ts-table" id="items-table">
                        <thead class="bg-white/30 dark:bg-slate-800/40 backdrop-blur-sm text-slate-700 dark:text-slate-200 font-semibold border-b border-slate-200/50 dark:border-slate-700/50">
                            <tr>
                                <th class="w-32 px-4 py-3">Tipo</th>
                                <th class="w-[45%] px-4 py-3">Descripción / Producto</th>
                                <th class="w-24 text-center px-4 py-3">Cant.</th>
                                <th class="min-w-[150px] text-right px-4 py-3">Precio Un. ($)</th>
                                <th class="min-w-[150px] text-right px-4 py-3">Subtotal</th>
                                <th class="w-12 text-center px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body" class="divide-y divide-slate-200/50 dark:divide-slate-700/50 bg-white/20 dark:bg-slate-900/20">
                            <!-- La primera fila se inserta por JS -->
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200/50 dark:border-slate-700/50 bg-white/30 dark:bg-slate-800/30">
                                <td colspan="4" class="text-right font-bold text-slate-500 uppercase tracking-widest text-xs pt-4 pb-4">Total Cotización:</td>
                                <td class="text-right font-black text-2xl text-blue-600 dark:text-blue-400 pt-4 pb-4 pr-4" id="total-display">$0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div>
                <label class="field-label">Notas para el cliente</label>
                <textarea name="notas" rows="3" class="glass-input resize-y focus:ring-blue-500" placeholder="Ej: Precios sujetos a cambio sin previo aviso. Tiempo estimado de entrega: 3 días hábiles."></textarea>
            </div>

            <div class="flex flex-col md:flex-row justify-end gap-3 pt-6 border-t border-gray-200/50 dark:border-white/10 mt-6">
                <a href="{{ route('cotizaciones.index') }}" class="btn-cancel">↩️ Cancelar</a>
                <button type="submit" class="btn-primary px-8">
                    💾 Guardar Cotización
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TomSelect CDN -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>


@php
    $stocksJson = $stocks->map(fn($s) => [
        'id' => $s->id,
        'nombre' => $s->producto,
        'precio' => $s->precio_venta,
        'cantidad' => $s->cantidad,
    ])->values()->all();
@endphp
@include('cotizaciones._scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Inicializamos con una fila limpia
    agregarFila();
    // Inicializar select de cliente principal
    const clienteSelect = document.querySelector('select[name="cliente_id"]');
    if (clienteSelect) {
        window.initGlassTomSelect(clienteSelect);
    }
});
</script>
@endsection
