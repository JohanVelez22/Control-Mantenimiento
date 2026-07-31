@extends('layouts.print')

@section('title', 'Factura ' . $factura->numero_factura)

@section('watermark_class', $factura->estado === 'anulada' ? 'anulado' : '')

@section('doc_title')
    FACTURA DE {{ Str::upper($factura->tipo_movimiento) }} - {{ $factura->numero_factura }}
@endsection

@section('content')
<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 2px;">
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>{{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}:</strong> <strong>{{ $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? 'N/A' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Identificación:</strong> <strong>{{ $factura->facturable->nit_documento ?? $factura->facturable->documento ?? 'N/A' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Teléfono:</strong> <strong>{{ $factura->facturable->telefono ?? 'N/A' }}</strong></p>
    </div>
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Emisión:</strong> <strong>{{ \Carbon\Carbon::parse($factura->fecha)->format('d/m/Y') }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Estado:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ str_replace('_', ' ', $factura->estado) }}</span></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Registrado por:</strong> <strong>{{ $factura->user->name ?? 'Sistema' }}</strong></p>
    </div>
</div>

<table class="items-table" style="text-align: center; margin-bottom: 2px;">
    <thead>
        <tr style="background: #f8fafc;">
            <th class="text-center" style="width: 10%; font-size: 6.8pt; padding: 1px 2px;">CANT</th>
            <th class="text-center" style="font-size: 6.8pt; padding: 1px 2px;">DESCRIPCIÓN / PRODUCTO</th>
            <th class="text-center" style="width: 22%; font-size: 6.8pt; padding: 1px 2px;">V. UNITARIO</th>
            <th class="text-center" style="width: 22%; font-size: 6.8pt; padding: 1px 2px;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($factura->items as $item)
        <tr style="font-weight: bold;">
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">{{ $item->cantidad }}</td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">{{ $item->stock->producto ?? $item->descripcion ?? 'Producto Desconocido' }}</td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">${{ number_format($item->cantidad * $item->precio_unitario, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="clearfix" style="margin-bottom: 2px;">
    <div style="float: left; width: 48%; border: 1px solid #ccc; padding: 2px 5px; background: #fafafa; font-size: 6.8pt; box-sizing: border-box;">
        <strong style="font-size: 6.8pt;">OBSERVACIONES:</strong><br>
        <span style="color: #333; font-size: 6.5pt; font-weight: normal;">{!! nl2br(e($factura->observaciones ?: 'Sin observaciones.')) !!}</span>
    </div>

    <table class="totals" style="width: 48%; float: right; margin-bottom: 0;">
        <tr>
            <td class="lbl" style="font-size: 6.8pt; padding: 0px 2px;">Total Documento:</td>
            <td class="val" style="font-size: 6.8pt; padding: 0px 2px; font-weight: bold;">${{ number_format($factura->total_documento, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="lbl" style="font-size: 6.8pt; padding: 0px 2px;">Total Pagado:</td>
            <td class="val" style="font-size: 6.8pt; padding: 0px 2px; font-weight: bold;">${{ number_format($factura->total_pagado, 0, ',', '.') }}</td>
        </tr>
        <tr class="grand-total">
            <td class="lbl" style="font-size: 6.8pt; padding: 1px 2px;">SALDO PENDIENTE:</td>
            <td class="val" style="font-size: 6.8pt; padding: 1px 2px; font-weight: bold;">
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
                <th style="padding: 1px 3px; text-align: center; width: 15%; font-size: 6.5pt;">FECHA</th>
                <th style="padding: 1px 3px; text-align: center; width: 25%; font-size: 6.5pt;">MEDIO PAGO</th>
                <th style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">DESCRIPCIÓN</th>
                <th style="padding: 1px 3px; text-align: center; width: 20%; font-size: 6.5pt;">ABONO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($abonos->take(3) as $abono)
            <tr>
                <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }}</td>
                <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">{{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Banco / Transf.' }}</td>
                <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">{{ $abono->descripcion }}</td>
                <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt; font-weight: bold;">${{ number_format($abono->monto, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="signatures-block clearfix" style="position: absolute; bottom: 38px; left: 0; width: 100%;">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma {{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
