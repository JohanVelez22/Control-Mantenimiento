<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Electrónica – Tecni Systemas</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm 15mm 18mm 15mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.2px;
            color: #1e293b;
            background: #fff;
            line-height: 1.35;
        }

        /* ─── HEADER ─── */
        .report-header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 3px solid #4c1d95;
        }
        .header-left  { display: table-cell; vertical-align: middle; width: 70%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 30%; }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #4c1d95;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }
        .header-meta {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 5px;
        }
        .header-badge {
            display: inline-block;
            background: #4c1d95;
            color: #fff;
            font-size: 8px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .header-count {
            font-size: 9px;
            color: #475569;
            margin-top: 4px;
        }

        /* ─── SUMMARY ROW ─── */
        .summary-bar {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            background: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 4px;
            padding: 6px 10px;
        }
        .summary-item   { display: table-cell; text-align: center; vertical-align: middle; }
        .summary-label  { font-size: 6.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
        .summary-value  { font-size: 11px; font-weight: 800; color: #4c1d95; }
        .summary-divider { display: table-cell; width: 1px; background: #ddd6fe; padding: 0; }

        /* ─── TABLE ─── */
        table { width: 100%; border-collapse: collapse; }

        thead tr th {
            background: #4c1d95;
            color: #fff;
            padding: 5px 4px;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #3b0764;
        }

        tbody tr td {
            border: 1px solid #dde3ea;
            padding: 4px 4px;
            vertical-align: middle;
            font-size: 7.8px;
            color: #1e293b;
        }
        tbody tr:nth-child(even) td { background: #faf7ff; }
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
            border-radius: 3px;
            font-size: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid;
            white-space: nowrap;
        }
        .badge-pendiente   { background:#fef9c3; color:#854d0e; border-color:#fde047; }
        .badge-en_proceso  { background:#dbeafe; color:#1e40af; border-color:#93c5fd; }
        .badge-reparado    { background:#dcfce7; color:#166534; border-color:#86efac; }
        .badge-terminado   { background:#d1fae5; color:#065f46; border-color:#6ee7b7; }
        .badge-entregado   { background:#d1fae5; color:#065f46; border-color:#6ee7b7; }
        .badge-activo      { background:#dcfce7; color:#166534; border-color:#86efac; }
        .badge-anulado     { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
        .badge-correctivo  { background:#ede9fe; color:#4c1d95; border-color:#c4b5fd; }
        .badge-preventivo  { background:#dbeafe; color:#1e40af; border-color:#93c5fd; }
        .badge-diagnostico { background:#fef9c3; color:#854d0e; border-color:#fde047; }
        .badge-instalacion { background:#dcfce7; color:#166534; border-color:#86efac; }

        .sub-text { font-size: 7px; color: #64748b; margin-top: 1px; }

        /* ─── FOOTER TABLE ─── */
        tfoot tr td {
            background: #4c1d95;
            color: #fff;
            font-weight: 700;
            font-size: 8px;
            border: 1px solid #3b0764;
            padding: 5px 4px;
        }

        .monto-cell { color: #4c1d95; font-weight: 700; }

        /* ─── DOCUMENT FOOTER ─── */
        .doc-footer {
            margin-top: 14px;
            padding-top: 7px;
            border-top: 1px solid #e2e8f0;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; font-size: 7px; color: #94a3b8; font-style: italic; }
        .footer-right { display: table-cell; text-align: right; font-size: 7px; color: #94a3b8; }
    </style>
</head>
<body>

    {{-- ── ENCABEZADO ── --}}
    <div class="report-header">
        <div class="header-left">
            <div class="company-name">⚡ Tecni Systemas</div>
            <div class="report-title">Reporte Detallado de Módulo Electrónica</div>
            <div class="header-meta">
                Generado el {{ \Carbon\Carbon::now()->isoFormat('dddd D [de] MMMM [de] YYYY, h:mm A') }}
            </div>
        </div>
        <div class="header-right">
            <div class="header-badge">ELECTRÓNICA</div>
            <div class="header-count">{{ count($electronicas) }} registro(s) encontrados</div>
        </div>
    </div>

    {{-- ── BARRA DE RESUMEN ── --}}
    @php
        $totalCosto    = $electronicas->sum('costo');
        $totalActivos  = $electronicas->where('anulado', false)->count();
        $totalAnulados = $electronicas->where('anulado', true)->count();
        $totalEntregados = $electronicas->where('estado', 'entregado')->count();
    @endphp
    <div class="summary-bar">
        <div class="summary-item">
            <div class="summary-label">Total Órdenes</div>
            <div class="summary-value">{{ count($electronicas) }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Activos</div>
            <div class="summary-value" style="color:#166534">{{ $totalActivos }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Anulados</div>
            <div class="summary-value" style="color:#991b1b">{{ $totalAnulados }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Entregados</div>
            <div class="summary-value" style="color:#0369a1">{{ $totalEntregados }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Costo Total</div>
            <div class="summary-value" style="color:#4c1d95; font-size:9px">${{ number_format($totalCosto, 0, '', '.') }}</div>
        </div>
    </div>

    {{-- ── TABLA ── --}}
    <table>
        <thead>
            <tr>
                <th style="width:6%">Orden</th>
                <th style="width:14%">Cliente</th>
                <th style="width:15%">Equipo / Info</th>
                <th style="width:10%">Técnico</th>
                <th style="width:8%">Tipo</th>
                <th style="width:9%">Progreso</th>
                <th style="width:7%">Estado</th>
                <th style="width:8%">Entrada</th>
                <th style="width:8%">Salida</th>
                <th style="width:8%; text-align:right">Costo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($electronicas as $e)
            @php
                $isAnulado = !empty($e->anulado);
                $progreso  = strtolower($e->estado ?? '');
                $tipo      = strtolower($e->tipo ?? '');
            @endphp
            <tr class="{{ $isAnulado ? 'anulado' : '' }}">
                <td class="col-center col-bold">{{ $e->id_orden }}</td>
                <td>
                    <div class="col-bold">{{ $e->equipo->cliente->nombre ?? 'N/A' }}</div>
                    <div class="sub-text">{{ $e->equipo->cliente->identificacion ?? '-' }}</div>
                </td>
                <td>
                    <div class="col-bold">{{ $e->equipo->nombre ?? 'N/A' }}</div>
                    <div class="sub-text">{{ $e->equipo->marca ?? '' }} {{ $e->equipo->modelo ?? '' }} &mdash; {{ strtoupper($e->equipo->serie ?? '') }}</div>
                </td>
                <td class="col-center">{{ $e->tecnico->nombre ?? 'N/A' }}</td>
                <td class="col-center">
                    <span class="badge badge-{{ $tipo ?: 'correctivo' }}">{{ ucfirst($tipo) ?: '—' }}</span>
                </td>
                <td class="col-center">
                    <span class="badge badge-{{ $progreso ?: 'pendiente' }}">{{ ucfirst($progreso) ?: '—' }}</span>
                </td>
                <td class="col-center">
                    <span class="badge {{ $isAnulado ? 'badge-anulado' : 'badge-activo' }}">
                        {{ $isAnulado ? 'Anulado' : 'Activo' }}
                    </span>
                </td>
                <td class="col-center">{{ \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y') }}</td>
                <td class="col-center">{{ $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : '—' }}</td>
                <td class="col-right monto-cell">${{ number_format($e->costo, 0, '', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:20px; color:#94a3b8; font-style:italic;">
                    No hay registros para mostrar con los filtros aplicados.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($electronicas) > 0)
        <tfoot>
            <tr>
                <td colspan="9" style="text-align:right; letter-spacing:0.5px; text-transform:uppercase; font-size:7.5px;">
                    TOTAL — {{ count($electronicas) }} registros &nbsp;|&nbsp; Costo acumulado:
                </td>
                <td style="text-align:right; font-size:9px; font-weight:800;">
                    ${{ number_format($electronicas->sum('costo'), 0, '', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- ── PIE DE DOCUMENTO ── --}}
    <div class="doc-footer">
        <div class="footer-left">
            Sistema de Control de Mantenimiento &mdash; Reporte generado automáticamente. Documento de uso interno.
        </div>
        <div class="footer-right">
            Tecni Systemas &copy; {{ date('Y') }}
        </div>
    </div>

</body>
</html>
