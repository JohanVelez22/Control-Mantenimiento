@extends('layouts.print')

@section('title', 'Factura ' . $mantenimiento->id_orden)

@section('watermark_class', $mantenimiento->anulado ? 'anulado' : '')

@section('doc_title', 'ORDEN DE SERVICIO TÉCNICO - ' . $mantenimiento->id_orden)

@section('content')
<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 2px;">
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Cliente:</strong> <strong>{{ $mantenimiento->equipo->cliente->nombre ?? 'N/A' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Teléfono:</strong> <strong>{{ $mantenimiento->equipo->cliente->telefono ?? 'N/A' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Técnico:</strong> <strong>{{ $mantenimiento->tecnico->nombre ?? 'N/A' }}</strong></p>
    </div>
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Ingreso:</strong> <strong>{{ $mantenimiento->fecha_entrada ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('d/m/Y') : '—' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Emisión:</strong> <strong>{{ now()->format('d/m/Y h:i A') }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Estado:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $mantenimiento->estado }}</span></p>
    </div>
</div>

<div style="margin-bottom: 2px; font-size: 7.5pt;">
    <strong>Detalles del Equipo:</strong> 
    <strong>{{ $mantenimiento->equipo->nombre ?? 'N/A' }}</strong> | 
    Marca/Modelo: <strong>{{ trim(($mantenimiento->equipo->marca ?? '') . ' ' . ($mantenimiento->equipo->modelo ?? '')) ?: '—' }}</strong> | 
    Serie: <strong>{{ Str::upper($mantenimiento->equipo->serie ?? 'N/A') }}</strong>
</div>

<div style="margin-bottom: 2px; padding: 2px 5px; border: 1px solid #ccc; background: #fafafa; font-size: 6.8pt;">
    <strong style="font-size: 6.8pt;">SERVICIO:</strong> <strong>{{ Str::upper($mantenimiento->tipo) }} — {{ Str::upper($mantenimiento->reparacion) }}</strong><br>
    <strong style="font-size: 6.8pt;">OBSERVACIONES:</strong> <span style="font-size: 6.5pt; font-weight: normal;">{{ Str::upper($mantenimiento->descripcion ?: 'Sin observaciones adicionales.') }}</span>
</div>

@if($mantenimiento->stocks->count() > 0)
    <p style="font-size: 6.8pt; font-weight: bold; text-transform: uppercase; color: #333; margin: 2px 0 1px; text-align: left;">Repuestos / Insumos Utilizados:</p>
    <table class="items-table" style="font-size: 6.5pt; text-align: center; margin-bottom: 2px; width: 100%;">
        <thead>
            <tr style="background: #f8fafc;">
                <th class="text-center" style="width: 10%; font-size: 6.8pt; padding: 1px 2px;">CANT</th>
                <th class="text-center" style="font-size: 6.8pt; padding: 1px 2px;">DESCRIPCIÓN</th>
                <th class="text-center" style="width: 22%; font-size: 6.8pt; padding: 1px 2px;">V. UNITARIO</th>
                <th class="text-center" style="width: 22%; font-size: 6.8pt; padding: 1px 2px;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimiento->stocks as $stock)
            <tr style="font-weight: bold;">
                <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">{{ $stock->pivot->cantidad }}</td>
                <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">{{ $stock->producto }}</td>
                <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">${{ number_format($stock->pivot->precio_unitario, 0, ',', '.') }}</td>
                <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">${{ number_format($stock->pivot->cantidad * $stock->pivot->precio_unitario, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="clearfix" style="margin-bottom: 2px;">
    <table class="totals" style="width: 45%; float: right; margin-bottom: 0;">
        <tr>
            <td class="lbl" style="font-size: 6.8pt; padding: 0px 2px;">Costo Total Estimado:</td>
            <td class="val" style="font-size: 6.8pt; padding: 0px 2px; font-weight: bold;">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
        </tr>
        @if($mantenimiento->abonos->count() > 0)
            <tr>
                <td class="lbl" style="font-size: 6.8pt; padding: 0px 2px;">Total Abonado:</td>
                <td class="val" style="font-size: 6.8pt; padding: 0px 2px; font-weight: bold;">- ${{ number_format($mantenimiento->total_abonado, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="lbl" style="font-size: 6.8pt; padding: 1px 2px;">SALDO PENDIENTE:</td>
                <td class="val" style="font-size: 6.8pt; padding: 1px 2px; font-weight: bold;">
                    ${{ number_format($mantenimiento->saldo_pendiente, 0, ',', '.') }}
                </td>
            </tr>
        @else
            <tr class="grand-total">
                <td class="lbl" style="font-size: 6.8pt; padding: 1px 2px;">SALDO PENDIENTE:</td>
                <td class="val" style="font-size: 6.8pt; padding: 1px 2px; font-weight: bold;">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
            </tr>
        @endif
    </table>
</div>

@if($mantenimiento->abonos->count() > 0)
    @php
        $abonosHistorial = $mantenimiento->abonos->sortBy('fecha');
        $abonosMostrar = $abonosHistorial->take(3);
    @endphp
    <div style="margin-top: -10px; clear: both;">
        <p style="font-size: 6.8pt; font-weight: bold; text-transform: uppercase; color: #333; margin: 0 0 1px; text-align: left;">HISTORIAL DE ABONOS / PAGOS RECIBIDOS:</p>
        <table class="items-table" style="font-size: 6.5pt; margin-bottom: 0; width: 100%; text-align: center;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 1px 2px; text-align: center; width: 15%; font-size: 6.8pt;">FECHA</th>
                    <th style="padding: 1px 2px; text-align: center; width: 25%; font-size: 6.8pt;">MEDIO PAGO</th>
                    <th style="padding: 1px 2px; text-align: center; font-size: 6.8pt;">DESCRIPCIÓN</th>
                    <th style="padding: 1px 2px; text-align: center; width: 20%; font-size: 6.8pt;">ABONO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($abonosMostrar as $abono)
                <tr style="font-weight: bold;">
                    <td style="padding: 1px 2px; text-align: center; font-size: 6.5pt; font-weight: bold;">{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }}</td>
                    <td style="padding: 1px 2px; text-align: center; font-size: 6.5pt; font-weight: bold;">{{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Banco / Transf.' }}</td>
                    <td style="padding: 1px 2px; text-align: center; font-size: 6.5pt; font-weight: bold;">{{ $abono->descripcion ?: 'Abono a cuenta' }}</td>
                    <td style="padding: 1px 2px; text-align: center; font-size: 6.5pt; font-weight: bold;">${{ number_format($abono->monto, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="signatures-block clearfix" style="position: absolute; bottom: 38px; left: 0; width: 100%;">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 2px; font-size: 7pt;">
        <strong>Firma Cliente / Recibe</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 2px; font-size: 7pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
