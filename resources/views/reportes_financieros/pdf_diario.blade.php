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
    <title>Informe Financiero</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { margin: 25px 30px; }
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
        .summary-grid { display: table; width: 100%; margin-bottom: 4px; border-spacing: 4px; }
        .summary-grid .card { display: table-cell; width: 25%; border: 1px solid #e2e8f0; border-radius: 4px; padding: 5px 8px; text-align: center; vertical-align: middle; }
        .card .card-label { font-size: 6.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #718096; margin-bottom: 2px; }
        .card .card-value { font-size: 11px; font-weight: 800; }
        .card.ingresos .card-value { color: #000000; }
        .card.egresos  .card-value { color: #000000; }
        .card.mantenimientos .card-value { color: #000000; }
        .card.anulados .card-value { color: #000000; }

        /* Tabla */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 4px;
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
            border: 1px solid #cbd5e0;
        }
        tbody tr td {
            border: 1px solid #cbd5e0;
            padding: 4px 6px;
            vertical-align: middle;
            font-size: 8.5px;
            text-align: center;
        }
        tbody tr:nth-child(even) { background-color: #f7fafc; }
        tbody tr.anulado { opacity: 1.0; }

        td.tipo-badge { text-align: center; }
        .badge {
            font-size: 8.5px;
            font-weight: normal;
            text-transform: uppercase;
        }
        .pill-active  { color: #000000; font-weight: normal; }
        .pill-anulado { color: #000000; font-weight: normal; font-style: italic; }

        td.monto { text-align: center; font-weight: 700; }
        td.monto.positivo { color: #000000; }
        td.monto.negativo { color: #000000; }

        tfoot tr td {
            background: #2d3748;
            color: #ffffff;
            font-weight: 800;
            font-size: 8.5px;
            border: none !important;
            padding: 5px 6px;
        }

        .footer { margin-top: 16px; text-align: right; font-size: 8px; color: #718096; font-style: italic; }
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
            <div style="font-size: 11px; font-weight: bold; color: #1a202c; text-transform: uppercase; margin-bottom: 3px;">INFORME FINANCIERO DIARIO</div>
            @if($empresa->nit)<div><strong>NIT:</strong> {{ $empresa->nit }}</div>@endif
            @if($empresa->telefono)<div><strong>Tel:</strong> {{ $empresa->telefono }}</div>@endif
            @if($empresa->direccion)<div><strong>Dir:</strong> {{ $empresa->direccion }}</div>@endif
        </div>
    </div>

    <div style="font-size: 9px; color: #4a5568; margin-bottom: 6px; padding: 2px 0;">
        @if(isset($fecha))<strong>Período:</strong> {{ $fecha }} &nbsp;|&nbsp; @endif
        <strong>Generado:</strong> {{ date('d/m/Y h:i A') }}
    </div>

    @if(isset($resumen))
    <div class="summary-grid">
        <div class="card ingresos">
            <div class="card-label">Ingresos</div>
            <div class="card-value">${{ number_format($resumen['total_ingresos'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card egresos">
            <div class="card-label">Egresos</div>
            <div class="card-value">${{ number_format($resumen['total_egresos'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card mantenimientos">
            <div class="card-label">Mantenimientos</div>
            <div class="card-value">${{ number_format($resumen['total_mantenimientos'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card anulados">
            <div class="card-label">Anulados</div>
            <div class="card-value">{{ $resumen['total_anulados'] ?? 0 }}</div>
        </div>
    </div>
    @endif

    <p class="section-title">Movimientos Detallados ({{ count($movimientos) }} registros)</p>
    <table>
        <thead>
            <tr>
                <th style="width:12%">Código</th>
                <th style="width:12%">Tipo</th>
                <th style="width:30%">Descripción / Concepto</th>
                <th style="width:12%">Fecha</th>
                <th style="width:12%">Progreso</th>
                <th style="width:12%">Estado</th>
                <th style="width:10%; text-align:center">Costo</th>
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
                <td style="text-align:center; font-weight:bold;">{{ $mov['codigo'] ?? '—' }}</td>
                <td class="tipo-badge">
                    <span class="badge">{{ ucfirst($tipo) }}</span>
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
                                <td class="monto">
                    ${{ number_format($mov['monto'] ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center; padding:20px; color:#718096;">No hay movimientos en este período.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($movimientos) > 0)
        <tfoot>
            <tr>
                @php
                    $neto = collect($movimientos)->where('anulado', false)->whereIn('tipo', ['ingreso','venta','mantenimiento','electronica'])->sum('monto')
                          - collect($movimientos)->where('anulado', false)->whereIn('tipo', ['egreso','compra'])->sum('monto');
                @endphp
                <td colspan="6" style="text-align:left; border:none !important; padding: 5px 6px;">
                    <span style="float:left; font-weight:700; color:#ffffff;">TOTAL: {{ count($movimientos) }} REGISTROS</span>
                    <span style="float:right; font-weight:700; color:#ffffff;">BALANCE NETO DEL DÍA:</span>
                </td>
                <td style="text-align:center; border:none !important; padding: 5px 6px; color:#ffffff; font-weight:800;">
                    ${{ number_format($neto, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
