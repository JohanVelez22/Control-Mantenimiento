<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bg-gray { background-color: #f9f9f9; }
        .font-bold { font-weight: bold; }
        .text-red { color: red; }
        .text-green { color: green; }
        
        .watermark {
            position: fixed;
            top: 30%;
            left: 20%;
            font-size: 80px;
            color: rgba(255, 0, 0, 0.1);
            transform: rotate(-45deg);
            z-index: -1;
            white-space: nowrap;
        }
    </style>
</head>
<body>

    <h2 class="text-center">Reporte Financiero (Mes {{ $mes }}/{{ $anio }})</h2>
    <p><strong>Fecha de Generación:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>

    <h3>1. Resumen Acumulado</h3>
    <table>
        <thead>
            <tr>
                <th>Ingresos Totales</th>
                <th>Egresos Totales</th>
                <th>Facturación Total</th>
                <th>Utilidad Neta</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-green">${{ number_format($acumulado['ingresos'], 2) }}</td>
                <td class="text-red">${{ number_format($acumulado['egresos'], 2) }}</td>
                <td>${{ number_format($acumulado['facturado_total'], 2) }}</td>
                <td class="font-bold ${{ $acumulado['utilidad_neta'] >= 0 ? 'text-green' : 'text-red' }}">
                    ${{ number_format($acumulado['utilidad_neta'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <h3>2. Desglose por Tipos de Dinero</h3>
    <table>
        <thead>
            <tr>
                <th>Método de Pago</th>
                <th>Ingresos</th>
                <th>Egresos</th>
                <th>Balance Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Efectivo</strong></td>
                <td class="text-green">+${{ number_format($operaciones['ingresos_efectivo'], 2) }}</td>
                <td class="text-red">-${{ number_format($operaciones['egresos_efectivo'], 2) }}</td>
                <td class="font-bold">${{ number_format($operaciones['efectivo'], 2) }}</td>
            </tr>
            <tr class="bg-gray">
                <td><strong>Consignación / Banco</strong></td>
                <td class="text-green">+${{ number_format($operaciones['ingresos_consignacion'], 2) }}</td>
                <td class="text-red">-${{ number_format($operaciones['egresos_consignacion'], 2) }}</td>
                <td class="font-bold">${{ number_format($operaciones['consignacion'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h3>3. Transacciones Detalladas</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto / Descripción</th>
                <th>Persona / Empresa</th>
                <th>Movimiento</th>
                <th>Pago</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaccionesParaExportar as $tx)
                @if($tx->estado === 'anulado')
                    <div class="watermark">ANULADO</div>
                @endif
                <tr>
                    <td>{{ \Carbon\Carbon::parse($tx->fecha)->format('d/m/Y') }}</td>
                    <td>
                        {{ $tx->concepto->nombre ?? 'N/A' }}
                        @if($tx->descripcion)
                            <br><small>{{ $tx->descripcion }}</small>
                        @endif
                    </td>
                    <td>
                        {{ $tx->persona }}
                        @if($tx->empresa)
                            <br><small>{{ $tx->empresa }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ ucfirst($tx->tipo_movimiento) }}</td>
                    <td class="text-center">{{ ucfirst($tx->tipo_pago) }}</td>
                    <td class="text-right {{ $tx->tipo_movimiento == 'ingreso' ? 'text-green' : 'text-red' }}">
                        {{ $tx->tipo_movimiento == 'ingreso' ? '+' : '-' }}${{ number_format($tx->monto, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
