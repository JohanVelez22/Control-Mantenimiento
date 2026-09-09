@extends('layouts.print_pos')

@section('title', 'Ticket Factura ' . $factura->numero_factura)

@if($factura->estado === 'anulada')
    @section('watermark', 'FACTURA ANULADA')
@endif

@section('doc_title')
    FACTURA DE {{ Str::upper($factura->tipo_movimiento) }} #{{ $factura->numero_factura }}
@endsection

@section('content')
<div class="info-pos">
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y H:i') }}</p>
    <p><strong>{{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}:</strong> {{ $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? 'N/A' }}</p>
    <p><strong>Doc/NIT:</strong> {{ $factura->facturable->nit_documento ?? $factura->facturable->documento ?? 'N/A' }}</p>
    @if(!empty($factura->facturable->telefono))
        <p><strong>Teléfono:</strong> {{ $factura->facturable->telefono }}</p>
    @endif
    <p><strong>Atendido por:</strong> {{ $factura->user->name ?? 'Sistema' }}</p>
    <p><strong>Estado:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ str_replace('_', ' ', $factura->estado) }}</span></p>
</div>

<div class="divider"></div>

{{-- Tabla de Productos / Ítems --}}
<table class="table-pos">
    <thead>
        <tr>
            <th style="width: 15%;">CANT</th>
            <th>DESCRIPCIÓN</th>
            <th class="text-right" style="width: 28%;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($factura->items as $item)
        @php
            $subtotalItem = $item->cantidad * $item->precio_unitario;
            $nombreItem = $item->stock->producto ?? $item->descripcion ?? 'Producto';
        @endphp
        <tr>
            <td class="font-bold">{{ $item->cantidad }}</td>
            <td>
                {{ $nombreItem }}
                @if($item->cantidad > 1)
                    <br><span style="font-size: 6.5pt; color: #444;">${{ number_format($item->precio_unitario, 0, ',', '.') }} c/u</span>
                @endif
            </td>
            <td class="text-right font-bold">${{ number_format($subtotalItem, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="divider"></div>

{{-- Resumen de Totales --}}
<table class="totals-pos">
    <tr>
        <td class="text-left font-bold">Total Documento:</td>
        <td class="text-right font-bold">${{ number_format($factura->total_documento, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="text-left">Total Pagado / Abonos:</td>
        <td class="text-right font-bold">${{ number_format($factura->total_pagado, 0, ',', '.') }}</td>
    </tr>
    <tr class="grand-total">
        <td class="text-left">SALDO PENDIENTE:</td>
        <td class="text-right font-bold">${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</td>
    </tr>
</table>

@if($factura->observaciones)
    <div style="margin-top: 5px; font-size: 7pt; background: #f9f9f9; padding: 3px; border: 1px dashed #ccc;">
        <strong>Obs:</strong> {{ $factura->observaciones }}
    </div>
@endif

{{-- Historial de Abonos si existen --}}
@if(isset($abonos) && $abonos->count() > 0)
    <div class="divider"></div>
    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin: 3px 0;">HISTORIAL DE ABONOS</div>
    <table class="table-pos" style="font-size: 7pt;">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>MEDIO</th>
                <th class="text-right">VALOR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($abonos as $abono)
            <tr>
                <td>{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/y') }}</td>
                <td>{{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Transf.' }}</td>
                <td class="text-right font-bold">${{ number_format($abono->monto, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div style="margin-top: 15px; text-align: center;">
    <div style="width: 70%; margin: 15px auto 2px auto; border-top: 1px dashed #000;"></div>
    <span style="font-size: 7pt;">Firma {{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}</span>
</div>
@endsection
