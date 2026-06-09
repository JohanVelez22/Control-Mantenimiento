<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante #{{ $movimiento->id }} — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            background: #fff;
            padding: 0;
        }

        /* ── Pantalla: preview bonito ─────────────────── */
        @media screen {
            body { background: #f3f4f6; padding: 2rem; }
            .ticket {
                max-width: 420px;
                margin: 0 auto;
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,.15);
                overflow: hidden;
            }
            .no-print { display: flex; justify-content: center; gap: 1rem; padding: 1.5rem; background: #f9fafb; }
        }

        /* ── Impresión: comprobante limpio ────────────── */
        @media print {
            body { padding: 0; background: #fff; }
            .ticket { box-shadow: none; border-radius: 0; max-width: 100%; }
            .no-print { display: none !important; }
            @page { margin: 10mm; size: A5; }
        }

        /* ── Ticket header ────────────────────────────── */
        .ticket-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            color: #fff;
            padding: 1.5rem 1.5rem 1rem;
            text-align: center;
        }
        .ticket-header .company { font-size: 1.1rem; font-weight: 800; letter-spacing: .5px; }
        .ticket-header .subtitle { font-size: .75rem; opacity: .8; margin-top: 2px; }
        .ticket-header .voucher-type {
            display: inline-block;
            margin-top: .75rem;
            padding: .3rem 1rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: .85rem;
            letter-spacing: .5px;
        }
        .ingreso-badge { background: rgba(34,197,94,.25); color: #bbf7d0; border: 1px solid rgba(34,197,94,.4); }
        .egreso-badge  { background: rgba(239,68,68,.25);  color: #fecaca;  border: 1px solid rgba(239,68,68,.4); }

        /* ── Ticket body ──────────────────────────────── */
        .ticket-body { padding: 1.25rem 1.5rem; }

        .amount-box {
            text-align: center;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
        }
        .amount-box.ingreso { background: #f0fdf4; border: 2px solid #86efac; }
        .amount-box.egreso  { background: #fef2f2; border: 2px solid #fca5a5; }
        .amount-label { font-size: .7rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
        .amount-value { font-size: 2rem; font-weight: 900; margin-top: .1rem; }
        .ingreso .amount-value { color: #16a34a; }
        .egreso  .amount-value { color: #dc2626; }

        .info-grid { display: grid; grid-template-columns: auto 1fr; gap: .45rem 1rem; }
        .info-label { font-weight: 700; color: #6b7280; white-space: nowrap; }
        .info-value { color: #111827; word-break: break-word; }

        .divider {
            border: none;
            border-top: 1px dashed #d1d5db;
            margin: 1rem 0;
        }

        .pago-badge {
            display: inline-block;
            padding: .2rem .75rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: .78rem;
        }
        .efectivo    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .consignacion { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }

        /* ── Footer ───────────────────────────────────── */
        .ticket-footer {
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: .75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: .7rem;
            color: #9ca3af;
        }

        /* ── Botones pantalla ─────────────────────────── */
        .btn {
            padding: .6rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: .85rem;
            border: none;
            transition: all .2s;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back  { background: #e5e7eb; color: #374151; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-back:hover { background: #d1d5db; }
    </style>
</head>
<body>

<div class="ticket">
    {{-- Header --}}
    <div class="ticket-header">
        <div class="company">⚙️ {{ config('app.name') }}</div>
        <div class="subtitle">Sistema de Control de Mantenimiento</div>
        <div class="voucher-type {{ $movimiento->tipo_movimiento === 'ingreso' ? 'ingreso-badge' : 'egreso-badge' }}">
            {{ $movimiento->tipo_movimiento === 'ingreso' ? '📈 COMPROBANTE DE INGRESO' : '📉 COMPROBANTE DE EGRESO' }}
        </div>
    </div>

    {{-- Body --}}
    <div class="ticket-body">

        {{-- Monto destacado --}}
        <div class="amount-box {{ $movimiento->tipo_movimiento }}">
            <div class="amount-label">Valor del Movimiento</div>
            <div class="amount-value">${{ number_format($movimiento->monto, 0, ',', '.') }}</div>
        </div>

        {{-- Información --}}
        <div class="info-grid">
            <span class="info-label">N° Comprobante:</span>
            <span class="info-value">#{{ str_pad($movimiento->id, 6, '0', STR_PAD_LEFT) }}</span>

            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $movimiento->fecha->format('d/m/Y') }}</span>

            <span class="info-label">Persona:</span>
            <span class="info-value">{{ $movimiento->persona }}</span>

            @if($movimiento->empresa)
            <span class="info-label">Empresa:</span>
            <span class="info-value">{{ $movimiento->empresa }}</span>
            @endif

            <span class="info-label">Concepto:</span>
            <span class="info-value">{{ $movimiento->concepto->nombre }}</span>

            <span class="info-label">Forma de Pago:</span>
            <span class="info-value">
                <span class="pago-badge {{ $movimiento->tipo_pago }}">
                    {{ $movimiento->tipo_pago === 'efectivo' ? '💵 Efectivo' : '🏦 Consignación' }}
                </span>
            </span>

            @if($movimiento->descripcion)
            <span class="info-label">Descripción:</span>
            <span class="info-value">{{ $movimiento->descripcion }}</span>
            @endif
        </div>

        <hr class="divider">

        <div class="info-grid">
            <span class="info-label">Registrado por:</span>
            <span class="info-value">{{ $movimiento->user->name }}</span>
            <span class="info-label">Fecha registro:</span>
            <span class="info-value">{{ $movimiento->created_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    {{-- Footer --}}
    <div class="ticket-footer">
        <span>Documento generado automáticamente</span>
        <span>{{ now()->format('d/m/Y H:i') }}</span>
    </div>

    {{-- Botones (solo pantalla) --}}
    <div class="no-print">
        <a href="{{ route('caja.index') }}" class="btn btn-back">⬅️ Volver</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir</button>
    </div>
</div>

<script>
    // Auto-imprimir si viene de guardar
    @if(request()->has('auto_print'))
        window.addEventListener('load', () => setTimeout(() => window.print(), 500));
    @endif
</script>
</body>
</html>
