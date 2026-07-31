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
        <p><strong>Registrado por:</strong> {{ $factura->user->name ?? 'Sistema' }}</p>
    </div>
</div>

<table class="items-table" style="text-align: center;">
    <thead>
        <tr>
            <th class="text-center" style="width: 10%;">CANT</th>
            <th class="text-center">DESCRIPCIÓN / PRODUCTO</th>
            <th class="text-center" style="width: 22%;">V. UNITARIO</th>
            <th class="text-center" style="width: 22%;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($factura->items as $item)
        <tr>
            <td class="text-center">{{ $item->cantidad }}</td>
            <td class="text-center">{{ $item->stock->producto ?? $item->descripcion ?? 'Producto Desconocido' }}</td>
            <td class="text-center">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-center">${{ number_format($item->cantidad * $item->precio_unitario, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="clearfix" style="margin-bottom: 4px;">
    <div style="float: left; width: 45%; border: 1px solid #ccc; padding: 4px 6px; background: #fafafa; font-size: 7.5pt; min-height: 42px; height: auto; box-sizing: border-box;">
        <strong>Observaciones:</strong><br>
        <span style="color: #333;">{!! nl2br(e($factura->observaciones ?: 'Sin observaciones.')) !!}</span>
    </div>

    <table class="totals" style="width: 48%; float: right; margin-bottom: 0;">
        <tr>
            <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">Total Documento:</td>
            <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($factura->total_documento, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">Total Pagado:</td>
            <td class="val" style="font-size: 7.5pt; padding: 1px 3px; color: green;">${{ number_format($factura->total_pagado, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand-total">
            <td class="lbl" style="font-size: 8pt; padding: 1px 3px;">SALDO PENDIENTE:</td>
            <td class="val" style="font-size: 8pt; padding: 1px 3px; {{ $factura->saldo_pendiente <= 0 ? 'color: green;' : 'color: red;' }}">
                ${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}
            </td>
        </tr>
    </table>
</div>

@if(isset($abonos) && $abonos->count() > 0)
<div style="margin-top: 4px; clear: both;">
    <p style="font-size: 7pt; font-weight: bold; text-transform: uppercase; color: #333; margin: 0 0 2px; text-align: left;">HISTORIAL DE ABONOS / PAGOS RECIBIDOS:</p>
    <table class="items-table" style="font-size: 6.5pt; margin-bottom: 0; width: 100%; text-align: center;">
        <thead>
            <tr style="background: #f8fafc;">
                <th style="padding: 1px 3px; text-align: center; width: 15%;">FECHA</th>
                <th style="padding: 1px 3px; text-align: center; width: 25%;">MEDIO PAGO</th>
                <th style="padding: 1px 3px; text-align: center;">DESCRIPCIÓN</th>
                <th style="padding: 1px 3px; text-align: center; width: 20%;">ABONO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($abonos->take(3) as $abono)
            <tr>
                <td style="padding: 1px 3px; text-align: center;">{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }}</td>
                <td style="padding: 1px 3px; text-align: center;">{{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Banco / Transf.' }}</td>
                <td style="padding: 1px 3px; text-align: center;">{{ $abono->descripcion }}</td>
                <td style="padding: 1px 3px; text-align: center; font-weight: bold; color: green;">${{ number_format($abono->monto, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="signatures-block clearfix">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma {{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
