<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Financiero</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 9px; color: #1a202c; background: #fff; }

        .header {
            text-align: center;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 3px solid #2d3748;
        }
        .header h1 { font-size: 16px; font-weight: 700; color: #1a202c; letter-spacing: 1px; text-transform: uppercase; }
        .header p { font-size: 10px; color: #4a5568; margin-top: 4px; }

        /* Tarjetas resumen */
        .summary-grid { display: table; width: 100%; margin-bottom: 16px; border-spacing: 6px; }
        .summary-grid .card { display: table-cell; width: 25%; border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center; vertical-align: middle; }
        .card .card-label { font-size: 7.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #718096; margin-bottom: 3px; }
        .card .card-value { font-size: 13px; font-weight: 800; }
        .card.ingresos .card-value { color: #276749; }
        .card.egresos  .card-value { color: #9b2c2c; }
        .card.mantenimientos .card-value { color: #1a365d; }
        .card.anulados .card-value { color: #4a5568; }

        /* Tabla */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table { width: 100%; border-collapse: collapse; }
        thead tr th {
            background-color: #2d3748;
            color: #ffffff;
            padding: 5px 6px;
            text-transform: uppercase;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #1a202c;
        }
        tbody tr td {
            border: 1px solid #cbd5e0;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 8.5px;
        }
        tbody tr:nth-child(even) { background-color: #f7fafc; }
        tbody tr.anulado { opacity: 0.55; }

        td.tipo-badge { text-align: center; }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-mant   { background: #bee3f8; color: #2b6cb0; border: 1px solid #90cdf4; }
        .badge-elec   { background: #e9d8fd; color: #553c9a; border: 1px solid #d6bcfa; }
        .badge-ingreso{ background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .badge-egreso { background: #fed7d7; color: #9b2c2c; border: 1px solid #feb2b2; }
        .badge-venta  { background: #fefcbf; color: #7b6200; border: 1px solid #f6e05e; }
        .badge-compra { background: #feebc8; color: #7b341e; border: 1px solid #fbd38d; }

        .pill-active  { color: #276749; font-weight: 700; }
        .pill-anulado { color: #9b2c2c; font-weight: 700; font-style: italic; }

        td.monto { text-align: center; font-weight: 700; }
        td.monto.positivo { color: #276749; }
        td.monto.negativo { color: #9b2c2c; }

        tfoot tr td {
            background: #edf2f7;
            font-weight: 800;
            font-size: 9px;
            border: 1px solid #a0aec0;
            padding: 5px 6px;
        }

        .footer { margin-top: 16px; text-align: right; font-size: 8px; color: #718096; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Informe Financiero</h1>
        @if(isset($fecha))
        <p>Período: <strong>{{ $fecha }}</strong></p>
        @endif
        <p>Generado el: {{ date('d/m/Y h:i A') }}</p>
    </div>

        @if(isset($acumulado))
    <div class="summary-grid" style="margin-bottom: 8px;">
        <div class="card" style="border-color: #90cdf4;">
            <div class="card-label">🔧 Mantenimientos</div>
            <div class="card-value" style="color: #2b6cb0;">${{ number_format($acumulado['facturado_mant'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card" style="border-color: #d6bcfa;">
            <div class="card-label">⚡ Electrónica</div>
            <div class="card-value" style="color: #553c9a;">${{ number_format($acumulado['facturado_elec'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card" style="border-color: #fbd38d;">
            <div class="card-label">📦 Compras</div>
            <div class="card-value" style="color: #7b341e;">${{ number_format($acumulado['compras_inventario'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card" style="border-color: #9ae6b4;">
            <div class="card-label">🛒 Ventas</div>
            <div class="card-value" style="color: #276749;">${{ number_format($acumulado['ventas_inventario'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="summary-grid">
        <div class="card" style="border-color: #9ae6b4;">
            <div class="card-label">💵 Ingresos Reales</div>
            <div class="card-value" style="color: #276749;">${{ number_format($acumulado['ingresos_caja'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card" style="border-color: #feb2b2;">
            <div class="card-label">💸 Egresos Reales</div>
            <div class="card-value" style="color: #9b2c2c;">${{ number_format($acumulado['egresos_caja'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card" style="border-color: {{ ($acumulado['balance_neto'] ?? 0) >= 0 ? '#4fd1c5' : '#fbd38d' }};">
            <div class="card-label">⚖️ Balance Neto</div>
            <div class="card-value" style="color: {{ ($acumulado['balance_neto'] ?? 0) >= 0 ? '#234e52' : '#7b341e' }};">${{ number_format($acumulado['balance_neto'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
    @endif

        <p class="section-title">Resumen Consolidado del Período</p>

    <table>
        <thead>
            <tr>
                <th style="width:40%; text-align:left">Categoría</th>
                <th style="width:30%; text-align:center">Cantidad de Movimientos</th>
                <th style="width:30%; text-align:center">Costo Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>🔧 Mantenimientos</td>
                <td style="text-align:center">{{ $acumulado['total_mantenimientos'] ?? 0 }}</td>
                <td class="monto positivo" style="text-align:center">${{ number_format($acumulado['facturado_mant'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>⚡ Electrónica</td>
                <td style="text-align:center">{{ $acumulado['total_electronicas'] ?? 0 }}</td>
                <td class="monto positivo" style="text-align:center">${{ number_format($acumulado['facturado_elec'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>📦 Compras de Inventario</td>
                <td style="text-align:center">{{ $acumulado['total_compras'] ?? 0 }}</td>
                <td class="monto negativo" style="text-align:center">${{ number_format($acumulado['compras_inventario'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>🛒 Ventas de Inventario</td>
                <td style="text-align:center">{{ $acumulado['total_ventas'] ?? 0 }}</td>
                <td class="monto positivo" style="text-align:center">${{ number_format($acumulado['ventas_inventario'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>📈 Ingresos Reales (Caja)</td>
                <td style="text-align:center">{{ $acumulado['total_ingresos'] ?? 0 }}</td>
                <td class="monto positivo" style="text-align:center">${{ number_format($acumulado['ingresos_caja'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>📉 Egresos Reales (Caja)</td>
                <td style="text-align:center">{{ $acumulado['total_egresos'] ?? 0 }}</td>
                <td class="monto negativo" style="text-align:center">${{ number_format($acumulado['egresos_caja'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr class="anulado">
                <td style="color:#718096">🚫 Movimientos Anulados</td>
                <td style="text-align:center; color:#718096">{{ $acumulado['total_anulados'] ?? 0 }}</td>
                <td style="text-align:center; color:#718096">—</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right">Balance Neto del Período:</td>
                <td style="text-align:center; color: {{ ($acumulado['balance_neto'] ?? 0) >= 0 ? '#276749' : '#9b2c2c' }}; font-weight: bold;">${{ number_format($acumulado['balance_neto'] ?? 0, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Sistema de Control de Mantenimiento &mdash; Informe generado automáticamente
    </div>
</body>
</html>
