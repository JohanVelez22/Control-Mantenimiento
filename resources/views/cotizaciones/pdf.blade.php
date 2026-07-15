@extends('layouts.print')

@section('title', 'Cotización ' . $cotizacion->codigo)

@section('watermark_class', $cotizacion->estado === 'rechazada' || $cotizacion->estado === 'vencida' ? 'anulado' : '')

@section('doc_title')
    COTIZACIÓN COMERCIAL - {{ $cotizacion->codigo }}
@endsection

@section('content')
<div class="info-grid">
    <div class="info-col">
        <p><strong>Cliente:</strong> {{ $cotizacion->cliente->nombre }}</p>
        <p><strong>Identificación:</strong> {{ $cotizacion->cliente->identificacion }}</p>
        <p><strong>Teléfono:</strong> {{ $cotizacion->cliente->telefono ?? 'N/A' }}</p>
    </div>
    <div class="info-col">
        <p><strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}</p>
        <p><strong>Validez:</strong> {{ $cotizacion->validez_dias }} días</p>
        <p><strong>Vendedor:</strong> {{ $cotizacion->user->name }}</p>
    </div>
</div>

<table class="items-table">
    <thead>
        <tr>
            <th class="text-center" style="width: 10%;">CANT</th>
            <th style="width: 15%;">TIPO</th>
            <th>DESCRIPCIÓN</th>
            <th class="text-right" style="width: 20%;">V. UNITARIO</th>
            <th class="text-right" style="width: 20%;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->items as $item)
        <tr>
            <td class="text-center">{{ $item->cantidad }}</td>
            <td>{{ $item->tipo === 'stock' ? 'Producto' : 'Servicio' }}</td>
            <td>
                {{ $item->descripcion }}
                @if($item->stock)
                    <br><span style="font-size: 7pt; color: #666;">Ref: {{ $item->stock->codigo }}</span>
                @endif
            </td>
            <td class="text-right">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-right">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="clearfix">
    <div style="float: left; width: 55%; border: 1px solid #ccc; padding: 10px; background: #fafafa; font-size: 8pt; min-height: 60px;">
        <strong>Condiciones y Notas:</strong><br>
        {!! nl2br(e($cotizacion->notas ?: 'Sin notas adicionales.')) !!}
        <br><br>
        <em style="color: #666;">* Esta cotización no es un comprobante de pago ni representa una obligación fiscal o contable. Los precios pueden estar sujetos a cambio después de la fecha de validez establecida.</em>
    </div>

    <table class="totals">
        <tr class="grand-total">
            <td class="lbl">TOTAL PRESUPUESTO:</td>
            <td class="val">${{ number_format($cotizacion->total, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

<div class="clearfix" style="margin-top: 65px;">
    <div style="float: left; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px; font-size: 8.5pt;">
        <strong>Aprobación del Cliente</strong><br>
        <span style="font-size: 7pt; color: #666;">Firma y Cédula</span>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #000; width: 40%; padding-top: 5px; font-size: 8.5pt;">
        <strong>{{ $empresa->nombre ?? 'Elaborado por' }}</strong>
    </div>
</div>
@endsection
