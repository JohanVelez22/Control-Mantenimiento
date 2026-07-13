@extends('layouts.print')

@section('title', 'Comprobante de Caja ' . $movimiento->id)

@section('watermark_class', $movimiento->anulado ? 'anulado' : '')

@section('doc_title')
    COMPROBANTE DE {{ Str::upper($movimiento->tipo_movimiento) }} A CAJA - #{{ $movimiento->id }}
@endsection

@section('content')
<div class="info-grid">
    <div class="info-col">
        <p><strong>Persona / Contacto:</strong> {{ $movimiento->persona ?: '—' }}</p>
        <p><strong>Empresa:</strong> {{ $movimiento->empresa ?: '—' }}</p>
        <p><strong>Atendido por:</strong> {{ $movimiento->user->name ?? 'Sistema' }}</p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Transacción:</strong> {{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</p>
        <p><strong>Método de Pago:</strong> <span style="text-transform: uppercase;">{{ $movimiento->tipo_pago }}</span></p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;">{{ $movimiento->estado }}</span></p>
    </div>
</div>

<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; text-align: center;">
    <h3 style="margin: 0 0 6px; font-size: 10pt; text-transform: uppercase;">Concepto del Movimiento</h3>
    <p style="font-size: 11pt; font-weight: bold; margin: 0; color: #222;">
        {{ $movimiento->concepto->nombre ?? 'Concepto Desconocido' }}
    </p>
</div>

<div class="clearfix" style="margin-top: 30px;">
    <div style="float: left; width: 45%; border: 1px solid #ccc; padding: 10px; font-size: 8pt; min-height: 80px;">
        <strong>Observaciones / Descripción:</strong><br>
        {!! nl2br(e($movimiento->descripcion ?: 'Sin observaciones.')) !!}
    </div>
    
    <table class="totals">
        @if($movimiento->monto_total && $movimiento->monto_total > $movimiento->monto)
            <tr>
                <td class="lbl">MONTO TOTAL DEUDA:</td>
                <td class="val">${{ number_format($movimiento->monto_total, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="lbl">PAGADO HOY:</td>
                <td class="val" style="color: green;">${{ number_format($movimiento->monto, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="lbl">SALDO PENDIENTE:</td>
                <td class="val" style="color: red;">${{ number_format($movimiento->saldo_pendiente, 0, ',', '.') }}</td>
            </tr>
        @else
            <tr class="grand-total">
                <td class="lbl">MONTO TOTAL:</td>
                <td class="val" style="font-size: 14pt; {{ $movimiento->tipo_movimiento === 'ingreso' ? 'color: green;' : 'color: red;' }}">
                    ${{ number_format($movimiento->monto, 0, ',', '.') }}
                </td>
            </tr>
        @endif
    </table>
</div>

<div class="clearfix" style="margin-top: 32px;">
    <div style="float: left; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 4px; font-size: 8pt;">
        <strong>Firma Cliente / Recibe</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 4px; font-size: 8pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
