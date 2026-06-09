<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique()->nullable();
            $table->string('producto');
            $table->integer('cantidad')->default(0);
            $table->string('proveedor')->nullable();
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->decimal('utilidad', 5, 2)->default(0); // Porcentaje de utilidad
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->decimal('precio_tecnico', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
