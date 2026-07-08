@extends('layouts.print')

@section('title', 'Factura ' . $factura->numero_factura)

@section('watermark_class', $factura->estado === 'anulada' ? 'anulado' : '')

@section('doc_title')
    FACTURA DE {{ Str::upper($factura->tipo_movimiento) }} - {{ $factura->numero_factura }}
@endsection

@section('content')
<div class="info-grid">
    <div class="info-col">
        <p><strong>{{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}:</strong> {{ $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? 'N/A' }}</p>
        <p><strong>Identificación:</strong> {{ $factura->facturable->nit_documento ?? $factura->facturable->documento ?? 'N/A' }}</p>
        <p><strong>Teléfono:</strong> {{ $factura->facturable->telefono ?? 'N/A' }}</p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}</p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;">{{ str_replace('_', ' ', $factura->estado) }}</span></p>
        <p><strong>Vendedor:</strong> {{ $factura->user->name ?? 'Sistema' }}</p>
    </div>
</div>

<table class="items-table">
    <thead>
        <tr>
            <th class="text-center" style="width: 10%;">CANT</th>
            <th>DESCRIPCIÓN / PRODUCTO</th>
            <th class="text-right" style="width: 20%;">V. UNITARIO</th>
            <th class="text-right" style="width: 20%;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($factura->items as $item)
        <tr>
            <td class="text-center">{{ $item->cantidad }}</td>
            <td>{{ $item->stock->producto ?? 'Producto Desconocido' }}</td>
            <td class="text-right">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-right">${{ number_format($item->cantidad * $item->precio_unitario, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="clearfix">
    <div style="float: left; width: 45%; border: 1px solid #ccc; padding: 10px; background: #fafafa; font-size: 8pt; margin-top: 10px;">
        <strong>Observaciones:</strong><br>
        {!! nl2br(e($factura->observaciones ?: 'Sin observaciones.')) !!}
    </div>
    
    <table class="totals">
        <tr>
            <td class="lbl">Total Documento:</td>
            <td class="val">${{ number_format($factura->total_documento, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="lbl">Total Pagado:</td>
            <td class="val" style="color: green;">${{ number_format($factura->total_pagado, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand-total">
            <td class="lbl">SALDO PENDIENTE:</td>
            <td class="val" style="{{ $factura->saldo_pendiente <= 0 ? 'color: green;' : 'color: red;' }}">
                ${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}
            </td>
        </tr>
    </table>
</div>
@endsection
