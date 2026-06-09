<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_cajas', function (Blueprint $table) {
            $table->id();
            $table->string('empresa')->nullable();
            $table->string('persona');
            $table->date('fecha');
            $table->foreignId('concepto_id')->constrained('concepto_cajas')->onDelete('restrict');
            $table->enum('tipo_movimiento', ['ingreso', 'egreso']);
            $table->enum('tipo_pago', ['efectivo', 'consignacion']);
            $table->decimal('monto', 12, 2);
            $table->text('descripcion')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_cajas');
    }
};
