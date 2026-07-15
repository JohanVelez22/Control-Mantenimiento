@php
    $empresa = \App\Models\Configuracion::first() ?? new \App\Models\Configuracion();
    $logoBase64 = null;
    if ($empresa->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresa->logo_path)) {
        
        $type = pathinfo($empresa->logo_path, PATHINFO_EXTENSION);
        $data = \Illuminate\Support\Facades\Storage::disk('public')->get($empresa->logo_path);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Informe Financiero Mensual</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page {
            margin: 25px 30px;
        }
        body { font-family: sans-serif; font-size: 9px; color: #1a202c; background: #fff; margin: 25px 30px !important; }

        .report-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #2d3748;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header-logo-cell {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
        }
        .header-info-cell {
            display: table-cell;
            width: 60%;
            text-align: right;
            vertical-align: middle;
            font-size: 8px;
            color: #4a5568;
            line-height: 1.3;
        }

        /* Tarjetas resumen */
        .summary { display: table; width: 100%; margin-bottom: 4px; }
        .summary-row { display: table-row; }
        .summary-cell { display: table-cell; width: 25%; padding: 4px 6px; text-align: center; vertical-align: middle; border: 1px solid #e2e8f0; border-radius: 4px; }
        .summary-label { font-size: 6.5px; font-weight: 700; text-transform: uppercase; color: #718096; margin-bottom: 2px; }
        .summary-value { font-size: 11px; font-weight: 800; }
        .green { color: #000000; }
        .red   { color: #000000; }
        .blue  { color: #000000; }

        .section-title {
            font-size: 10px; font-weight: 700; color: #2d3748;
            margin: 8px 0 4px 0;
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
            text-align: center; border: 1px solid #cbd5e0;
        }
        tbody tr td {
            border: 1px solid #cbd5e0; padding: 4px 6px;
            vertical-align: middle; font-size: 8.5px;
        }
        tbody tr:nth-child(even) { background-color: #f7fafc; }
        tbody tr.anulado { opacity: 0.55; font-style: italic; }

        td.center { text-align: center; }
        td.right  { text-align: right; }
        td.ingreso { color: #000000; font-weight: 700; }
        td.egreso  { color: #000000; font-weight: 700; }

        tfoot tr td {
            background: #edf2f7; font-weight: 800; font-size: 8.5px;
            border: 1px solid #cbd5e0; padding: 4px 6px;
        }
        .footer { margin-top: 14px; text-align: right; font-size: 7.5px; color: #718096; font-style: italic; }
    </style>
</head>
<body>
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $size = 7.5;
            $color = array(0.44, 0.50, 0.59); // rgb(113, 128, 150)
            $y = $pdf->get_height() - 24;
            $x = $pdf->get_width() - 85;
            $pdf->page_text($x, $y, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, $size, $color);
        }
    </script>
    <div class="report-header">
        <div class="header-logo-cell">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; max-width: 160px; object-fit: contain;">
            @else
                <span style="font-size: 14px; font-weight: bold; color: #1a202c; text-transform: uppercase;">{{ $empresa->nombre }}</span>
            @endif
        </div>
        <div class="header-info-cell">
            <div style="font-size: 11px; font-weight: bold; color: #1a202c; text-transform: uppercase; margin-bottom: 3px;">INFORME FINANCIERO MENSUAL</div>
            @if($empresa->nit)<div><strong>NIT:</strong> {{ $empresa->nit }}</div>@endif
            @if($empresa->telefono)<div><strong>Tel:</strong> {{ $empresa->telefono }}</div>@endif
            @if($empresa->direccion)<div><strong>Dir:</strong> {{ $empresa->direccion }}</div>@endif
        </div>
    </div>

    <div style="font-size: 9px; color: #4a5568; margin-bottom: 6px; padding: 2px 0;">
        <strong>Período:</strong> {{ $mes }}/{{ $anio }} &nbsp;|&nbsp;
        <strong>Generado:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>

    {{-- 1. Resumen Acumulado --}}
    <p class="section-title">1. Resumen Acumulado del Mes</p>
    <div class="summary">
        <div class="summary-row">
            <div class="summary-cell">
                <div class="summary-label">Ingresos Totales</div>
                <div class="summary-value green">${{ number_format($acumulado['ingresos'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">Egresos Totales</div>
                <div class="summary-value red">${{ number_format($acumulado['egresos'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">Facturación Total</div>
                <div class="summary-value blue">${{ number_format($acumulado['facturado_total'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-cell">
                <div class="summary-label">Utilidad Neta</div>
                <div class="summary-value green">${{ number_format($acumulado['utilidad_neta'], 0, ',', '.') }}</div>
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
                <td><strong>Efectivo</strong></td>
                <td class="ingreso">${{ number_format($operaciones['ingresos_efectivo'], 0, ',', '.') }}</td>
                <td class="egreso">${{ number_format($operaciones['egresos_efectivo'], 0, ',', '.') }}</td>
                <td class="right">${{ number_format($operaciones['efectivo'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Consignación / Banco</strong></td>
                <td class="ingreso">${{ number_format($operaciones['ingresos_consignacion'], 0, ',', '.') }}</td>
                <td class="egreso">${{ number_format($operaciones['egresos_consignacion'], 0, ',', '.') }}</td>
                <td class="right">${{ number_format($operaciones['consignacion'], 0, ',', '.') }}</td>
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
                <td class="right">
                    ${{ number_format($tx->monto, 0, ',', '.') }}
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
                <td style="text-align:right; color:#000000;">${{ number_format($acumulado['ingresos'], 0, ',', '.') }} / ${{ number_format($acumulado['egresos'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
