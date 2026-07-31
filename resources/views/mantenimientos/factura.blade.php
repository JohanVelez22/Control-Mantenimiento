@extends('layouts.print')

@section('title', 'Factura ' . $mantenimiento->id_orden)

@section('watermark_class', $mantenimiento->anulado ? 'anulado' : '')

@section('doc_title', 'ORDEN DE SERVICIO TÉCNICO - ' . $mantenimiento->id_orden)

@section('content')
<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 4px;">
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Cliente:</strong> {{ $mantenimiento->equipo->cliente->nombre ?? 'N/A' }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Teléfono:</strong> {{ $mantenimiento->equipo->cliente->telefono ?? 'N/A' }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Técnico:</strong> {{ $mantenimiento->tecnico->nombre ?? 'N/A' }}</p>
    </div>
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Ingreso:</strong> {{ $mantenimiento->fecha_entrada ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('d/m/Y') : '—' }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Emisión:</strong> {{ now()->format('d/m/Y h:i A') }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Estado:</strong> <span style="text-transform: uppercase;">{{ $mantenimiento->estado }}</span></p>
    </div>
</div>

<div style="margin-bottom: 4px; font-size: 7.5pt;">
    <strong>Detalles del Equipo:</strong> 
    {{ $mantenimiento->equipo->nombre ?? 'N/A' }} | 
    Marca/Modelo: {{ trim(($mantenimiento->equipo->marca ?? '') . ' ' . ($mantenimiento->equipo->modelo ?? '')) ?: '—' }} | 
    Serie: {{ Str::upper($mantenimiento->equipo->serie ?? 'N/A') }}
</div>

<div style="margin-bottom: 4px; padding: 4px 6px; border: 1px solid #ccc; background: #fafafa; font-size: 7.5pt;">
    <strong>Servicio:</strong> {{ Str::upper($mantenimiento->tipo) }} — {{ Str::upper($mantenimiento->reparacion) }}<br>
    <strong>Observaciones:</strong> {{ Str::upper($mantenimiento->descripcion ?: 'Sin observaciones adicionales.') }}
</div>

@if($mantenimiento->stocks->count() > 0)
    <p class="font-bold" style="font-size: 7.5pt; margin: 2px 0 2px 0;">Repuestos / Insumos Utilizados:</p>
    <table class="items-table" style="text-align: center; margin-bottom: 4px;">
        <thead>
            <tr>
                <th class="text-center" style="width: 10%; font-size: 7.5pt; padding: 2px 4px;">CANT</th>
                <th class="text-center" style="font-size: 7.5pt; padding: 2px 4px;">DESCRIPCIÓN</th>
                <th class="text-center" style="width: 22%; font-size: 7.5pt; padding: 2px 4px;">V. UNITARIO</th>
                <th class="text-center" style="width: 22%; font-size: 7.5pt; padding: 2px 4px;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimiento->stocks as $stock)
            <tr>
                <td class="text-center" style="font-size: 7.5pt; padding: 2px 4px;">{{ $stock->pivot->cantidad }}</td>
                <td class="text-center" style="font-size: 7.5pt; padding: 2px 4px;">{{ $stock->producto }}</td>
                <td class="text-center" style="font-size: 7.5pt; padding: 2px 4px;">${{ number_format($stock->pivot->precio_unitario, 0, ',', '.') }}</td>
                <td class="text-center" style="font-size: 7.5pt; padding: 2px 4px;">${{ number_format($stock->pivot->cantidad * $stock->pivot->precio_unitario, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="clearfix" style="margin-bottom: 4px;">
    <table class="totals" style="width: 48%; float: right; margin-bottom: 0;">
        <tr>
            <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">Costo Total Estimado:</td>
            <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
        </tr>
        @if($mantenimiento->abonos->count() > 0)
            <tr>
                <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">Total Abonado:</td>
                <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">- ${{ number_format($mantenimiento->total_abonado, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="lbl" style="font-size: 8pt; padding: 1px 3px;">SALDO PENDIENTE:</td>
                <td class="val" style="font-size: 8pt; padding: 1px 3px;">
                    ${{ number_format($mantenimiento->saldo_pendiente, 0, ',', '.') }}
                </td>
            </tr>
        @else
            <tr class="grand-total">
                <td class="lbl" style="font-size: 8pt; padding: 1px 3px;">SALDO PENDIENTE:</td>
                <td class="val" style="font-size: 8pt; padding: 1px 3px;">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
            </tr>
        @endif
    </table>
</div>

@if($mantenimiento->abonos->count() > 0)
    @php
        $abonosHistorial = $mantenimiento->abonos->sortBy('fecha');
        $abonosMostrar = $abonosHistorial->take(3);
    @endphp
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
                @foreach($abonosMostrar as $abono)
                <tr>
                    <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }}</td>
                    <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">{{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Banco / Transf.' }}</td>
                    <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">{{ $abono->descripcion ?: 'Abono a cuenta' }}</td>
                    <td style="padding: 1px 3px; text-align: center; font-size: 6.5pt; font-weight: bold;">${{ number_format($abono->monto, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="signatures-block clearfix">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma Cliente / Recibe</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
