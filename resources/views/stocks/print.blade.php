@extends('layouts.print')

@section('title', 'Ficha de Producto - ' . ($stock->codigo ?? $stock->producto))

@section('watermark_class', !$stock->active ? 'anulado' : '')

@section('doc_title')
    FICHA DE CONTROL DE STOCK / INVENTARIO
@endsection

@section('content')
<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 2px;">
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Producto:</strong> <strong>{{ $stock->producto }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Categoría:</strong> <strong>{{ $stock->categoria ?: 'General' }} {{ $stock->subcategoria ? ' / ' . $stock->subcategoria : '' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Proveedor:</strong> <strong>{{ $proveedor->nombre_razon_social ?? '—' }}</strong></p>
    </div>
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Código / Ref:</strong> <strong>{{ $stock->codigo ?: '—' }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Existencias:</strong> <strong>{{ $stock->cantidad }} Unidades</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Estado:</strong> <span style="text-transform: uppercase; font-weight: bold;">{{ $stock->active ? 'ACTIVO' : 'INACTIVO' }}</span></p>
    </div>
</div>

<div style="padding: 2px 5px; border: 1px solid #ccc; background: #fafafa; margin-bottom: 2px; font-size: 7.5pt;">
    <strong>Información de Registro:</strong><br>
    Fecha de Registro: <strong>{{ $stock->created_at ? \Carbon\Carbon::parse($stock->created_at)->format('d/m/Y h:i A') : '—' }}</strong> &nbsp;|&nbsp; 
    Identificación Proveedor: <strong>{{ $proveedor->identificacion ?? '—' }}</strong>
</div>

<p class="font-bold" style="margin: 2px 0 1px 0; font-size: 7.5pt;">Estructura de Precios y Costos:</p>
<table class="items-table" style="margin-bottom: 2px;">
    <thead>
        <tr style="background: #f8fafc;">
            <th style="padding: 1px 2px; font-size: 6.8pt;">CONCEPTO / TARIFA</th>
            <th class="text-center" style="width: 25%; padding: 1px 2px; font-size: 6.8pt;">UTILIDAD / MARGEN</th>
            <th class="text-right" style="width: 25%; padding: 1px 2px; font-size: 6.8pt;">VALOR UNITARIO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">Costo de Compra (Precio Proveedor)</td>
            <td class="text-center text-gray-500" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">—</td>
            <td class="text-right" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">${{ number_format($stock->precio_compra, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">Precio Especial a Técnico</td>
            @php
                $utilidadTecnico = $stock->precio_compra > 0 ? (($stock->precio_tecnico - $stock->precio_compra) / $stock->precio_compra) * 100 : 0;
            @endphp
            <td class="text-center" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">+{{ number_format($utilidadTecnico, 0) }}%</td>
            <td class="text-right" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">${{ number_format($stock->precio_tecnico, 0, ',', '.') }}</td>
        </tr>
        <tr style="background-color: #fafafa;">
            <td class="font-bold" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">Precio de Venta Público (PVP)</td>
            <td class="text-center font-bold" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">+{{ number_format($stock->utilidad ?? 0, 0) }}%</td>
            <td class="text-right font-bold" style="padding: 1px 2px; font-size: 6.5pt; font-weight: bold;">${{ number_format($stock->precio_venta, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<div class="signatures-block clearfix" style="position: absolute; bottom: 38px; left: 0; width: 100%;">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Responsable de Inventario</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 3px; font-size: 7.5pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
