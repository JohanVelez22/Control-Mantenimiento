@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
 <div class="glass-card p-6 md:p-8">

 {{-- Alertas de estado especiales --}}
 @if($factura->estado !== 'anulada' && $factura->saldo_pendiente > 0)
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
  <div class="flex flex-col md:flex-row items-center gap-4">
      <div class="text-center md:text-right">
          <p class="font-black text-yellow-700 dark:text-yellow-400 uppercase tracking-tight">Saldo Actual</p>
          <p class="text-2xl font-black text-yellow-700 dark:text-yellow-400">${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</p>
      </div>

      @if(!auth()->user()->isInvitado() && $movimientoPadre)
          <button type="button" onclick="openAbonoFacturaModal()" class="btn-primary py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/20 flex items-center gap-2 shrink-0 text-sm">
              <span>💵</span> Registrar Abono
          </button>
      @endif
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
 <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6 md:pb-8 relative z-20">
  <div class="flex items-center gap-3">
  <a href="{{ route('inventario.facturas') }}" class="btn-ghost px-3 py-2 text-xl" title="Volver">⬅️</a>
  <div>
  <h2 class="text-3xl font-black text-slate-800 dark:text-white tracking-tight flex items-center gap-3">
  {{ $factura->numero_factura }}
  <span class="pill {{ $factura->tipo_movimiento === 'compra' ? 'pill-pending' : 'pill-done' }} text-sm py-1 px-3">
  {{ $factura->tipo_movimiento === 'compra' ? '📦 COMPRA' : '🛒 VENTA' }}
  </span>
  </h2>
  <p class="text-sm font-bold text-gray-500 dark:text-gray-400 mt-2">{{ \Carbon\Carbon::parse($factura->fecha)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</p>
  </div>
  </div>
 
 <div class="flex items-center gap-3 shrink-0">
 @if($factura->estado !== 'anulada' && !auth()->user()->isInvitado() && $movimientoPadre)
  <a href="{{ route('caja.edit', $movimientoPadre->id) }}" class="btn-ghost border-emerald-500/20 text-emerald-600 dark:text-emerald-400" title="Ver detalle del movimiento en el módulo de Caja">📦 Ver en Caja</a>
 @endif

  <x-print-dropdown 
      :url="route('inventario.facturas.print', $factura->id)"
      label="Imprimir"
      colorClass="border-blue-500/20 text-blue-600 dark:text-blue-400"
      id="print-factura-{{ $factura->id }}"
  />
 
	@if($factura->estado !== 'anulada' && !auth()->user()->isInvitado())
	<button type="button" onclick="openAnularModal('{{ route('inventario.facturas.anular', $factura->id) }}', false)" class="btn-danger">🚫 Anular</button>
	@endif
 </div>
 </div>

 {{-- Entidad (cliente o proveedor) --}}
 <div class="mb-8 p-5 rounded-2xl bg-white/20 dark:bg-slate-900/35 border border-white/50 dark:border-white/5 backdrop-blur-md flex items-start gap-4 shadow-sm min-w-0">
 <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-blue-500 flex items-center justify-center text-white text-xl shadow-lg shrink-0">
 {{ $factura->tipo_movimiento === 'compra' ? '🏭' : '👤' }}
 </div>
 <div class="min-w-0">
 <p class="text-[11px] font-bold text-slate-900 dark:text-white tracking-wider uppercase mb-1">{{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}</p>
 <p class="font-medium text-xl text-slate-800 dark:text-slate-200 leading-tight break-words">
 {{ $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? '—' }}
 </p>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-1 break-words">
                ID: {{ $factura->facturable->identificacion ?? 'N/A' }}
                @php
                    $telEntidad = $factura->facturable->movil ?? $factura->facturable->telefono ?? null;
                @endphp
                @if($telEntidad)
                    <span class="mx-2"></span> Tel: <x-whatsapp-link :telefono="$telEntidad" class="text-slate-600 dark:text-slate-300 font-medium" />
                @endif
                @if(isset($factura->facturable->email) && $factura->facturable->email)
                    <span class="mx-2"></span> Correo: {{ $factura->facturable->email }}
                @endif
            </p>
 </div>
 </div>

 {{-- Tabla de ítems --}}
 <div class="mb-8">
 <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3">Detalle del Documento</h3>
 <div class="overflow-x-auto pb-2">
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
 <td class="font-bold text-slate-800 dark:text-white">{{ $item->stock->producto ?? $item->descripcion ?? 'Producto/Servicio' }}</td>
 <td class="text-center font-bold">{{ $item->cantidad }}</td>
 <td class="text-right font-bold text-slate-800 dark:text-white">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
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
  <div class="p-5 bg-white/10 dark:bg-slate-900/25 border border-white/40 dark:border-white/5 backdrop-blur-md rounded-2xl shadow-sm">
 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Observaciones</p>
 <p class="text-sm font-medium text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $factura->observaciones }}</p>
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

  @if($factura->tipo_movimiento === 'venta' && $factura->estado !== 'anulada')
  <div class="flex justify-between items-center py-2 border-t border-gray-200/50 dark:border-white/10">
      <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Costo Total Compra</span>
      <span class="text-base font-black text-slate-700 dark:text-slate-300">${{ number_format($factura->costo_total, 0, ',', '.') }}</span>
  </div>
  <div class="flex justify-between items-center py-2 border-t border-gray-200/50 dark:border-white/10">
      <span class="text-sm font-bold {{ $factura->utilidad >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
          {{ $factura->utilidad >= 0 ? 'Utilidad Generada' : 'Pérdida Generada' }}
      </span>
      <span class="text-base font-black {{ $factura->utilidad >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
          {{ $factura->utilidad >= 0 ? '+' : '-' }}${{ number_format(abs($factura->utilidad), 0, ',', '.') }}
      </span>
  </div>
  @endif
 </div>
 </div>

 {{-- Historial de Pagos y Abonos --}}
 @if(isset($abonos) && $abonos->count() > 0)
 <div class="mb-8 pt-4 border-t border-gray-200/50 dark:border-white/10">
     <h3 class="font-bold text-lg text-slate-800 dark:text-white mb-3 flex items-center gap-2">
         💳 Historial de Pagos y Abonos en Caja ({{ $abonos->count() }})
     </h3>
     <div class="overflow-x-auto">
         <table class="ts-table mb-0 w-full text-xs">
             <thead>
                 <tr>
                     <th class="text-center whitespace-nowrap px-2" style="width: 50px;">Código</th>
                     <th class="text-center whitespace-nowrap px-2" style="width: 85px;">Fecha</th>
                     <th class="text-left px-2">Descripción</th>
                     <th class="text-center whitespace-nowrap px-2" style="width: 100px;">Método Pago</th>
                     <th class="text-center whitespace-nowrap px-2" style="width: 110px;">Registrado Por</th>
                     <th class="text-right whitespace-nowrap px-2" style="width: 85px;">Monto</th>
                     <th class="text-center whitespace-nowrap px-2" style="width: 45px;">Acciones</th>
                 </tr>
             </thead>
             <tbody>
                 @foreach($abonos as $abono)
                 <tr>
                     <td class="font-bold text-center text-slate-600 dark:text-slate-300 px-2">#{{ $abono->id }}</td>
                     <td class="text-sm font-medium text-center whitespace-nowrap px-2">{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }}</td>
                     <td class="text-xs font-semibold text-slate-700 dark:text-slate-300 px-2">{{ $abono->descripcion ?? '—' }}</td>
                     <td class="text-center whitespace-nowrap px-2">
                         <span class="pill {{ $abono->tipo_pago === 'efectivo' ? 'pill-efectivo' : 'pill-banco' }} text-xs">
                             {{ $abono->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Banco' }}
                         </span>
                     </td>
                     <td class="text-sm font-bold text-center text-slate-700 dark:text-slate-300 whitespace-nowrap px-2">
                         {{ $abono->user->name ?? 'Sistema' }}
                     </td>
                     <td class="text-right font-black text-emerald-600 dark:text-emerald-400 whitespace-nowrap px-2">
                         ${{ number_format($abono->monto, 0, ',', '.') }}
                     </td>
                     <td class="text-center whitespace-nowrap px-2">
                         <a href="{{ route('caja.show', $abono->id) }}" class="btn-ghost px-1.5 py-1 text-xs font-bold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30" title="Ver detalle de este pago #{{ $abono->id }}">
                             👁️
                         </a>
                     </td>
                 </tr>
                 @endforeach
             </tbody>
         </table>
     </div>
 </div>
 @endif

 <div class="pt-4 border-t border-gray-200/50 dark:border-white/5 flex justify-between items-center text-xs font-semibold text-gray-400">
 <span>Usuario: {{ $factura->user->name ?? '—' }}</span>
 <span>Registro: {{ $factura->created_at->format('d/m/Y H:i:s') }}</span>
 </div>
 </div>
</div>

@if($factura->saldo_pendiente > 0 && $movimientoPadre)
{{-- Modal interactivo para registrar abono directamente en la factura --}}
<div id="abono-factura-modal" class="ts-modal-overlay opacity-0 hidden transition-opacity duration-300 z-[200]">
    <div id="abono-factura-card" class="ts-modal-card scale-95 opacity-0 p-6 flex flex-col transition-all duration-300 w-full mx-4 max-w-md">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200/50 dark:border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    💵
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white leading-tight">Registrar Abono</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Factura #{{ $factura->numero_factura }}</p>
                </div>
            </div>
            <button type="button" onclick="closeAbonoFacturaModal()" class="btn-ghost p-2 text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="mb-4 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 flex justify-between items-center text-sm">
            <span class="text-amber-800 dark:text-amber-300 font-semibold">Saldo Pendiente:</span>
            <span class="text-lg font-black text-amber-600 dark:text-amber-400">${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</span>
        </div>

        <form action="{{ route('caja.abonos.store', $movimientoPadre->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="field-label">Monto del Abono ($) *</label>
                <input type="text" id="monto_abono_factura_visual" required placeholder="Ej: 50.000" class="glass-input font-bold text-right py-2.5">
                <input type="hidden" name="monto_abono" id="monto_abono_factura_real">
            </div>

            <div>
                <label class="field-label">Fecha del Pago *</label>
                <input type="date" name="fecha" required value="{{ date('Y-m-d') }}" class="glass-input">
            </div>

            <div>
                <label class="field-label">Tipo de Pago *</label>
                <select name="tipo_pago" required class="glass-input no-search">
                    <option value="efectivo">💵 Efectivo</option>
                    <option value="consignacion">🏦 Banco / Transferencia</option>
                </select>
            </div>

            <div>
                <label class="field-label">Descripción (Opcional)</label>
                <textarea name="descripcion" rows="2" placeholder="Detalle del abono..." class="glass-input text-xs"></textarea>
            </div>

            <div class="flex gap-2 pt-3">
                <button type="button" onclick="closeAbonoFacturaModal()" class="btn-cancel w-1/3 justify-center">Cancelar</button>
                <button type="submit" class="btn-primary w-2/3 justify-center shadow-lg shadow-emerald-500/20 bg-emerald-600 hover:bg-emerald-700">💾 Guardar Abono</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAbonoFacturaModal() {
    const modal = document.getElementById('abono-factura-modal');
    const card  = document.getElementById('abono-factura-card');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        card.classList.remove('scale-95', 'opacity-0');
        const input = document.getElementById('monto_abono_factura_visual');
        if (input) input.focus();
    }, 10);
}

function closeAbonoFacturaModal() {
    const modal = document.getElementById('abono-factura-modal');
    const card  = document.getElementById('abono-factura-card');
    if (!modal) return;
    modal.classList.add('opacity-0');
    card.classList.add('scale-95', 'opacity-0');
    document.body.style.overflow = 'auto';
    setTimeout(() => { modal.classList.add('hidden'); }, 300);
}

document.addEventListener('DOMContentLoaded', function() {
    const visualInput = document.getElementById('monto_abono_factura_visual');
    const realInput = document.getElementById('monto_abono_factura_real');
    if (visualInput && realInput) {
        visualInput.addEventListener('input', function() {
            let raw = this.value.replace(/\D/g, '');
            if (!raw) {
                this.value = '';
                realInput.value = '';
                return;
            }
            realInput.value = raw;
            this.value = parseInt(raw, 10).toLocaleString('es-CO');
        });
    }

    if (window.location.hash === '#registrar-abono') {
        openAbonoFacturaModal();
    }
});
</script>
@endif
@endsection
