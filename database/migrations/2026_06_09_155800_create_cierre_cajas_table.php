<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierre_cajas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();           // Un cierre por día
            $table->decimal('total_ingresos', 12, 2)->default(0);
            $table->decimal('total_egresos', 12, 2)->default(0);
            $table->decimal('efectivo', 12, 2)->default(0);
            $table->decimal('consignacion', 12, 2)->default(0);
            $table->decimal('saldo_final', 12, 2)->default(0);
            $table->integer('num_movimientos')->default(0);
            $table->boolean('bloqueado')->default(true);  // Al cerrar, bloquea el día
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierre_cajas');
    }
};
