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
    $desc = $movimiento->descripcion ?: '';
    $parent = $movimiento->parent ?: $movimiento;
    $isChild = (bool) $movimiento->parent_id;
    $rootId = $movimiento->parent_id ?: $movimiento->id;

    // 1. Identificar referencia (Factura, Mantenimiento u Electrónica)
    $facturaRef = null;
    $mantRef = null;
    $elecRef = null;
    $refSearch = null;

    if (preg_match('/#([A-Za-z0-9-]+)/', $desc, $m)) {
        $facturaRef = \App\Models\Factura::where('numero_factura', $m[1])->first();
        $refSearch = '#' . $m[1];
    }
    if (!$facturaRef && preg_match('/Orden\s+#?([A-Za-z0-9-]+)/i', $desc, $m)) {
        $mantRef = \App\Models\Mantenimiento::where('id_orden', $m[1])->first();
        $refSearch = 'Orden ' . $m[1];
    }
    if (!$facturaRef && !$mantRef && preg_match('/ELC\s+#?([A-Za-z0-9-]+)/i', $desc, $m)) {
        $elecRef = \App\Models\Electronica::where('id_orden', $m[1])->first();
        $refSearch = 'ELC ' . $m[1];
    }

    // 2. Determinar el Monto Total de la deuda/servicio
    $effectiveMontoTotal = $movimiento->monto_total ?: ($parent->monto_total ?: null);
    if (!$effectiveMontoTotal || $effectiveMontoTotal == 0) {
        if ($facturaRef) {
            $effectiveMontoTotal = $facturaRef->total_documento;
        } elseif ($mantRef) {
            $effectiveMontoTotal = $mantRef->costo;
        } elseif ($elecRef) {
            $effectiveMontoTotal = $elecRef->costo;
        }
    }

    // 3. Obtener todos los pagos/abonos relacionados para el historial
    $todosPagos = \App\Models\MovimientoCaja::where('estado', 'activo')
        ->where('anulado', false)
        ->where(function($q) use ($rootId, $refSearch) {
            $q->where('id', $rootId)
              ->orWhere('parent_id', $rootId);
            if ($refSearch) {
                $q->orWhere('descripcion', 'like', "%{$refSearch}%");
            }
        })
        ->orderBy('created_at', 'asc')
        ->get();

    $totalAcumulado = $todosPagos->sum('monto');
    $saldoPendiente = $effectiveMontoTotal > 0 ? max(0, (float)$effectiveMontoTotal - $totalAcumulado) : 0;
    $hasHistory = $todosPagos->count() > 1;
@endphp

<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 2px;">
    <div class="info-col">
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Persona / Contacto:</strong> <strong>{{ $movimiento->persona ?: ($parent->persona ?: '—') }}</strong></p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Empresa:</strong> <strong>{{ $movimiento->empresa ?: ($parent->empresa ?: '—') }}</strong></p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Atendido por:</strong> <strong>{{ $movimiento->user->name ?? 'Sistema' }}</strong></p>
        @if($isChild)
            <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Movimiento Principal:</strong> <strong>#{{ $parent->id }}</strong></p>
        @endif
    </div>
    <div class="info-col">
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Fecha Transacción:</strong> <strong>{{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</strong></p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Método de Pago:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $movimiento->tipo_pago }}</span></p>
        <p style="margin: 1px 0; font-size: 7.5pt;"><strong>Estado:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $movimiento->anulado ? 'ANULADO' : $movimiento->estado }}</span></p>
    </div>
</div>

<table style="width: 100%; margin-bottom: 2px; border: 1px solid #d1d5db; background-color: #f8fafc; border-collapse: collapse;">
    <tr>
        <td style="text-align: center; vertical-align: middle; padding: 2px 5px; line-height: 1.0;">
            <span style="font-size: 7.5pt; text-transform: uppercase; font-weight: bold; color: #0f172a; letter-spacing: 0.3px;">
                {{ $isChild ? 'Concepto del Movimiento Original:' : 'Concepto del Movimiento:' }}
            </span>
            <span style="font-size: 8pt; font-weight: bold; color: #0f172a; margin-left: 6px;">
                {{ $movimiento->concepto->nombre ?? ($parent->concepto->nombre ?? 'Concepto Desconocido') }}
            </span>
        </td>
    </tr>
</table>

<div class="clearfix" style="margin-top: 2px; margin-bottom: 2px;">
    <div style="float: left; width: 48%; border: 1px solid #ccc; padding: 2px 5px; font-size: 6.8pt; height: auto; min-height: 35px; box-sizing: border-box; overflow: hidden; background: #fafafa;">
        <strong style="font-size: 6.8pt;">OBSERVACIONES / DESCRIPCIÓN:</strong><br>
        <span style="color: #333; font-size: 6.5pt; font-weight: normal;">{!! nl2br(e($movimiento->descripcion ?: 'Sin observaciones.')) !!}</span>
    </div>
    
    <table class="totals" style="width: 48%; margin-bottom: 0;">
        @if($effectiveMontoTotal > 0)
            <tr>
                <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">TOTAL DEUDA:</td>
                <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($effectiveMontoTotal, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">PAGADO EN ESTE COMPROBANTE:</td>
            <td class="val" style="font-size: 7.5pt; padding: 1px 3px; font-weight: bold;">${{ number_format($movimiento->monto, 0, ',', '.') }}</td>
        </tr>
        @if($hasHistory)
            <tr>
                <td class="lbl" style="font-size: 7.5pt; padding: 1px 3px;">TOTAL ACUMULADO PAGADO:</td>
                <td class="val" style="font-size: 7.5pt; padding: 1px 3px;">${{ number_format($totalAcumulado, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($effectiveMontoTotal > 0)
            <tr class="grand-total">
                <td class="lbl" style="font-size: 8pt; padding: 1px 3px;">SALDO PENDIENTE:</td>
                <td class="val" style="font-size: 8pt; padding: 1px 3px;">
                    ${{ number_format($saldoPendiente, 0, ',', '.') }}
                </td>
            </tr>
        @endif
    </table>
</div>

{{-- Historial de Pagos / Abonos Registrados --}}
@if($hasHistory)
    <div style="margin-top: 4px; clear: both;">
        <p style="font-size: 7pt; font-weight: bold; text-transform: uppercase; color: #333; margin: 0 0 2px; text-align: left;">Historial de Pagos / Abonos Registrados:</p>
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
                @foreach($todosPagos as $pagoItem)
                    <tr style="{{ $pagoItem->id === $movimiento->id ? 'background-color: #eff6ff; font-weight: bold;' : '' }}">
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ \Carbon\Carbon::parse($pagoItem->fecha)->format('d/m/Y') }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ Str::upper($pagoItem->tipo_pago) }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ $pagoItem->descripcion ?: 'Abono / Pago' }} {{ $pagoItem->id === $movimiento->id ? '(*Este recibo)' : '' }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">{{ $pagoItem->user->name ?? 'Sistema' }}</td>
                        <td style="padding: 1px 3px; font-size: 6.5pt;">${{ number_format($pagoItem->monto, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="signatures-block clearfix" style="position: absolute; bottom: 38px; left: 0; width: 100%;">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt; color: #222;">
        <strong>Firma Cliente / Recibe</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt; color: #222;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
