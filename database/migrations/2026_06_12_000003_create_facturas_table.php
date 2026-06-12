<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de facturas (cabecera)
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura')->unique();
            $table->enum('tipo_movimiento', ['compra', 'venta']);
            $table->enum('estado', ['emitida', 'anulada', 'pendiente_pago'])->default('emitida');

            // Polimórfico: puede ser Cliente o Proveedor
            $table->nullableMorphs('facturable'); // facturable_id, facturable_type

            $table->decimal('total_documento', 12, 2)->default(0);
            $table->decimal('total_pagado',    12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2)->storedAs('total_documento - total_pagado');
            $table->text('observaciones')->nullable();
            $table->date('fecha')->default(now()->toDateString());
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Líneas de la factura (ítems de stock)
        Schema::create('factura_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->restrictOnDelete();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2)->storedAs('cantidad * precio_unitario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_items');
        Schema::dropIfExists('facturas');
    }
};
