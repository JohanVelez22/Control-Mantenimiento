@extends('layouts.print_pos')

@section('title', 'Ticket Comprobante ' . $movimiento->id)

@if($movimiento->anulado)
    @section('watermark', 'COMPROBANTE ANULADO')
@endif

@section('doc_title')
    @if($movimiento->parent_id)
        ABONO A CAJA #{{ $movimiento->id }}
    @else
        COMPROBANTE DE {{ Str::upper($movimiento->tipo_movimiento) }} #{{ $movimiento->id }}
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

<div class="info-pos">
    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y H:i') }}</p>
    <p><strong>Contacto:</strong> {{ $movimiento->persona ?: ($parent->persona ?: '—') }}</p>
    @if(!empty($movimiento->empresa) || !empty($parent->empresa))
        <p><strong>Empresa:</strong> {{ $movimiento->empresa ?: $parent->empresa }}</p>
    @endif
    <p><strong>Método de Pago:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $movimiento->tipo_pago }}</span></p>
    <p><strong>Atendido por:</strong> {{ $movimiento->user->name ?? 'Sistema' }}</p>
    @if($isChild)
        <p><strong>Movimiento Principal:</strong> #{{ $parent->id }}</p>
    @endif
</div>

<div class="divider"></div>

<div style="text-align: center; font-size: 7.5pt; margin: 3px 0;">
    <strong>CONCEPTO:</strong><br>
    <span style="font-size: 8pt; font-weight: bold;">{{ $movimiento->concepto->nombre ?? ($parent->concepto->nombre ?? 'Movimiento de Caja') }}</span>
</div>

@if($movimiento->descripcion)
    <div style="font-size: 7pt; background: #f9f9f9; padding: 3px; border: 1px dashed #ccc; margin: 4px 0;">
        <strong>Detalle:</strong> {{ $movimiento->descripcion }}
    </div>
@endif

<div class="divider"></div>

{{-- Valores y Totales --}}
<table class="totals-pos">
    @if($effectiveMontoTotal > 0)
        <tr>
            <td class="text-left font-bold">TOTAL SERVICIO/DEUDA:</td>
            <td class="text-right font-bold">${{ number_format($effectiveMontoTotal, 0, ',', '.') }}</td>
        </tr>
    @endif
    <tr class="grand-total">
        <td class="text-left">VALOR PAGADO AHORA:</td>
        <td class="text-right font-bold">${{ number_format($movimiento->monto, 0, ',', '.') }}</td>
    </tr>
    @if($hasHistory)
        <tr>
            <td class="text-left">Total Acumulado Pagado:</td>
            <td class="text-right font-bold">${{ number_format($totalAcumulado, 0, ',', '.') }}</td>
        </tr>
    @endif
    @if($effectiveMontoTotal > 0)
        <tr>
            <td class="text-left font-bold" style="color: #c00;">SALDO PENDIENTE:</td>
            <td class="text-right font-bold" style="color: #c00;">${{ number_format($saldoPendiente, 0, ',', '.') }}</td>
        </tr>
    @endif
</table>

{{-- Historial de Abonos si existen múltiples pagos --}}
@if($hasHistory)
    <div class="divider"></div>
    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin: 3px 0;">HISTORIAL DE PAGOS</div>
    <table class="table-pos" style="font-size: 7pt;">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>MÉTODO</th>
                <th class="text-right">ABONO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($todosPagos as $pago)
            <tr>
                <td>{{ \Carbon\Carbon::parse($pago->fecha)->format('d/m/y') }}</td>
                <td>{{ $pago->tipo_pago }}</td>
                <td class="text-right font-bold">${{ number_format($pago->monto, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div style="margin-top: 15px; text-align: center;">
    <div style="width: 70%; margin: 15px auto 2px auto; border-top: 1px dashed #000;"></div>
    <span style="font-size: 7pt;">Firma / Recibí Conforme</span>
</div>
@endsection
