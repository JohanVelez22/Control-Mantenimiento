@extends('layouts.print')

@section('title', 'Cotización ' . $cotizacion->codigo)

@section('watermark_class', $cotizacion->anulado ? 'anulado' : ($cotizacion->estado === 'rechazada' ? 'rechazada' : ''))

@section('doc_title')
    COTIZACIÓN COMERCIAL - {{ $cotizacion->codigo }}
@endsection

@section('content')
<div class="info-grid" style="font-size: 7.5pt; margin-bottom: 2px;">
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Cliente:</strong> <strong>{{ $cotizacion->cliente->nombre }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Identificación:</strong> <strong>{{ $cotizacion->cliente->identificacion }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Teléfono:</strong> <strong>{{ $cotizacion->cliente->movil ?? 'N/A' }}</strong></p>
    </div>
    <div class="info-col">
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Fecha Emisión:</strong> <strong>{{ \Carbon\Carbon::parse($cotizacion->fecha)->format('d/m/Y') }}</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Validez:</strong> <strong>{{ $cotizacion->validez_dias }} días</strong></p>
        <p style="font-size: 7.5pt; margin: 1px 0;"><strong>Vendedor:</strong> <strong>{{ $cotizacion->user->name }}</strong></p>
    </div>
</div>

<table class="items-table" style="margin-bottom: 2px;">
    <thead>
        <tr style="background: #f8fafc;">
            <th class="text-center" style="width: 10%; font-size: 6.8pt; padding: 1px 2px;">CANT</th>
            <th class="text-center" style="width: 15%; font-size: 6.8pt; padding: 1px 2px;">TIPO</th>
            <th class="text-center" style="font-size: 6.8pt; padding: 1px 2px;">DESCRIPCIÓN</th>
            <th class="text-center" style="width: 20%; font-size: 6.8pt; padding: 1px 2px;">V. UNITARIO</th>
            <th class="text-center" style="width: 20%; font-size: 6.8pt; padding: 1px 2px;">SUBTOTAL</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cotizacion->items as $item)
        <tr style="font-weight: bold;">
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">{{ $item->cantidad }}</td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">{{ $item->tipo === 'stock' ? 'Producto' : 'Servicio' }}</td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">
                {{ $item->descripcion }}
                @if($item->stock)
                    <br><span style="font-size: 6pt; color: #555;">Ref: {{ $item->stock->codigo }}</span>
                @endif
            </td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
            <td class="text-center" style="font-size: 6.5pt; padding: 1px 2px; font-weight: bold;">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table style="width: 100%; border-collapse: collapse; margin-top: 2px; margin-bottom: 2px;">
    <tr>
        <td colspan="3" style="width: 60%;"></td>
        <td style="width: 20%; text-align: right; font-weight: bold; font-size: 6.8pt; padding: 1px 4px; border-top: 1.5px solid #000;">TOTAL PRESUPUESTO:</td>
        <td style="width: 20%; text-align: center; font-weight: bold; font-size: 6.8pt; padding: 1px 2px; border-top: 1.5px solid #000;">${{ number_format($cotizacion->total, 0, ',', '.') }}</td>
    </tr>
</table>

<table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
    <tr>
        <td style="border: 1px solid #ccc; padding: 2px 5px; background: #fafafa; font-size: 6.8pt;">
            <strong style="font-size: 6.8pt;">CONDICIONES Y NOTAS:</strong><br>
            <span style="font-size: 6.5pt; font-weight: normal; color: #333;">{!! nl2br(e($cotizacion->notas ?: 'Sin notas adicionales.')) !!}</span>
            <div style="margin-top: 2px; font-size: 5.8pt; color: #666; font-style: italic;">
                * Esta cotización no es un comprobante de pago ni representa una obligación fiscal o contable. Los precios pueden estar sujetos a cambio después de la fecha de validez establecida.
            </div>
        </td>
    </tr>
</table>

<div class="signatures-block clearfix" style="position: absolute; bottom: 38px; left: 0; width: 100%;">
    <div style="float: left; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 4px; font-size: 7.5pt;">
        <strong>Aprobación del Cliente</strong>
    </div>
    <div style="float: right; text-align: center; border-top: 1px solid #333; width: 40%; padding-top: 4px; font-size: 7.5pt;">
        <strong>{{ $empresa->nombre ?? 'Elaborado por' }}</strong>
    </div>
</div>
@endsection
