<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Stock;
use App\Models\MovimientoCaja;

class FixVt3 extends Command
{
    protected $signature = 'fix:vt3';
    protected $description = 'Fix VT-3 and restore stock prices';

    public function handle()
    {
        // 1. Restore Stock ID 1 (Disco Duro) to correct price (was wrongly changed to 50000)
        $stock1 = Stock::find(1);
        if ($stock1) {
            $this->info("Stock 1: {$stock1->producto} | Venta actual: {$stock1->precio_venta}");
            if ((float)$stock1->precio_venta == 50000 && strtolower($stock1->producto) !== 'disco mecanico') {
                $stock1->precio_venta = 130000;
                $stock1->save();
                $this->warn("  -> Restaurado precio_venta de Disco Duro a 130000");
            }
        }

        // 2. Fix factura VT-3
        $factura = Factura::where('numero_factura', 'VT-3')->with('items')->first();
        if (!$factura) {
            $this->error('VT-3 no encontrada');
            return 1;
        }

        $this->info("VT-3 Total Doc: {$factura->total_documento}");

        foreach ($factura->items as $item) {
            $this->line("  Item {$item->id}: {$item->descripcion} | stock_id={$item->stock_id} | precio={$item->precio_unitario}");
            if ((float)$item->precio_unitario >= 1000000) {
                $item->precio_unitario = 50000;
                $item->save();
                $this->warn("    -> Precio corregido a 50000");
            }
        }

        $totalDoc = 0;
        foreach ($factura->items()->get() as $item) {
            $totalDoc += (float)$item->cantidad * (float)$item->precio_unitario;
        }

        $factura->total_documento = $totalDoc;
        $factura->total_pagado = $totalDoc;
        $factura->estado = 'emitida';

        // Clean observations
        $obs = $factura->observaciones ?? '';
        $lines = explode("\n", $obs);
        $clean = [];
        $lastTag = null;
        foreach ($lines as $l) {
            $t = trim($l);
            if (preg_match('/^\[(ANULADA|REACTIVADA)/', $t)) {
                $lastTag = $t;
            } elseif (!str_contains($t, 'SALDO PENDIENTE')) {
                $clean[] = $l;
            }
        }
        if ($lastTag) $clean[] = $lastTag;
        $factura->observaciones = trim(implode("\n", $clean)) ?: null;
        $factura->save();

        $mov = MovimientoCaja::where('descripcion', 'like', "%#{$factura->numero_factura}%")
            ->whereNull('parent_id')->first();
        if ($mov) {
            $mov->update(['monto' => $totalDoc, 'estado' => 'activo', 'anulado' => false]);
        }

        $factura->recalcularPagos();
        $this->info("RESULTADO: Doc={$factura->total_documento} | Pagado={$factura->total_pagado} | Estado={$factura->estado}");
        return 0;
    }
}
