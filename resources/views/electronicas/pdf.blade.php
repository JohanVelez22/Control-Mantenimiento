<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Electrónica</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 8.5px; color: #1a202c; background: #fff; }

        .header {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 3px solid #553c9a;
        }
        .header h1 { font-size: 15px; font-weight: 700; color: #1a202c; letter-spacing: 1px; text-transform: uppercase; }
        .header p  { font-size: 9px; color: #4a5568; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr th {
            background-color: #553c9a;
            color: #ffffff;
            padding: 5px 5px;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.4px;
            text-align: center;
            border: 1px solid #44337a;
        }
        tbody tr td {
            border: 1px solid #cbd5e0;
            padding: 4px 5px;
            vertical-align: middle;
            font-size: 8px;
        }
        tbody tr:nth-child(even) { background-color: #faf5ff; }
        tbody tr.anulado { opacity: 0.55; }
        tbody tr.anulado td { font-style: italic; }

        td.center { text-align: center; }
        td.right  { text-align: right; }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 6.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            border: 1px solid;
        }
        .badge-pendiente  { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .badge-en_proceso { background: #dbeafe; color: #1e40af; border-color: #bfdbfe; }
        .badge-reparado   { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
        .badge-terminado  { background: #a7f3d0; color: #065f46; border-color: #6ee7b7; }
        .badge-entregado  { background: #c6f6d5; color: #276749; border-color: #9ae6b4; }
        .badge-activo     { background: #c6f6d5; color: #276749; border-color: #9ae6b4; }
        .badge-anulado    { background: #fed7d7; color: #9b2c2c; border-color: #feb2b2; }

        /* Tipo badges electrónica */
        .badge-correctivo    { background: #e9d8fd; color: #553c9a; border-color: #d6bcfa; }
        .badge-preventivo    { background: #bee3f8; color: #2b6cb0; border-color: #90cdf4; }
        .badge-diagnostico   { background: #fefcbf; color: #7b6200; border-color: #f6e05e; }
        .badge-instalacion   { background: #c6f6d5; color: #276749; border-color: #9ae6b4; }

        .equipo-sub { font-size: 7px; color: #718096; margin-top: 1px; }

        tfoot tr td {
            background: #f3ebff;
            font-weight: 800;
            font-size: 8.5px;
            border: 1px solid #b794f4;
            padding: 4px 5px;
        }

        .monto { color: #553c9a; font-weight: 700; }
        .footer { margin-top: 14px; text-align: right; font-size: 7.5px; color: #718096; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚡ Reporte de Módulo Electrónica</h1>
        <p>Generado el: {{ date('d/m/Y h:i A') }} &nbsp;|&nbsp; Total de registros: {{ count($electronicas) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:7%">Orden</th>
                <th style="width:13%">Cliente</th>
                <th style="width:17%">Equipo / Info</th>
                <th style="width:11%">Técnico</th>
                <th style="width:9%">Tipo</th>
                <th style="width:9%">Progreso</th>
                <th style="width:8%">Estado</th>
                <th style="width:8%">Entrada</th>
                <th style="width:8%">Salida</th>
                <th style="width:9%; text-align:right">Costo</th>
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
                <td class="center"><strong>{{ $e->id_orden }}</strong></td>
                <td>
                    <div style="font-weight:700">{{ $e->equipo->cliente->nombre ?? 'N/A' }}</div>
                    <div class="equipo-sub">{{ $e->equipo->cliente->identificacion ?? '-' }}</div>
                </td>
                <td>
                    <div style="font-weight:700">{{ $e->equipo->nombre ?? 'N/A' }}</div>
                    <div class="equipo-sub">{{ $e->equipo->marca ?? '' }} {{ $e->equipo->modelo ?? '' }} &mdash; {{ $e->equipo->serie ?? '' }}</div>
                </td>
                <td class="center">{{ $e->tecnico->nombre ?? 'N/A' }}</td>
                <td class="center">
                    <span class="badge badge-{{ $tipo ?: 'correctivo' }}">{{ ucfirst($tipo) ?: '—' }}</span>
                </td>
                <td class="center">
                    <span class="badge badge-{{ $progreso }}">{{ ucfirst($progreso) ?: '—' }}</span>
                </td>
                <td class="center">
                    <span class="badge {{ $isAnulado ? 'badge-anulado' : 'badge-activo' }}">
                        {{ $isAnulado ? 'Anulado' : 'Activo' }}
                    </span>
                </td>
                <td class="center">{{ \Carbon\Carbon::parse($e->fecha_entrada)->format('d/m/Y') }}</td>
                <td class="center">{{ $e->fecha_salida ? \Carbon\Carbon::parse($e->fecha_salida)->format('d/m/Y') : '—' }}</td>
                <td class="right monto">${{ number_format($e->costo, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align:center; padding:20px; color:#718096;">No hay registros para mostrar.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($electronicas) > 0)
        <tfoot>
            <tr>
                <td colspan="9" style="text-align:right; font-size:8px; text-transform:uppercase;">Total {{ count($electronicas) }} registros — Costo acumulado:</td>
                <td style="text-align:right; color:#553c9a">${{ number_format($electronicas->sum('costo'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">Sistema de Control de Mantenimiento &mdash; Reporte generado automáticamente</div>
</body>
</html>
