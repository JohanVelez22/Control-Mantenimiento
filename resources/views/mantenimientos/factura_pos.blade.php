@extends('layouts.print_pos')

@section('title', 'Ticket Mantenimiento ' . $mantenimiento->id_orden)

@if($mantenimiento->anulado)
    @section('watermark', 'ORDEN ANULADA')
@endif

@section('doc_title')
    ORDEN DE MANTENIMIENTO #{{ $mantenimiento->id_orden }}
@endsection

@section('content')
<div class="info-pos">
    <p><strong>Fecha Ingreso:</strong> {{ $mantenimiento->fecha_entrada ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('d/m/Y') : '—' }}</p>
    <p><strong>{{ $mantenimiento->equipo?->propietario_tipo === 'proveedor' ? 'Proveedor:' : 'Cliente:' }}</strong> {{ $mantenimiento->equipo?->propietario_nombre ?? 'N/A' }}</p>
    @if(!empty($mantenimiento->equipo?->propietario_telefono))
        <p><strong>Tel:</strong> {{ $mantenimiento->equipo->propietario_telefono }}</p>
    @endif
    <p><strong>Técnico:</strong> {{ $mantenimiento->tecnico->nombre ?? 'N/A' }}</p>
    <p><strong>Estado:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $mantenimiento->estado }}</span></p>
</div>

<div class="divider"></div>

<div class="info-pos">
    <p><strong>Equipo:</strong> {{ $mantenimiento->equipo->nombre ?? 'N/A' }}</p>
    <p><strong>Marca/Mod:</strong> {{ trim(($mantenimiento->equipo->marca ?? '') . ' ' . ($mantenimiento->equipo->modelo ?? '')) ?: '—' }}</p>
    @if(!empty($mantenimiento->equipo->serie))
        <p><strong>Serie:</strong> {{ Str::upper($mantenimiento->equipo->serie) }}</p>
    @endif
    <p><strong>Servicio:</strong> {{ Str::upper($mantenimiento->tipo) }} — {{ Str::upper($mantenimiento->reparacion) }}</p>
</div>

@if($mantenimiento->descripcion)
    <div style="font-size: 7pt; background: #f9f9f9; padding: 3px; border: 1px dashed #ccc; margin: 3px 0;">
        <strong>Obs:</strong> {{ $mantenimiento->descripcion }}
    </div>
@endif

{{-- Repuestos utilizados --}}
@if($mantenimiento->stocks->count() > 0)
    <div class="divider"></div>
    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin: 2px 0;">REPUESTOS / INSUMOS</div>
    <table class="table-pos">
        <thead>
            <tr>
                <th style="width: 15%;">CANT</th>
                <th>DESCRIPCIÓN</th>
                <th class="text-right" style="width: 28%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimiento->stocks as $stock)
            <tr>
                <td class="font-bold">{{ $stock->pivot->cantidad }}</td>
                <td>{{ $stock->producto }}</td>
                <td class="text-right font-bold">${{ number_format($stock->pivot->cantidad * $stock->pivot->precio_unitario, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="divider"></div>

{{-- Totales --}}
<table class="totals-pos">
    <tr>
        <td class="text-left font-bold">Costo Total Estimado:</td>
        <td class="text-right font-bold">${{ number_format($mantenimiento->costo, 0, ',', '.') }}</td>
    </tr>
    @if($mantenimiento->abonos->count() > 0)
        <tr>
            <td class="text-left">Total Abonado:</td>
            <td class="text-right font-bold">- ${{ number_format($mantenimiento->total_abonado, 0, ',', '.') }}</td>
        </tr>
    @endif
    <tr class="grand-total">
        <td class="text-left">SALDO PENDIENTE:</td>
        <td class="text-right font-bold">
            ${{ number_format($mantenimiento->saldo_pendiente, 0, ',', '.') }}
        </td>
    </tr>
</table>

{{-- Historial de abonos si existen --}}
@if($mantenimiento->abonos->count() > 0)
    <div class="divider"></div>
    <div style="font-size: 7.5pt; font-weight: bold; text-align: center; margin: 2px 0;">HISTORIAL DE ABONOS</div>
    <table class="table-pos" style="font-size: 7pt;">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>MEDIO</th>
                <th class="text-right">VALOR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimiento->abonos->sortBy('fecha') as $abono)
            <tr>
                <td>{{ \Carbon\Carbon::parse($abono->fecha)->format('d/m/y') }}</td>
                <td>{{ $abono->tipo_pago === 'efectivo' ? 'Efectivo' : 'Transf.' }}</td>
                <td class="text-right font-bold">${{ number_format($abono->monto, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div style="margin-top: 15px; text-align: center;">
    <div style="width: 70%; margin: 15px auto 2px auto; border-top: 1px dashed #000;"></div>
    <span style="font-size: 7pt;">Firma Cliente / Entrega Conforme</span>
</div>
@endsection
