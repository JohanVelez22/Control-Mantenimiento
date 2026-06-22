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

        td.monto { text-align: right; font-weight: 700; }
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

    @if(isset($resumen))
    <div class="summary-grid">
        <div class="card ingresos">
            <div class="card-label">📈 Ingresos</div>
            <div class="card-value">${{ number_format($resumen['total_ingresos'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="card egresos">
            <div class="card-label">📉 Egresos</div>
            <div class="card-value">${{ number_format($resumen['total_egresos'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="card mantenimientos">
            <div class="card-label">🔧 Mantenimientos</div>
            <div class="card-value">${{ number_format($resumen['total_mantenimientos'] ?? 0, 2, ',', '.') }}</div>
        </div>
        <div class="card anulados">
            <div class="card-label">🚫 Anulados</div>
            <div class="card-value">{{ $resumen['total_anulados'] ?? 0 }}</div>
        </div>
    </div>
    @endif

    <p class="section-title">Movimientos Detallados ({{ count($movimientos) }} registros)</p>

    <table>
        <thead>
            <tr>
                <th style="width:12%">Tipo</th>
                <th style="width:30%">Descripción</th>
                <th style="width:12%">Fecha</th>
                <th style="width:14%">Progreso</th>
                <th style="width:12%">Estado</th>
                <th style="width:10%; text-align:right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
            @php
                $tipo     = $mov['tipo'] ?? 'ingreso';
                $isAnulado = !empty($mov['anulado']);
                $badgeClass = match($tipo) {
                    'mantenimiento' => 'badge-mant',
                    'electronica'   => 'badge-elec',
                    'ingreso'       => 'badge-ingreso',
                    'egreso'        => 'badge-egreso',
                    'venta'         => 'badge-venta',
                    'compra'        => 'badge-compra',
                    default         => 'badge-ingreso',
                };
                $isPositivo = in_array($tipo, ['ingreso','venta','mantenimiento','electronica']);
                $progreso = ucfirst($mov['estado'] ?? '—');
            @endphp
            <tr class="{{ $isAnulado ? 'anulado' : '' }}">
                <td class="tipo-badge">
                    <span class="badge {{ $badgeClass }}">{{ $mov['icono'] ?? '' }} {{ ucfirst($tipo) }}</span>
                </td>
                <td>{{ $mov['descripcion'] ?? '—' }}</td>
                <td style="text-align:center">{{ \Carbon\Carbon::parse($mov['fecha'])->format('d/m/Y') }}</td>
                <td style="text-align:center">{{ $progreso }}</td>
                <td style="text-align:center">
                    @if($isAnulado)
                        <span class="pill-anulado">Anulado</span>
                    @else
                        <span class="pill-active">Activo</span>
                    @endif
                </td>
                <td class="monto {{ $isPositivo ? 'positivo' : 'negativo' }}">
                    {{ $isPositivo ? '+' : '-' }}${{ number_format($mov['monto'] ?? 0, 2, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; padding:20px; color:#718096;">No hay movimientos en este período.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($movimientos) > 0)
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right">Total de {{ count($movimientos) }} registros:</td>
                <td style="text-align:right">${{ number_format(collect($movimientos)->sum('monto'), 2, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        Sistema de Control de Mantenimiento &mdash; Informe generado automáticamente
    </div>
</body>
</html>
