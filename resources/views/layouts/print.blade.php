<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Documento')</title>
    <style>
        * { box-sizing: border-box; }
        @page {
            size: 5.5in 8.5in; /* Media Carta / Statement */
            margin: 0.5in;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #111;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-logo {
            max-width: 120px;
            max-height: 80px;
        }
        .header-info {
            text-align: right;
            flex-grow: 1;
        }
        .header-info h1 {
            margin: 0 0 5px;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-info p {
            margin: 2px 0;
            font-size: 9pt;
            color: #444;
        }
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
            background-color: #f3f4f6;
            padding: 5px;
            border: 1px solid #ddd;
        }
        .info-grid {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 20px;
        }
        .info-col {
            flex: 1;
        }
        .info-col p {
            margin: 3px 0;
            font-size: 9.5pt;
        }
        .info-col strong {
            display: inline-block;
            width: 80px;
        }
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.items-table th, table.items-table td {
            border: 1px solid #ccc;
            padding: 6px;
            font-size: 9pt;
        }
        table.items-table th {
            background-color: #f3f4f6;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.items-table td.text-right { text-align: right; }
        table.items-table th.text-right { text-align: right; }
        table.items-table td.text-center { text-align: center; }
        table.items-table th.text-center { text-align: center; }

        .totals {
            width: 50%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .totals td {
            padding: 4px 6px;
            font-size: 9.5pt;
        }
        .totals td.lbl { font-weight: bold; text-align: right; }
        .totals td.val { text-align: right; border-bottom: 1px solid #ddd; }
        .totals tr.grand-total td { font-size: 11pt; font-weight: bold; border-top: 2px solid #000; border-bottom: none; }
        
        .clearfix::after { content: ""; clear: both; display: table; }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #000;
            text-align: center;
            font-size: 8pt;
            color: #555;
        }
        
        .watermark-container { position: relative; }
        .watermark-container.anulado::after {
            content: "ANULADO";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 6rem;
            font-weight: 900;
            color: rgba(239, 68, 68, 0.15);
            z-index: 1000;
            pointer-events: none;
            white-space: nowrap;
        }
        @media print {
            .watermark-container.anulado::after {
                color: rgba(200, 0, 0, 0.2) !important;
            }
        }
        .text-center { text-align: center; }
        .mt-4 { margin-top: 15px; }
        .mb-4 { margin-bottom: 15px; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
    @php
        $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
    @endphp

    <div class="watermark-container @yield('watermark_class')">
        <div class="header">
            @if($empresa->logo_path)
                <img src="{{ Storage::url($empresa->logo_path) }}" alt="Logo" class="header-logo">
            @else
                <div class="header-logo" style="width: 120px; height: 80px; display: flex; align-items: center; justify-content: center; background: #eee; font-size: 10pt; color: #666; font-weight: bold;">SIN LOGO</div>
            @endif
            <div class="header-info">
                <h1>{{ $empresa->nombre }}</h1>
                @if($empresa->nit)<p>NIT: {{ $empresa->nit }}</p>@endif
                @if($empresa->telefono)<p>Tel: {{ $empresa->telefono }}</p>@endif
                @if($empresa->direccion)<p>Dir: {{ $empresa->direccion }}</p>@endif
                @if($empresa->correo)<p>Email: {{ $empresa->correo }}</p>@endif
            </div>
        </div>

        <div class="doc-title">
            @yield('doc_title', 'DOCUMENTO')
        </div>

        @yield('content')

        <div class="footer">
            <p>{{ $empresa->pie_pagina_factura ?? 'Gracias por preferirnos.' }}</p>
        </div>
    </div>
</body>
</html>
