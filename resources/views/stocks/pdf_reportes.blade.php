<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario – Tecni Systemas</title>
    <style>
        @page {
            size: A4 landscape;
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
            border-bottom: 3px solid #1e3a5f;
        }
        .header-left  { display: table-cell; vertical-align: middle; width: 70%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 30%; }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #1e3a5f;
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
            background: #1e3a5f;
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
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
        .summary-label { font-size: 6.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; }
        .summary-value { font-size: 11px; font-weight: 800; color: #1e3a5f; }
        .summary-divider {
            display: table-cell;
            width: 1px;
            background: #cbd5e1;
            padding: 0;
        }

        /* ─── TABLE ─── */
        table { width: 100%; border-collapse: collapse; }

        thead tr th {
            background: #1e3a5f;
            color: #fff;
            padding: 5px 4px;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            border: 1px solid #152d4a;
        }
        thead tr th:first-child { border-radius: 0; }

        tbody tr td {
            border: 1px solid #dde3ea;
            padding: 4px 4px;
            vertical-align: middle;
            font-size: 7.8px;
            color: #1e293b;
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
        .col-left   { text-align: left; }
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
        .badge-activo     { background:#dcfce7; color:#166534; border-color:#86efac; }
        .badge-anulado    { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }

        .sub-text { font-size: 7px; color: #64748b; margin-top: 1px; }

        /* ─── FOOTER TABLE ─── */
        tfoot tr td {
            background: #1e3a5f;
            color: #fff;
            font-weight: 700;
            font-size: 8px;
            border: 1px solid #152d4a;
            padding: 5px 4px;
        }

        .monto-cell { color: #16a34a; font-weight: 700; }
        .monto-venta { color: #60a5fa; font-weight: 700; }

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
            <div class="company-name">⚙ Tecni Systemas</div>
            <div class="report-title">Reporte Detallado de Inventario</div>
            <div class="header-meta">
                Generado el {{ \Carbon\Carbon::now()->isoFormat('dddd D [de] MMMM [de] YYYY, h:mm A') }}
            </div>
        </div>
        <div class="header-right">
            <div class="header-badge">INVENTARIO</div>
            <div class="header-count">{{ count($stocks) }} registro(s) encontrados</div>
        </div>
    </div>

    {{-- ── BARRA DE RESUMEN ── --}}
    @php
        $totalCompra   = $stocks->sum('precio_compra');
        $totalVenta    = $stocks->sum('precio_venta');
        $totalCantidad = $stocks->sum('cantidad');
        $totalActivos  = $stocks->where('active', true)->count();
        $totalInactivos= $stocks->where('active', false)->count();
    @endphp
    <div class="summary-bar">
        <div class="summary-item">
            <div class="summary-label">Total Productos</div>
            <div class="summary-value">{{ count($stocks) }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Activos</div>
            <div class="summary-value" style="color:#166534">{{ $totalActivos }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Inactivos</div>
            <div class="summary-value" style="color:#991b1b">{{ $totalInactivos }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Cant. Total</div>
            <div class="summary-value">{{ $totalCantidad }}</div>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
            <div class="summary-label">Valor Inv. (Compra)</div>
            <div class="summary-value" style="color:#166534; font-size:9px">${{ number_format($totalCompra, 2) }}</div>
        </div>
    </div>

    {{-- ── TABLA ── --}}
    <table>
        <thead>
            <tr>
                <th style="width:10%">Código</th>
                <th style="width:25%">Producto</th>
                <th style="width:15%">Proveedor</th>
                <th style="width:10%">Categoría</th>
                <th style="width:8%">Cant.</th>
                <th style="width:10%; text-align:right">P. Compra</th>
                <th style="width:7%">Util.</th>
                <th style="width:10%; text-align:right">P. Venta</th>
                <th style="width:5%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stocks as $stock)
            @php
                $isAnulado = !$stock->active;
            @endphp
            <tr class="{{ $isAnulado ? 'anulado' : '' }}">
                <td class="col-center col-bold">{{ $stock->codigo ?? '-' }}</td>
                <td>
                    <div class="col-bold">{{ $stock->producto }}</div>
                    @if($stock->subcategoria)
                    <div class="sub-text">{{ $stock->subcategoria }}</div>
                    @endif
                </td>
                <td class="col-center">{{ $stock->proveedor->nombre_razon_social ?? $stock->proveedor ?? 'N/A' }}</td>
                <td class="col-center">{{ $stock->categoria ?? '-' }}</td>
                <td class="col-center">{{ $stock->cantidad }}</td>
                <td class="col-right monto-cell">${{ number_format($stock->precio_compra, 2) }}</td>
                <td class="col-center" style="font-size:7.5px">+{{ number_format($stock->utilidad, 0) }}%</td>
                <td class="col-right monto-venta">${{ number_format($stock->precio_venta, 2) }}</td>
                <td class="col-center">
                    <span class="badge {{ $isAnulado ? 'badge-anulado' : 'badge-activo' }}">
                        {{ $isAnulado ? 'Inactivo' : 'Activo' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center; padding:20px; color:#94a3b8; font-style:italic;">
                    No hay registros para mostrar con los filtros aplicados.
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($stocks) > 0)
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right; letter-spacing:0.5px; text-transform:uppercase; font-size:7.5px;">
                    TOTALES:
                </td>
                <td class="col-center" style="font-size:9px; font-weight:800;">
                    {{ $stocks->sum('cantidad') }}
                </td>
                <td style="text-align:right; font-size:9px; font-weight:800; color:#4ade80;">
                    ${{ number_format($stocks->sum('precio_compra'), 2) }}
                </td>
                <td></td>
                <td style="text-align:right; font-size:9px; font-weight:800; color:#93c5fd;">
                    ${{ number_format($stocks->sum('precio_venta'), 2) }}
                </td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- ── PIE DE DOCUMENTO ── --}}
    <div class="doc-footer">
        <div class="footer-left">
            Sistema de Control de Inventario &mdash; Reporte generado automáticamente. Documento de uso interno.
        </div>
        <div class="footer-right">
            Tecni Systemas &copy; {{ date('Y') }}
        </div>
    </div>

</body>
</html>
