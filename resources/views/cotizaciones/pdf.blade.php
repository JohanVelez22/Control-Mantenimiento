@extends('layouts.print')

@section('title', 'Cotización ' . $cotizacion->codigo)

@section('watermark_class', $cotizacion->anulado ? 'anulado' : ($cotizacion->estado === 'rechazada' ? 'rechazada' : ''))

@section('doc_title')
    COTIZACIÓN COMERCIAL - {{ $cotizacion->codigo }}
@endsection

@section('content')
<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 6px;">
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Cliente:</strong> {{ $cotizacion->cliente->nombre }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Identificación:</strong> {{ $cotizacion->cliente->identificacion }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Teléfono:</strong> {{ $cotizacion->cliente->movil ?? 'N/A' }}</p>
    </div>
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Validez:</strong> {{ $cotizacion->validez_dias }} días</p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Vendedor:</strong> {{ $cotizacion->user->name }}</p>
    </div>
</div>

<table class="items-table">
    <thead>
        <tr>
            <th class="text-center" style="width: 10%; font-size: 7.5pt;">CANT</th>
            <th class="text-center" style="width: 15%; font-size: 7.5pt;">TIPO</th>
            <th class="text-center" style="font-size: 7.5pt;">DESCRIPCIÓN</th>
            <th class="text-center" style="width: 20%; font-size: 7.5pt;">V. UNITARIO</th>
            <th class="text-center" style="width: 20%; font-size: 7.5pt;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->items as $item)
        <tr>
            <td class="text-center" style="font-size: 7.5pt;">{{ $item->cantidad }}</td>
            <td class="text-center" style="font-size: 7.5pt;">{{ $item->tipo === 'stock' ? 'Producto' : 'Servicio' }}</td>
            <td class="text-center" style="font-size: 7.5pt;">
                {{ $item->descripcion }}
                @if($item->stock)
                    <br><span style="font-size: 6.5pt; color: #666;">Ref: {{ $item->stock->codigo }}</span>
                @endif
            </td>
            <td class="text-center" style="font-size: 7.5pt;">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-center" style="font-size: 7.5pt;">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table style="width: 100%; border-collapse: collapse; margin-top: 2px; margin-bottom: 4px;">
    <tr>
        <td colspan="3" style="width: 60%;"></td>
        <td style="width: 20%; text-align: right; font-weight: bold; font-size: 7.5pt; padding: 3px 6px; border-top: 1.5px solid #000;">TOTAL PRESUPUESTO:</td>
        <td style="width: 20%; text-align: center; font-weight: bold; font-size: 7.5pt; padding: 3px 2px; border-top: 1.5px solid #000;">${{ number_format($cotizacion->total, 0, ',', '.') }}</td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 8px;">
    <tr>
        <td style="border: 1px solid #ccc; padding: 4px 8px; background: #fafafa; font-size: 7.5pt;">
            <strong style="font-size: 7.5pt;">Condiciones y Notas:</strong><br>
            <span style="font-size: 7.5pt;">{!! nl2br(e($cotizacion->notas ?: 'Sin notas adicionales.')) !!}</span>
            <div style="margin-top: 3px; font-size: 6.5pt; color: #666; font-style: italic;">
                * Esta cotización no es un comprobante de pago ni representa una obligación fiscal o contable. Los precios pueden estar sujetos a cambio después de la fecha de validez establecida.
            </div>
        </td>
    </tr>
</table>

<div class="signatures-block clearfix" style="position: absolute; bottom: 58px; left: 0; width: 100%;">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 4px; font-size: 7.5pt;">
        <strong>Aprobación del Cliente</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 4px; font-size: 7.5pt;">
        <strong>{{ $empresa->nombre ?? 'Elaborado por' }}</strong>
    </div>
</div>
@endsection
