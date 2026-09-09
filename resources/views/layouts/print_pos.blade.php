<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Ticket POS')</title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        @page {
            margin: 1mm 2mm 0.5mm 2mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .ticket-wrapper {
            width: 100%;
            max-width: 74mm;
            margin: 0 auto;
            padding: 0.5mm 0.5mm 0 0.5mm;
            page-break-inside: avoid !important;
        }
        .header-pos {
            text-align: center;
            margin-bottom: 3px;
            page-break-inside: avoid !important;
        }
        .header-logo {
            max-width: 120px;
            max-height: 44px;
            object-fit: contain;
            margin: 0 auto 2px auto;
            display: block;
        }
        .header-pos h1 {
            font-size: 9.5pt;
            font-weight: bold;
            margin: 1px 0;
            text-transform: uppercase;
        }
        .header-pos p {
            margin: 0.5px 0;
            font-size: 7pt;
            color: #111;
        }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 3px 0;
        }
        .divider-double {
            border-bottom: 1.5px solid #000;
            margin: 4px 0;
        }
        .doc-title-pos {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 2px 0;
            margin: 2px 0;
            background: #eee;
            border: 1px dashed #888;
            page-break-inside: avoid !important;
        }
        .info-pos {
            font-size: 7pt;
            margin-bottom: 2px;
            page-break-inside: avoid !important;
        }
        .info-pos p {
            margin: 0.5px 0;
        }
        .table-pos {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            margin: 2px 0;
            page-break-inside: avoid !important;
        }
        .table-pos tr, .table-pos td, .table-pos th {
            page-break-inside: avoid !important;
        }
        .table-pos th {
            border-bottom: 1px dashed #000;
            border-top: 1px dashed #000;
            padding: 2px 1px;
            text-align: left;
            font-weight: bold;
            font-size: 6.8pt;
            text-transform: uppercase;
        }
        .table-pos td {
            padding: 1.5px 1px;
            vertical-align: top;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .font-bold { font-weight: bold; }
        .totals-pos {
            width: 100%;
            margin-top: 3px;
            font-size: 7.5pt;
            page-break-inside: avoid !important;
        }
        .totals-pos tr, .totals-pos td {
            page-break-inside: avoid !important;
        }
        .totals-pos td {
            padding: 1px 0;
        }
        .totals-pos .grand-total {
            font-size: 9pt;
            font-weight: bold;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2.5px 0;
        }
        .footer-pos {
            text-align: center;
            font-size: 6.8pt;
            color: #333;
            margin-top: 5px;
            padding-top: 3px;
            margin-bottom: 0;
            padding-bottom: 0;
            border-top: 1px dashed #000;
            page-break-inside: avoid !important;
        }
        .footer-pos p {
            margin: 1px 0;
        }
        .watermark-anulado {
            border: 2px solid #c00;
            color: #c00;
            font-weight: bold;
            text-align: center;
            font-size: 11pt;
            padding: 3px;
            margin: 4px 0;
            text-transform: uppercase;
        }
    </style>
</head>
<body onload="window.print()">
    @php
        $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
        $logoBase64 = \Illuminate\Support\Facades\Cache::remember('empresa_logo_base64', 3600, function () use ($empresa) {
            if ($empresa->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->logo_path)) {
                $type = pathinfo($empresa->logo_path, PATHINFO_EXTENSION);
                $data = \Illuminate\Support\Facades\Storage::disk('public')->get($empresa->logo_path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
            return null;
        });
    @endphp

    <div class="ticket-wrapper">
        {{-- Header Empresa --}}
        <div class="header-pos">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" class="header-logo">
            @endif
            <h1>{{ Str::upper($empresa->nombre) }}</h1>
            @if($empresa->nit)<p><strong>NIT:</strong> {{ $empresa->nit }}</p>@endif
            @if($empresa->direccion)<p>{{ $empresa->direccion }}</p>@endif
            @if($empresa->telefono)<p><strong>Tel:</strong> {{ $empresa->telefono }}</p>@endif
            @if($empresa->correo)<p>{{ $empresa->correo }}</p>@endif
        </div>

        @hasSection('watermark')
            <div class="watermark-anulado">@yield('watermark')</div>
        @endif

        <div class="doc-title-pos">
            @yield('doc_title', 'TICKET DE VENTA')
        </div>

        @yield('content')

        {{-- Pie de página y garantía --}}
        <div class="footer-pos">
            <p class="font-bold">{{ $empresa->pie_pagina_factura ?? '¡Gracias por su compra / preferencia!' }}</p>
            <p style="font-size: 6.5pt; color: #555;">Software Tecni-Systemas</p>
        </div>
    </div>
</body>
</html>
