@extends('layouts.print')

@section('title', 'Comprobante de Caja ' . $movimiento->id)

@section('watermark_class', $movimiento->anulado ? 'anulado' : '')

@section('doc_title')
    @if($movimiento->parent_id)
        COMPROBANTE DE ABONO A CAJA - #{{ $movimiento->id }}
    @else
        COMPROBANTE DE {{ Str::upper($movimiento->tipo_movimiento) }} A CAJA - #{{ $movimiento->id }}
    @endif
@endsection

@section('content')
@php
    $parent = $movimiento->parent ?: $movimiento;
    $isChild = (bool) $movimiento->parent_id;
@endphp

<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 4px;">
    <div class="info-col">
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Persona / Contacto:</strong> {{ $movimiento->persona ?: ($parent->persona ?: '—') }}</p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Empresa:</strong> {{ $movimiento->empresa ?: ($parent->empresa ?: '—') }}</p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Atendido por:</strong> {{ $movimiento->user->name ?? 'Sistema' }}</p>
        @if($isChild)
            <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Movimiento Padre:</strong> #{{ $parent->id }}</p>
        @endif
    </div>
    <div class="info-col">
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Fecha Transacción:</strong> {{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Método de Pago:</strong> <span style="text-transform: uppercase;">{{ $movimiento->tipo_pago }}</span></p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Estado:</strong> <span style="text-transform: uppercase;">{{ $movimiento->anulado ? 'ANULADO' : $movimiento->estado }}</span></p>
    </div>
</div>

<table style="width: 100%; margin-bottom: 4px; border: 1px solid #d1d5db; background-color: #f8fafc; border-collapse: collapse;">
    <tr>
        <td style="text-align: center; vertical-align: middle; padding: 3px 6px; line-height: 1.0;">
            <span style="font-size: 7.5pt; text-transform: uppercase; font-weight: bold; color: #0f172a; letter-spacing: 0.3px;">
                {{ $isChild ? 'Concepto del Movimiento Original:' : 'Concepto del Movimiento:' }}
            </span>
            <span style="font-size: 8pt; font-weight: bold; color: #0f172a; margin-left: 6px;">
                {{ $parent->concepto->nombre ?? 'Concepto Desconocido' }}
            </span>
        </td>
    </tr>
</table>

<div class="clearfix" style="margin-top: 4px; margin-bottom: 4px;">
    <div style="float: left; width: 48%; border: 1px solid #ccc; padding: 3px 6px; font-size: 7pt; height: auto; min-height: 40px; box-sizing: border-box; overflow: hidden;">
        <strong>Observaciones / Descripción:</strong><br>
        <span style="color: #333; font-size: 7pt;">{!! nl2br(e($movimiento->descripcion ?: ($isChild ? 'Abono parcial registrado al movimiento #' . $parent->id : 'Sin observaciones.'))) !!}</span>
    </div>
    
    <table class="totals" style="width: 48%; margin-bottom: 0;">
        @if($isChild)
            {{-- Impresión de un Abono Hijo --}}
            @if($parent->monto_total > 0)
                <tr>
                    <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">TOTAL DEUDA:</td>
                    <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($parent->monto_total, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr>
                <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">PAGADO HOY (ABONO):</td>
                <td class="val" style="font-size: 7.5pt; padding: 1px 3px; font-weight: bold;">${{ number_format($movimiento->monto, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">TOTAL ACUMULADO:</td>
                <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($parent->total_pagado, 0, ',', '.') }}</td>
            </tr>
            @if($parent->monto_total > 0)
                <tr class="grand-total">
                    <td class="lbl" style="font-size: 8pt; padding: 1px 3px;">SALDO PENDIENTE:</td>
                    <td class="val" style="font-size: 8pt; padding: 1px 3px;">
                        ${{ number_format($parent->saldo_pendiente, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        @else
            {{-- Impresión del Movimiento Principal (Padre) - Histórico Puro --}}
            @if($movimiento->monto_total && $movimiento->monto_total > 0)
                @php
                    $saldoInicial = max(0, $movimiento->monto_total - $movimiento->monto);
                @endphp
                <tr>
                    <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">TOTAL DEUDA:</td>
                    <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($movimiento->monto_total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">PAGO INICIAL:</td>
                    <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($movimiento->monto, 0, ',', '.') }}</td>
                </tr>
                <tr class="grand-total">
                    <td class="lbl" style="font-size: 8pt; padding: 1px 3px;">SALDO PENDIENTE:</td>
                    <td class="val" style="font-size: 8pt; padding: 1px 3px;">
                        ${{ number_format($saldoInicial, 0, ',', '.') }}
                    </td>
                </tr>
            @else
                <tr class="grand-total">
                    <td class="lbl" style="font-size: 8pt; padding: 2px 3px;">MONTO TOTAL:</td>
                    <td class="val" style="font-size: 9.5pt; padding: 2px 3px;">
                        ${{ number_format($movimiento->monto, 0, ',', '.') }}
                    </td>
                </tr>
            @endif
        @endif
    </table>
</div>

{{-- Trazabilidad / Historial de Pagos SOLO para el comprobante Hijo (Abono) --}}
@if($isChild)
    @php
        $abonosPrevios = $parent->childPayments->where('anulado', false)->where('id', '!=', $movimiento->id);
        $countPrevios = $abonosPrevios->count();
        $abonosMostrar = $abonosPrevios->sortBy('fecha')->take(3);
    @endphp

    <div style="margin-top: 4px; clear: both;">
        <p style="font-size: 7pt; font-weight: bold; text-transform: uppercase; color: #333; margin: 0 0 2px; text-align: left;">Historial de Pagos Anteriores (Movimiento Padre #{{ $parent->id }}):</p>
        <table class="items-table" style="font-size: 6.5pt; margin-bottom: 2px; width: 100%; text-align: center;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">FECHA</th>
                    <th style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">MÉT. PAGO</th>
                    <th style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">DESCRIPCIÓN</th>
                    <th style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">USUARIO</th>
                    <th style="padding: 1px 3px; text-align: center; font-size: 6.5pt;">MONTO</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 1px 3px; font-size: 6.5pt;">{{ \Carbon\Carbon::parse($parent->fecha)->format('d/m/Y') }}</td>
                    <td style="padding: 1px 3px; font-size: 6.5pt;">{{ Str::upper($parent->tipo_pago) }}</td>
                    <td style="padding: 1px 3px; font-size: 6.5pt;">{{ $parent->descripcion ?: 'Pago inicial' }}</td>
                    <td style="padding: 1px 3px; font-size: 6.5pt;">{{ $parent->user->name ?? 'Sistema' }}</td>
                    <td style="padding: 1px 3px; font-size: 6.5pt;">${{ number_format($parent->monto, 0, ',', '.') }}</td>
                </tr>
                @foreach($abonosMostrar as $abono)
                    <tr>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ Str::upper($abono->tipo_pago) }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ $abono->descripcion ?: 'Abono parcial' }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ $abono->user->name ?? 'Sistema' }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">${{ number_format($abono->monto, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($countPrevios > 3)
            <p style="font-size: 6pt; color: #666; font-style: italic; margin: 1px 0 0 0;">* Se muestran los 3 abonos más recientes en este resumen de impresión. El TOTAL ACUMULADO arriba incluye los {{ $countPrevios + 1 }} pagos del movimiento.</p>
        @endif
    </div>
@endif

<div class="signatures-block clearfix">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt; color: #222;">
        <strong>Firma Cliente / Recibe</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt; color: #222;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
