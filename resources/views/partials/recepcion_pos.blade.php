@extends('layouts.print_pos')

{{-- S-04: comprobante de recepción con el código de consulta. Se entrega al cliente al recibir el equipo. --}}
@section('title', 'Recepción '.$orden->id_orden)

@section('doc_title')
    COMPROBANTE DE RECEPCIÓN #{{ $orden->id_orden }}
@endsection

@section('content')
<div class="info-pos">
    <p><strong>Fecha de ingreso:</strong> {{ $orden->fecha_entrada ? \Carbon\Carbon::parse($orden->fecha_entrada)->format('d/m/Y') : '—' }}</p>
    <p><strong>{{ $orden->equipo?->propietario_tipo === 'proveedor' ? 'Proveedor:' : 'Cliente:' }}</strong> {{ $orden->equipo?->propietario_nombre ?? 'N/A' }}</p>
    <p><strong>Doc.:</strong> {{ $orden->equipo?->propietario_identificacion ?: 'N/A' }}</p>
    <p><strong>Técnico:</strong> {{ $orden->tecnico->nombre ?? 'N/A' }}</p>
</div>

<div class="divider"></div>

<div class="info-pos">
    <p><strong>Equipo:</strong> {{ $orden->equipo->nombre ?? 'N/A' }}</p>
    <p><strong>Marca/Mod:</strong> {{ trim(($orden->equipo->marca ?? '').' '.($orden->equipo->modelo ?? '')) ?: '—' }}</p>
    @if(!empty($orden->equipo->serie))
        <p><strong>Serie:</strong> {{ \Illuminate\Support\Str::upper($orden->equipo->serie) }}</p>
    @endif
    <p><strong>Servicio:</strong> {{ \Illuminate\Support\Str::upper($orden->tipo) }} — {{ \Illuminate\Support\Str::upper($orden->reparacion) }}</p>
    <p><strong>Motivo de ingreso:</strong> {{ $descripcion ?: 'Sin observaciones.' }}</p>
    <p><strong>Costo estimado:</strong> $ {{ number_format((float) $orden->costo, 0, ',', '.') }}</p>
</div>

@if($orden->codigo_consulta_formateado)
<div class="divider"></div>
<div class="info-pos" style="text-align: center;">
    <p><strong>CÓDIGO DE CONSULTA</strong></p>
    <p style="font-size: 14pt; font-weight: bold; letter-spacing: 2px;">{{ $orden->codigo_consulta_formateado }}</p>
    <p style="font-size: 7pt;">Consulte el estado de su equipo con su número de documento y este código. Consérvelo: es personal.</p>
</div>
@endif

<div class="divider"></div>
<div class="info-pos" style="font-size: 7pt;">
    <p>Recibido por: ______________________</p>
    <p>Firma del cliente: __________________</p>
</div>
@endsection
