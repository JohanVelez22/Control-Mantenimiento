@extends('layouts.print')

@section('title', 'Ficha de Producto - ' . ($stock->codigo ?? $stock->producto))

@section('watermark_class', !$stock->active ? 'anulado' : '')

@section('doc_title')
    FICHA DE CONTROL DE STOCK / INVENTARIO
@endsection

@section('content')
<div class="info-grid">
    <div class="info-col">
        <p><strong>Producto:</strong> {{ $stock->producto }}</p>
        <p><strong>Categoría:</strong> {{ $stock->categoria ?: 'General' }} {{ $stock->subcategoria ? ' / ' . $stock->subcategoria : '' }}</p>
        <p><strong>Proveedor:</strong> {{ $proveedor->nombre_razon_social ?? '—' }}</p>
    </div>
    <div class="info-col">
        <p><strong>Código / Ref:</strong> <span style="font-family: monospace;">{{ $stock->codigo ?: '—' }}</span></p>
        <p><strong>Existencias:</strong> {{ $stock->cantidad }} Unidades</p>
        <p><strong>Estado:</strong> <span style="text-transform: uppercase;">{{ $stock->active ? 'ACTIVO' : 'INACTIVO' }}</span></p>
    </div>
</div>

<div style="margin-bottom: 15px; padding: 10px; border: 1px solid #ccc; background: #fafafa;">
    <strong>Información de Registro:</strong><br>
    Fecha de Registro: {{ $stock->created_at ? \Carbon\Carbon::parse($stock->created_at)->format('d/m/Y h:i A') : '—' }} &nbsp;|&nbsp; 
    Identificación Proveedor: {{ $proveedor->identificacion ?? '—' }}
</div>

<p class="font-bold mb-4">Estructura de Precios y Costos:</p>
<table class="items-table">
    <thead>
        <tr>
            <th>CONCEPTO / TARIFA</th>
            <th class="text-center" style="width: 25%;">UTILIDAD / MARGEN</th>
            <th class="text-right" style="width: 25%;">VALOR UNITARIO</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Costo de Compra (Precio Proveedor)</td>
            <td class="text-center text-gray-500">—</td>
            <td class="text-right">${{ number_format($stock->precio_compra, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Precio Especial a Técnico</td>
            @php
                $utilidadTecnico = $stock->precio_compra > 0 ? (($stock->precio_tecnico - $stock->precio_compra) / $stock->precio_compra) * 100 : 0;
            @endphp
            <td class="text-center" style="color: #6b21a8; font-weight: bold;">+{{ number_format($utilidadTecnico, 0) }}%</td>
            <td class="text-right" style="color: #6b21a8; font-weight: bold;">${{ number_format($stock->precio_tecnico, 0, ',', '.') }}</td>
        </tr>
        <tr style="background-color: #fafafa;">
            <td class="font-bold">Precio de Venta Público (PVP)</td>
            <td class="text-center font-bold" style="color: #166534;">+{{ number_format($stock->utilidad ?? 0, 0) }}%</td>
            <td class="text-right font-bold" style="color: #166534;">${{ number_format($stock->precio_venta, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<div class="clearfix" style="margin-top: 45px;">
    <div style="float: left; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px; font-size: 8.5pt;">
        <strong>Responsable de Inventario</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px; font-size: 8.5pt;">
        <strong>Firma Autorizada</strong>
    </div>
</div>
@endsection
