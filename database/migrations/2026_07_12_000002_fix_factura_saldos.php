<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría 2026-07-12 — Saldos de Factura (debe / pagó de más).
 *
 * El saldo pendiente original era `total_documento - total_pagado`, lo que
 * quedaba NEGATIVO si el cliente/proveedor pagaba de más. Ahora:
 *   - saldo_pendiente = GREATEST(0, total_documento - total_pagado)
 *     (lo que aún se debe; nunca negativo)
 *   - saldo_a_favor   = GREATEST(0, total_pagado - total_documento)
 *     (lo que se pagó de más / a favor del cliente)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('saldo_pendiente');
            $table->decimal('saldo_pendiente', 12, 2)
                  ->storedAs('GREATEST(0, total_documento - total_pagado)')
                  ->after('total_pagado');
            $table->decimal('saldo_a_favor', 12, 2)
                  ->storedAs('GREATEST(0, total_pagado - total_documento)')
                  ->after('saldo_pendiente');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn(['saldo_pendiente', 'saldo_a_favor']);
            $table->decimal('saldo_pendiente', 12, 2)
                  ->storedAs('total_documento - total_pagado')
                  ->after('total_pagado');
        });
    }
};
