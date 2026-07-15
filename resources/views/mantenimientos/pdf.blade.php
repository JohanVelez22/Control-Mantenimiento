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
    <title>Reporte de Mantenimientos – {{ $empresa->nombre }}</title>
    <style>
        @page {
            size: A4 {{ $orientation ?? 'landscape' }};
            margin: 20px 25px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: sans-serif;
            font-size: 8.2px;
            color: #000000;
            background: #fff;
            line-height: 1.35;
            margin: 20px 25px !important;
        }

        /* ─── HEADER ─── */
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
            color: #000000;
            line-height: 1.3;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 11px;
            font-weight: 600;
            color: #000000;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }
        .header-meta {
            font-size: 7.5px;
            color: #000000;
            margin-top: 5px;
        }
        .header-badge {
            display: inline-block;
            background: #2d3748;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 0px;
            letter-spacing: 0.5px;
        }
        .header-count {
            font-size: 9px;
            color: #000000;
            margin-top: 4px;
        }

        /* ─── SUMMARY ROW ─── */
        .summary-bar {
            display: table;
            width: 100%;
            margin-bottom: 6px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 0px;
            padding: 6px 10px;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
        .summary-label { font-size: 6.5px; color: #000000; text-transform: uppercase; letter-spacing: 0.4px; }
        .summary-value { font-size: 11px; font-weight: 800; color: #000000; }
        .summary-divider {
            display: table-cell;
            width: 1px;
            background: #cbd5e1;
            padding: 0;
        }

        /* ─── TABLE ─── */
        table { width: 100%; border-collapse: collapse; }

        thead tr th {
            background: #2d3748;
            color: #fff;
            padding: 5px 4px;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #cbd5e0;
        }
        thead tr th:first-child { border-radius: 0; }

        tbody tr td {
            border: 1px solid #cbd5e0;
            padding: 4px 4px;
            vertical-align: middle;
            font-size: 7.8px;
            color: #000000;
        }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tbody tr.anulado td {
            opacity: 0.55;
            font-style: italic;
            background: #fff5f5 !important;
        }
        tbody tr { page-break-inside: avoid; }

        .col-center { text-align: center; }
        .col-right  { text-align: right; }
        .col-bold   { font-weight: 700; }

        /* ─── BADGES ─── */
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 0px;
            font-size: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none !important;
            background: transparent !important;
            color: #000000 !important;
            white-space: nowrap;
        }
        .badge-preventivo { background:transparent; color:#000000; }
        .badge-correctivo { background:transparent; color:#000000; }
        .badge-pendiente  { background:transparent; color:#000000; }
        .badge-en_proceso { background:transparent; color:#000000; }
        .badge-reparado   { background:transparent; color:#000000; }
        .badge-terminado  { background:transparent; color:#000000; }
        .badge-entregado  { background:transparent; color:#000000; }
        .badge-activo     { background:transparent; color:#000000; }
        .badge-anulado    { background:transparent; color:#000000; }

        .sub-text { font-size: 7px; color: #000000; margin-top: 1px; }

        /* ─── FOOTER TABLE ─── */
        tfoot tr td {
            background: #2d3748;
            color: #fff;
            font-weight: 700;
            font-size: 7.8px;
            border: none !important;
            padding: 5px 4px;
        }

        .monto-cell { color: #000000; font-weight: 700; }

        /* ─── DOCUMENT FOOTER ─── */
        .doc-footer {
            margin-top: 14px;
            padding-top: 7px;
            border-top: 1px solid #cbd5e0;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; font-size: 7px; color: #000000; font-style: italic; }
        .footer-right { display: table-cell; text-align: right; font-size: 7px; color: #000000; }

        .page-number:before { content: "Página " counter(page) " de " counter(pages); }
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

    {{-- ── ENCABEZADO ── --}}
    <div class="report-header">
        <div class="header-logo-cell">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" style="max-height: 50px; max-width: 160px; object-fit: contain;">
            @else
                <span style="font-size: 14px; font-weight: bold; color: #2d3748; text-transform: uppercase;">{{ $empresa->nombre }}</span>
            @endif
        </div>
        <div class="header-info-cell">
            <div style="font-size: 11px; font-weight: bold; color: #2d3748; text-transform: uppercase; margin-bottom: 3px;">REPORTE DETALLADO DE MANTENIMIENTOS</div>
            @if($empresa->nit)<div><strong>NIT:</strong> {{ $empresa->nit }}</div>@endif
            @if($empresa->telefono)<div><strong>Tel:</strong> {{ $empresa->telefono }}</div>@endif
            @if($empresa->direccion)<div><strong>Dir:</strong> {{ $empresa->direccion }}</div>@endif
        </div>
    </div>

    <div style="font-size: 9px; color: #000000; margin-bottom: 6px; padding: 2px 0;">
        <strong>Generado:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} &nbsp;|&nbsp;
        <strong>Registros:</strong> {{ count($mantenimientos) }}
    </div>

    {{-- ── BARRA DE RESUMEN ── --}}
    @php
        $totalCosto    = $mantenimientos->sum('costo');
        $totalActivos  = $mantenimientos->where('anulado', false)->count();
        $totalAnulados = $mantenimientos->where('anulado', true)->count();
        $totalEntregados = $mantenimientos->where('estado', 'entregado')->count();
    @endphp
    <div class="summary-bar">
        <div class="summary-item">
            <div class="summary-label">Total Órdenes</div>
            <div class="summary-value">{{ count($mantenimientos) }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Activos</div>
            <div class="summary-value" style="color:#000000">{{ $totalActivos }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Anulados</div>
            <div class="summary-value" style="color:#000000">{{ $totalAnulados }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Entregados</div>
            <div class="summary-value" style="color:#000000">{{ $totalEntregados }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Costo Total</div>
            <div class="summary-value" style="color:#000000; font-size:9px">${{ number_format($totalCosto, 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- ── TABLA ── --}}
    <table>
        <thead>
            <tr>
                <th style="width:6%">Orden</th>
                <th style="width:13%">Cliente</th>
                <th style="width:14%">Equipo / Info</th>
                <th style="width:10%">Técnico</th>
                <th style="width:8%">Tipo</th>
                <th style="width:9%">Reparación</th>
                <th style="width:9%">Progreso</th>
                <th style="width:7%">Estado</th>
                <th style="width:8%">Entrada</th>
                <th style="width:8%">Salida</th>
                <th style="width:8%; text-align:right">Costo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mantenimientos as $m)
            @php
                $isAnulado = !empty($m->anulado);
                $progreso  = strtolower($m->estado ?? '');
                $tipo      = strtolower($m->tipo ?? '');
                $reparacion = $m->reparacion ?? '';
            @endphp
            <tr class="{{ $isAnulado ? 'anulado' : '' }}">
                <td class="col-center col-bold">{{ $m->id_orden }}</td>
                <td>
                    <div class="col-bold">{{ $m->equipo->cliente->nombre ?? 'N/A' }}</div>
                    <div class="sub-text">{{ $m->equipo->cliente->identificacion ?? '-' }}</div>
                </td>
                <td>
                    <div class="col-bold">{{ $m->equipo->nombre ?? 'N/A' }}</div>
                    <div class="sub-text">{{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }} &mdash; {{ strtoupper($m->equipo->serie ?? '') }}</div>
                </td>
                <td class="col-center">{{ $m->tecnico->nombre ?? 'N/A' }}</td>
                <td class="col-center">
                    <span class="badge badge-{{ $tipo ?: 'correctivo' }}">{{ ucfirst($tipo) ?: '—' }}</span>
                </td>
                <td class="col-center" style="font-size:7.5px">{{ ucfirst($reparacion) ?: '—' }}</td>
                <td class="col-center">
                    <span class="badge badge-{{ $progreso ?: 'pendiente' }}">{{ ucfirst($progreso) ?: '—' }}</span>
                </td>
                <td class="col-center">
                    <span class="badge {{ $isAnulado ? 'badge-anulado' : 'badge-activo' }}">
                        {{ $isAnulado ? 'Anulado' : 'Activo' }}
                    </span>
                </td>
                <td class="col-center">{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</td>
                <td class="col-center">{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : '—' }}</td>
                <td class="col-right monto-cell">${{ number_format($m->costo, 0, '', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align:center; padding:20px; color:#94a3b8; font-style:italic;">
                    No hay registros para mostrar con los filtros aplicados.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($mantenimientos) > 0)
        <tfoot>
            <tr>
                <td colspan="10" style="text-align:left; letter-spacing:0.5px; text-transform:uppercase; padding: 5px 6px; border:none !important;">
                    <span style="float:left; font-weight:700;">TOTAL: {{ count($mantenimientos) }} REGISTROS</span>
                    <span style="float:right; font-weight:700;">COSTO ACUMULADO:</span>
                </td>
                <td style="text-align:right; font-weight:800; padding: 5px 6px; border:none !important;">
                    ${{ number_format($mantenimientos->sum('costo'), 0, '', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

</body>
</html>
