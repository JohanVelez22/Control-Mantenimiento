@extends('layouts.app')
@section('content')
<div class="max-w-5xl mx-auto">
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
  <div class="flex flex-col md:flex-row items-center gap-4">
      <div class="text-center md:text-right">
          <p class="text-[10px] font-bold text-yellow-600/70 uppercase tracking-widest">Saldo Actual</p>
          <p class="text-2xl font-black text-yellow-700 dark:text-yellow-400">${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</p>
      </div>
      @php
          $movCaja = \App\Models\MovimientoCaja::where('estado', 'activo')
              ->where('anulado', false)
              ->where('descripcion', 'like', "%#{$factura->numero_factura}%")
              ->whereNull('parent_id')
              ->first();

          $isCompra = $factura->tipo_movimiento === 'compra';
          $nombreEntidad = $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? '';
          $isEmpresa = $factura->facturable_type === \App\Models\Proveedor::class;

          $createParams = [
              'tipo_movimiento' => $isCompra ? 'egreso' : 'ingreso',
              'monto'           => round((float) $factura->saldo_pendiente),
              'monto_total'     => round((float) $factura->total_documento),
              'descripcion'     => ($isCompra ? "Pago compra #" : "Pago venta #") . $factura->numero_factura,
          ];
          if ($isEmpresa) {
              $createParams['empresa'] = $nombreEntidad;
          } else {
              $createParams['persona'] = $nombreEntidad;
          }
      @endphp

      @if(!auth()->user()->isInvitado())
          @if($movCaja)
          <a href="{{ route('caja.edit', $movCaja->id) }}" class="btn-primary py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md flex items-center gap-2 shrink-0">
              <span>💵</span> Registrar Abono en Caja
          </a>
          @else
          <a href="{{ route('caja.create', $createParams) }}" class="btn-primary py-2 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md flex items-center gap-2 shrink-0">
              <span>💵</span> Registrar Pago en Caja
          </a>
          @endif
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
 <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8 border-b border-gray-200/50 dark:border-white/10 pb-6">
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
 <a href="{{ route('inventario.facturas.print', $factura->id) }}" target="_blank" class="btn-ghost border-blue-500/20 text-blue-600">
 🖨️ Imprimir
 </a>
 
	@if($factura->estado !== 'anulada' && !auth()->user()->isInvitado())
	<button type="button" onclick="openAnularModal('{{ route('inventario.facturas.anular', $factura->id) }}', false)" class="btn-danger">
		🚫 Anular
	</button>
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
 @if(isset($factura->facturable->email)) <span class="mx-2">•</span> Correo: {{ $factura->facturable->email }} @endif
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
@endsection
