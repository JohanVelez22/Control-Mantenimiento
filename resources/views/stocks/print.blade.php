<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Producto - {{ $stock->codigo ?? $stock->producto }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #2563EB; }
        .header p { margin: 5px 0; color: #666; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
        .info-box h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 5px; color: #444; }
        .row { margin-bottom: 8px; display: flex; justify-content: space-between; border-bottom: 1px dashed #eee; padding-bottom: 4px; }
        .row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .label { font-weight: bold; color: #666; }
        .value { font-weight: bold; }
        .text-right { text-align: right; }
        .status { padding: 4px 8px; border-radius: 4px; font-weight: bold; }
        .status.active { background: #d1fae5; color: #065f46; }
        .status.inactive { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; margin-top: 50px; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
        
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    
    <div class="header">
        <h1>Ficha de Producto</h1>
        <p>Documento de Control Interno</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>Información General</h3>
            <div class="row"><span class="label">Código:</span> <span class="value">{{ $stock->codigo ?? 'N/A' }}</span></div>
            <div class="row"><span class="label">Producto:</span> <span class="value">{{ $stock->producto }}</span></div>
            <div class="row"><span class="label">Categoría:</span> <span class="value">{{ $stock->categoria ?? 'General' }}</span></div>
            <div class="row"><span class="label">Subcategoría:</span> <span class="value">{{ $stock->subcategoria ?? 'N/A' }}</span></div>
            <div class="row"><span class="label">Existencias:</span> <span class="value" style="font-size: 16px;">{{ $stock->cantidad }} uds.</span></div>
            <div class="row"><span class="label">Estado:</span> 
                <span class="status {{ $stock->active ? 'active' : 'inactive' }}">
                    {{ $stock->active ? 'ACTIVO' : 'INACTIVO' }}
                </span>
            </div>
        </div>

        <div class="info-box">
            <h3>Estructura Financiera</h3>
            <div class="row"><span class="label">Costo de Compra:</span> <span class="value text-right">${{ number_format($stock->precio_compra, 0, ',', '.') }}</span></div>
            <div class="row"><span class="label">Precio a Técnico:</span> <span class="value text-right" style="color: #6b21a8;">${{ number_format($stock->precio_tecnico, 0, ',', '.') }}</span></div>
            <div class="row"><span class="label">Precio Venta Público:</span> <span class="value text-right" style="color: #065f46; font-size: 14px;">${{ number_format($stock->precio_venta, 0, ',', '.') }}</span></div>
            
            <h3 style="margin-top: 20px;">Proveedor Predeterminado</h3>
            <div class="row"><span class="label">Razón Social:</span> <span class="value">{{ $stock->proveedor->nombre_razon_social ?? '—' }}</span></div>
            <div class="row"><span class="label">Identificación (NIT):</span> <span class="value">{{ $stock->proveedor->identificacion ?? 'N/A' }}</span></div>
        </div>
    </div>

    <div class="footer">
        Generado el {{ date('d/m/Y H:i:s') }} - Sistema Tecni Systemas
    </div>
</body>
</html>
