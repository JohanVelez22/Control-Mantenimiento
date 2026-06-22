<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Financiero Mensual</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 9px; color: #1a202c; background: #fff; }

        .header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 3px solid #2d3748;
        }
        .header h1 { font-size: 16px; font-weight: 700; color: #1a202c; letter-spacing: 1px; text-transform: uppercase; }
        .header p  { font-size: 9px; color: #4a5568; margin-top: 4px; }

        /* Tarjetas resumen */
        .summary { display: table; width: 100%; margin-bottom: 16px; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; width: 25%; padding: 6px 8px; text-align: center; vertical-align: middle; border: 1.5px solid #e2e8f0; border-radius: 4px; }
        .summary-label { font-size: 7px; font-weight: 700; text-transform: uppercase; color: #718096; margin-bottom: 3px; }
        .summary-value { font-size: 13px; font-weight: 800; }
        .green { color: #276749; }
        .red   { color: #9b2c2c; }
        .blue  { color: #1a365d; }

        .section-title {
            font-size: 10px; font-weight: 700; color: #2d3748;
            margin: 14px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr th {
            background-color: #2d3748; color: #fff;
            padding: 5px 6px;
            text-transform: uppercase; font-size: 7px; letter-spacing: 0.4px;
            text-align: center; border: 1px solid #1a202c;
        }
        tbody tr td {
            border: 1px solid #cbd5e0; padding: 4px 6px;
            vertical-align: middle; font-size: 8.5px;
        }
        tbody tr:nth-child(even) { background-color: #f7fafc; }
        tbody tr.anulado { opacity: 0.55; font-style: italic; }

        td.center { text-align: center; }
        td.right  { text-align: right; }
        td.ingreso { color: #276749; font-weight: 700; }
        td.egreso  { color: #9b2c2c; font-weight: 700; }

        tfoot tr td {
            background: #edf2f7; font-weight: 800; font-size: 9px;
            border: 1px solid #a0aec0; padding: 4px 6px;
        }
        .footer { margin-top: 14px; text-align: right; font-size: 7.5px; color: #718096; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💵 Informe Financiero Mensual</h1>
        <p>Período: {{ $mes }}/{{ $anio }} &nbsp;|&nbsp; Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- 1. Resumen Acumulado --}}
    <p class="section-title">1. Resumen Acumulado del Mes</p>
    <div class="summary">
        <div class="summary-row">
            <div class="summary-cell">
                <div class="summary-label">📈 Ingresos Totales</div>
                <div class="summary-value green">${{ number_format($acumulado['ingresos'], 2) }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">📉 Egresos Totales</div>
                <div class="summary-value red">${{ number_format($acumulado['egresos'], 2) }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">🏭 Facturación Total</div>
                <div class="summary-value blue">${{ number_format($acumulado['facturado_total'], 2) }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">💰 Utilidad Neta</div>
                <div class="summary-value {{ $acumulado['utilidad_neta'] >= 0 ? 'green' : 'red' }}">${{ number_format($acumulado['utilidad_neta'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- 2. Desglose por Método de Pago --}}
    <p class="section-title">2. Desglose por Método de Pago</p>
    <table>
        <thead>
            <tr>
                <th>Método de Pago</th>
                <th>Ingresos</th>
                <th>Egresos</th>
                <th style="text-align:right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>💵 Efectivo</strong></td>
                <td class="ingreso">+${{ number_format($operaciones['ingresos_efectivo'], 2) }}</td>
                <td class="egreso">-${{ number_format($operaciones['egresos_efectivo'], 2) }}</td>
                <td class="right {{ $operaciones['efectivo'] >= 0 ? 'ingreso' : 'egreso' }}">${{ number_format($operaciones['efectivo'], 2) }}</td>
            </tr>
            <tr>
                <td><strong>🏦 Consignación / Banco</strong></td>
                <td class="ingreso">+${{ number_format($operaciones['ingresos_consignacion'], 2) }}</td>
                <td class="egreso">-${{ number_format($operaciones['egresos_consignacion'], 2) }}</td>
                <td class="right {{ $operaciones['consignacion'] >= 0 ? 'ingreso' : 'egreso' }}">${{ number_format($operaciones['consignacion'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- 3. Transacciones Detalladas --}}
    <p class="section-title">3. Transacciones Detalladas ({{ count($transaccionesParaExportar) }} registros)</p>
    <table>
        <thead>
            <tr>
                <th style="width:11%">Fecha</th>
                <th style="width:22%">Concepto / Descripción</th>
                <th style="width:20%">Persona / Empresa</th>
                <th style="width:12%">Movimiento</th>
                <th style="width:12%">Pago</th>
                <th style="width:13%; text-align:right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transaccionesParaExportar as $tx)
            <tr class="{{ $tx->anulado ? 'anulado' : '' }}">
                <td class="center">{{ \Carbon\Carbon::parse($tx->fecha)->format('d/m/Y') }}</td>
                <td>
                    {{ $tx->concepto->nombre ?? 'N/A' }}
                    @if($tx->descripcion)
                    <br><small style="color:#718096">{{ $tx->descripcion }}</small>
                    @endif
                </td>
                <td>
                    {{ $tx->persona ?? '' }}
                    @if($tx->empresa)
                    <br><small style="color:#718096">{{ $tx->empresa }}</small>
                    @endif
                </td>
                <td class="center">{{ ucfirst($tx->tipo_movimiento) }}</td>
                <td class="center">{{ ucfirst($tx->tipo_pago) }}</td>
                <td class="right {{ $tx->tipo_movimiento == 'ingreso' ? 'ingreso' : 'egreso' }}">
                    {{ $tx->tipo_movimiento == 'ingreso' ? '+' : '-' }}${{ number_format($tx->monto, 2) }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center; padding:16px; color:#718096;">No hay transacciones en este período.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($transaccionesParaExportar) > 0)
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right; text-transform:uppercase; font-size:8px;">Total ingresos / egresos del período:</td>
                <td style="text-align:right; color:#276749">+${{ number_format($acumulado['ingresos'], 2) }} / <span style="color:#9b2c2c">-${{ number_format($acumulado['egresos'], 2) }}</span></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">Sistema de Control de Mantenimiento &mdash; Informe generado automáticamente</div>
</body>
</html>
