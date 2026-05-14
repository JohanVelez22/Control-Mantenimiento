<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Mantenimientos</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header p { font-size: 12px; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; border: 1px solid #94a3b8; }
        th {
            background-color: #4A5568;
            color: white;
            padding: 6px;
            text-transform: uppercase;
            text-align: center;
            border: 1px solid #404040;
        }
        td {
            border: 1px solid #94a3b8;
            padding: 5px;
            vertical-align: middle;
            text-align: center;
        }
        .marca-modelo { font-size: 9px; color: #666; }

        .footer {
            margin-top: 20px;
            text-align: right;
            font-style: italic;
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE MANTENIMIENTOS</h1>
        <p>Generado el: {{ date('d/m/Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Orden</th>
                <th>Cliente</th>
                <th>Equipo / Info</th>
                <th>Técnico</th>
                <th>Tipo</th>
                <th>Costo</th>
                <th>Entrada</th>
                <th>Salida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mantenimientos as $m)
            <tr>
                <td><strong>{{ $m->id_orden }}</strong></td>
                <td>
                    <div>{{ $m->equipo->cliente->nombre ?? 'N/A' }}</div>
                    <div style="font-size: 8px; color: #666;">{{ $m->equipo->cliente->identificacion ?? '-' }}</div>
                </td>
                <td>
                    <div>{{ $m->equipo->nombre ?? 'N/A' }}</div>
                    <div class="marca-modelo">({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }}) - {{ $m->equipo->serie ?? '' }}</div>
                </td>
                <td>{{ $m->tecnico->nombre ?? 'N/A' }}</td>
                <td>
                    <div>{{ ucfirst($m->tipo) }}</div>
                    <div style="font-size: 8px; color: #666; font-style: italic;">({{ ucfirst($m->reparacion) }})</div>
                </td>
                <td>${{ number_format($m->costo, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($m->fecha_entrada)->format('d/m/Y') }}</td>
                <td>{{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'Pendiente' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total registros: {{ count($mantenimientos) }} | Costo Total: ${{ number_format($mantenimientos->sum('costo'), 2) }}</p>
    </div>
</body>
</html>
