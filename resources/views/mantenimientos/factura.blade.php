<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura POS — {{ $mantenimiento->id_orden }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            margin: 0;
            padding: 10px 8px 14px;
            color: #111;
            background: #fff;
        }
        .ticket { max-width: 100%; margin: 0 auto; }

        .brand {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 8px;
            border-bottom: 2px solid #111;
        }
        .brand-name {
            margin: 0;
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .brand-tag {
            margin: 4px 0 0;
            font-size: 7.5pt;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .brand-meta {
            margin: 8px 0 0;
            font-size: 7pt;
            color: #444;
            line-height: 1.45;
        }
        .brand-meta p { margin: 1px 0; }

        /* TICKET Nº y orden en una sola línea (misma fila) */
        .ticket-order-bar {
            display: table;
            width: 100%;
            margin: 10px 0 8px;
            padding: 8px 6px;
            background: #f3f4f6;
            border: 1px solid #111;
            border-radius: 2px;
        }
        .ticket-order-bar .lbl {
            display: table-cell;
            vertical-align: middle;
            white-space: nowrap;
            font-weight: 700;
            font-size: 8pt;
            letter-spacing: 0.06em;
            width: 1%;
            padding-right: 6px;
        }
        .ticket-order-bar .ord {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-weight: 800;
            font-size: 12pt;
            letter-spacing: 0.04em;
        }

        .meta-table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 7.5pt; }
        .meta-table td { padding: 3px 0; vertical-align: top; }
        .meta-table td:first-child {
            font-weight: 700;
            width: 38%;
            color: #222;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .meta-table td:last-child { text-align: right; }

        .section-title {
            text-align: center;
            font-size: 7.5pt;
            font-weight: 800;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin: 12px 0 6px;
            padding: 4px 0;
            border-top: 1px dashed #333;
            border-bottom: 1px dashed #333;
        }

        .detail-box {
            border: 1px solid #333;
            padding: 8px 6px;
            margin: 6px 0 10px;
            background: #fafafa;
        }
        .detail-box p { margin: 0 0 5px; font-size: 7.5pt; }
        .detail-box p:last-child { margin-bottom: 0; }
        .detail-box .k { font-weight: 700; }

        /* Observaciones: centrado y mayúsculas */
        .obs-wrap {
            margin: 12px 0 10px;
            padding: 10px 6px;
            border: 1px dashed #333;
            text-align: center;
        }
        .obs-title {
            font-size: 7.5pt;
            font-weight: 800;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin: 0 0 8px;
            color: #111;
        }
        .obs-text {
            margin: 0;
            font-size: 7.5pt;
            line-height: 1.45;
            text-transform: uppercase;
            text-align: center;
            color: #222;
        }

        .total-wrap {
            margin: 12px 0 10px;
            padding: 10px 8px;
            border-top: 2px solid #111;
            border-bottom: 2px solid #111;
            color: #111;
        }
        .total-wrap table { width: 100%; border-collapse: collapse; }
        .total-wrap td { font-size: 10pt; font-weight: 800; padding: 0; }
        .total-wrap td:last-child { text-align: right; font-size: 11pt; }

        .code-block {
            text-align: center;
            margin: 10px 0 6px;
            padding-top: 6px;
            border-top: 1px dashed #666;
        }
        .code-bars {
            font-family: DejaVu Sans Mono, Courier New, monospace;
            font-size: 16pt;
            letter-spacing: 0;
            line-height: 1;
            color: #111;
        }
        .code-id { margin: 4px 0 0; font-size: 7.5pt; letter-spacing: 0.15em; font-weight: 600; }

        .footer {
            text-align: center;
            font-size: 6.5pt;
            color: #444;
            line-height: 1.45;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
        }
        .footer .thanks {
            margin: 6px 0 4px;
            font-weight: 800;
            font-size: 7pt;
            letter-spacing: 0.06em;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <header class="brand">
            <p class="brand-name">Taller De Soporte</p>
            <p class="brand-tag">Servicio Tecnico Especializado</p>
            <div class="brand-meta">
                <p>NIT: 900.123.456-7</p>
                <p>Tel: +57 300 000 0000</p>
                <p>Dir: Calle Falsa 123, Local 4</p>
            </div>
        </header>

        <div class="ticket-order-bar">
            <span class="lbl">TICKET Nº</span>
            <span class="ord">{{ $mantenimiento->id_orden }}</span>
        </div>

        <table class="meta-table">
            <tr>
                <td>Emisión</td>
                <td>{{ now()->format('d/m/Y h:i A') }}</td>
            </tr>
            <tr>
                <td>Estado</td>
                <td style="text-transform: uppercase; font-weight: 700;">{{ $mantenimiento->estado }}</td>
            </tr>
            <tr>
                <td>Ingreso</td>
                <td>{{ $mantenimiento->fecha_entrada ? \Carbon\Carbon::parse($mantenimiento->fecha_entrada)->format('d/m/Y') : '—' }}</td>
            </tr>
            <tr>
                <td>Salida</td>
                <td>{{ $mantenimiento->fecha_salida ? \Carbon\Carbon::parse($mantenimiento->fecha_salida)->format('d/m/Y') : '—' }}</td>
            </tr>
        </table>

        <p class="section-title">Datos del cliente</p>
        <table class="meta-table">
            <tr>
                <td>Cliente</td>
                <td>{{ $mantenimiento->equipo->cliente->nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Técnico</td>
                <td>{{ $mantenimiento->tecnico->nombre ?? 'N/A' }}</td>
            </tr>
        </table>

        <p class="section-title">Detalle del servicio</p>
        <div class="detail-box">
            <p><span class="k">Equipo:</span> {{ $mantenimiento->equipo->nombre ?? 'N/A' }}</p>
            <p><span class="k">Marca / modelo:</span> {{ trim(($mantenimiento->equipo->marca ?? '') . ' ' . ($mantenimiento->equipo->modelo ?? '')) ?: '—' }}</p>
            <p><span class="k">Serie (S/N):</span> {{ $mantenimiento->equipo->serie ?? 'N/A' }}</p>
            <p style="margin-top: 6px; padding-top: 6px; border-top: 1px dotted #999; white-space: nowrap;">
                <span class="k">Servicio:</span> {{ strtoupper($mantenimiento->tipo) }} — {{ strtoupper($mantenimiento->reparacion) }}
            </p>
        </div>

        <div class="obs-wrap">
            <p class="obs-title">Observaciones</p>
            <p class="obs-text">{{ \Illuminate\Support\Str::upper($mantenimiento->descripcion ?: 'Sin observaciones adicionales.') }}</p>
        </div>

        <div class="total-wrap">
            <table>
                <tr>
                    <td>TOTAL A PAGAR</td>
                    <td>${{ number_format($mantenimiento->costo, 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>

        <div class="code-block">
            <div class="code-bars">||||||||||||||||||||||</div>
            <p class="code-id">{{ $mantenimiento->id_orden }}</p>
        </div>

        <footer class="footer">
            <p>Registrado por: <strong>{{ $mantenimiento->user->name ?? 'Sistema' }}</strong></p>
            <p class="thanks">Gracias por preferirnos</p>
            <p>Conserve este comprobante para garantías o reclamos (válido según políticas del taller).</p>
        </footer>
    </div>
</body>
</html>
