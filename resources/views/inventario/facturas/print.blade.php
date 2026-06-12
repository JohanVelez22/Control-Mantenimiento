<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        /* ─── Reset & Base ─── */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Arial', 'Helvetica', sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }

        /* ─── Layout ─── */
        .factura-wrapper { max-width: 800px; margin: 0 auto; padding: 32px; position: relative; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 3px solid #1e40af; padding-bottom: 16px; }
        .company-info h1 { font-size: 20px; font-weight: 900; color: #1e40af; }
        .company-info p  { font-size: 10px; color: #555; }
        .factura-meta    { text-align: right; }
        .factura-meta .numero { font-size: 18px; font-weight: 900; color: #1e40af; }
        .factura-meta .fecha  { font-size: 10px; color: #666; }

        /* Tipo badge */
        .badge-tipo { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-compra { background: #fff7ed; color: #c2410c; border: 1px solid #fb923c; }
        .badge-venta  { background: #f0fdf4; color: #166534; border: 1px solid #4ade80; }

        /* Entidad */
        .entidad-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 20px; }
        .entidad-box .label { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .entidad-box .nombre { font-size: 14px; font-weight: 700; color: #1e293b; }
        .entidad-box .detalle { font-size: 10px; color: #64748b; margin-top: 2px; }

        /* Tabla de ítems */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table thead { background: #1e40af; color: #fff; }
        .items-table thead th { padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table thead th.right { text-align: right; }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        .items-table tbody td.right { text-align: right; }

        /* Totales */
        .totales { float: right; width: 280px; border-collapse: collapse; margin-bottom: 20px; }
        .totales td { padding: 5px 10px; font-size: 11px; }
        .totales .total-row td { font-weight: 900; font-size: 13px; border-top: 2px solid #1e40af; padding-top: 8px; color: #1e40af; }
        .totales .saldo-row td  { color: #dc2626; font-weight: 700; }
        .totales .pagado-row td { color: #16a34a; }
        .totales td:last-child  { text-align: right; }

        /* Observaciones */
        .obs { clear: both; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px; padding: 8px 12px; font-size: 10px; color: #92400e; margin-bottom: 20px; white-space: pre-wrap; }

        /* Pie de página */
        .footer { border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #94a3b8; display: flex; justify-content: space-between; }

        /* ═══════════════════════════════════════════════════════
           MARCA DE AGUA: visible en pantalla y en @media print
           ═══════════════════════════════════════════════════════ */
        .watermark {
            display: none; /* Oculto por defecto, solo visible si está anulada */
        }
        .factura-anulada .watermark {
            display: block;
            position: fixed;   /* Fijo en pantalla */
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-42deg);
            font-size: 96px;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.12);    /* Rojo muy translúcido */
            letter-spacing: 12px;
            text-transform: uppercase;
            white-space: nowrap;
            pointer-events: none;
            user-select: none;
            z-index: 0;
        }
        /* Todo el contenido debe estar por encima de la marca */
        .factura-wrapper > *:not(.watermark) { position: relative; z-index: 1; }

        /* ─── Print ─── */
        @media print {
            @page { margin: 15mm 20mm; size: A4; }
            body { font-size: 11px; }
            .no-print { display: none !important; }
            .factura-wrapper { padding: 0; max-width: 100%; }

            /* En impresión: marca de agua con position:absolute relativa a la página */
            .factura-anulada .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-42deg);
                font-size: 100px;
                color: rgba(200, 0, 0, 0.15) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="factura-wrapper {{ $factura->estado === 'anulada' ? 'factura-anulada' : '' }}">

    {{-- MARCA DE AGUA (solo visible si anulada) --}}
    <div class="watermark" aria-hidden="true">ANULADA</div>

    {{-- Botón imprimir (no imprimible) --}}
    <div class="no-print" style="text-align:right; margin-bottom: 16px;">
        <button onclick="window.print()" style="background:#1e40af;color:#fff;border:none;padding:8px 20px;border-radius:8px;font-weight:700;cursor:pointer;">🖨️ Imprimir</button>
        <a href="{{ route('inventario.facturas.show', $factura->id) }}" style="margin-left:8px;color:#64748b;font-size:12px;">← Volver</a>
    </div>

    {{-- Encabezado --}}
    <div class="header">
        <div class="company-info">
            <h1>Control Mantenimiento</h1>
            <p>Sistema de Gestión de Equipos y Servicios</p>
        </div>
        <div class="factura-meta">
            <p class="numero">{{ $factura->numero_factura }}</p>
            <p class="fecha">Fecha: {{ $factura->fecha->format('d/m/Y') }}</p>
            <p style="margin-top:4px;">
                <span class="badge-tipo {{ $factura->tipo_movimiento === 'compra' ? 'badge-compra' : 'badge-venta' }}">
                    {{ $factura->tipo_movimiento === 'compra' ? 'Compra' : 'Venta' }}
                </span>
            </p>
        </div>
    </div>

    {{-- Entidad --}}
    <div class="entidad-box">
        <p class="label">{{ $factura->tipo_movimiento === 'compra' ? 'Proveedor' : 'Cliente' }}</p>
        <p class="nombre">{{ $factura->facturable->nombre_razon_social ?? $factura->facturable->nombre ?? '—' }}</p>
        <p class="detalle">
            {{ $factura->facturable->identificacion ?? '' }}
            @if(!empty($factura->facturable->email)) · {{ $factura->facturable->email }} @endif
            @if(!empty($factura->facturable->telefono)) · {{ $factura->facturable->telefono }} @endif
        </p>
    </div>

    {{-- Artículos --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto / Descripción</th>
                <th class="right">Cant.</th>
                <th class="right">P. Unitario</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->stock->producto }}</td>
                <td class="right">{{ $item->cantidad }}</td>
                <td class="right">${{ number_format($item->precio_unitario, 0, ',', '.') }}</td>
                <td class="right">${{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    <table class="totales">
        <tr>
            <td>Subtotal:</td>
            <td>${{ number_format($factura->total_documento, 0, ',', '.') }}</td>
        </tr>
        <tr class="pagado-row">
            <td>Total Pagado:</td>
            <td>${{ number_format($factura->total_pagado, 0, ',', '.') }}</td>
        </tr>
        @if($factura->saldo_pendiente > 0)
        <tr class="saldo-row">
            <td>⚠️ Saldo Pendiente:</td>
            <td>${{ number_format($factura->saldo_pendiente, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL:</td>
            <td>${{ number_format($factura->total_documento, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div style="clear:both;"></div>

    @if($factura->observaciones)
    <div class="obs">📝 {{ $factura->observaciones }}</div>
    @endif

    <div class="footer">
        <span>Registrado por: {{ $factura->user->name ?? '—' }} — {{ $factura->created_at->format('d/m/Y H:i') }}</span>
        <span>Estado: <strong>{{ strtoupper(str_replace('_', ' ', $factura->estado)) }}</strong></span>
    </div>

</div>
</body>
</html>
