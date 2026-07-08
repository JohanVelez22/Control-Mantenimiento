@extends('layouts.print')

@section('title', 'Factura ' . $mantenimiento->id_orden)

@section('watermark_class', $mantenimiento->anulado ? 'anulado' : '')

@section('doc_title', 'ORDEN DE SERVICIO TÉCNICO - ' . $mantenimiento->id_orden)

@section('content')
<div class="info-grid">
    <div class="info-col">
        <p><strong>Cliente:</strong> {{ $mantenimiento->equipo->cliente->nombre ?? 'N/A' }}</p>
        <p><strong>Teléfono:</strong> {{ $mantenimiento->equipo->cliente->telefono ?? 'N/A' }}</p>
        <p><strong>Técnico:</strong> {{ $mantenimiento->tecnico->nombre ?? 'N/A' }}</p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Ingreso:</strong> {{ $mantenimiento->fecha_entrada ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('d/m/Y') : '—' }}</p>
        <p><strong>Fecha Emisión:</strong> {{ now()->format('d/m/Y h:i A') }}</p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;">{{ $mantenimiento->estado }}</span></p>
    </div>
</div>

<div style="margin-bottom: 15px;">
    <strong>Detalles del Equipo:</strong><br>
    Equipo: {{ $mantenimiento->equipo->nombre ?? 'N/A' }} | 
    Marca/Modelo: {{ trim(($mantenimiento->equipo->marca ?? '') . ' ' . ($mantenimiento->equipo->modelo ?? '')) ?: '—' }} | 
    Serie: {{ Str::upper($mantenimiento->equipo->serie ?? 'N/A') }}
</div>

<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; background: #fafafa;">
    <strong>Servicio:</strong> {{ Str::upper($mantenimiento->tipo) }} — {{ Str::upper($mantenimiento->reparacion) }}<br>
    <strong>Observaciones:</strong> {{ Str::upper($mantenimiento->descripcion ?: 'Sin observaciones adicionales.') }}
</div>

@if($mantenimiento->stocks->count() > 0)
    <p class="font-bold mb-4">Repuestos / Insumos Utilizados:</p>
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 10%;">CANT</th>
                <th>DESCRIPCIÓN</th>
                <th class="text-right" style="width: 20%;">V. UNITARIO</th>
                <th class="text-right" style="width: 20%;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimiento->stocks as $stock)
            <tr>
                <td class="text-center">{{ $stock->pivot->cantidad }}</td>
                <td>{{ $stock->producto }}</td>
                <td class="text-right">${{ number_format($stock->pivot->precio_unitario, 0, ',', '.') }}</td>
                <td class="text-right">${{ number_format($stock->pivot->cantidad * $stock->pivot->precio_unitario, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="clearfix">
    <table class="totals">
        <tr>
            <td class="lbl">Costo Total Estimado:</td>
            <td class="val">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
        </tr>
        @if($mantenimiento->abonos->count() > 0)
            <tr>
                <td class="lbl">Total Abonado:</td>
                <td class="val" style="color: #c00;">- ${{ number_format($mantenimiento->total_abonado, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td class="lbl">SALDO PENDIENTE:</td>
                <td class="val" style="{{ $mantenimiento->saldo_pendiente == 0 ? 'color: green;' : 'color: red;' }}">
                    ${{ number_format($mantenimiento->saldo_pendiente, 0, ',', '.') }}
                </td>
            </tr>
        @else
            <tr class="grand-total">
                <td class="lbl">SALDO PENDIENTE:</td>
                <td class="val" style="color: red;">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
            </tr>
        @endif
    </table>
</div>

@if($mantenimiento->abonos->count() > 0)
    <div style="margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px;">
        <p style="font-size: 8pt; font-weight: bold; margin-bottom: 5px;">Historial de Pagos:</p>
        <p style="font-size: 8pt; color: #555;">
            @foreach($mantenimiento->abonos->sortBy('fecha') as $abono)
                • {{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/Y') }} - {{ ucfirst($abono->tipo_pago) }}: ${{ number_format($abono->monto, 0, ',', '.') }}<br>
            @endforeach
        </p>
    </div>
@endif
@endsection
