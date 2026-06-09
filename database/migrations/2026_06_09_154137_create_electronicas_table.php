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
        Schema::create('electronicas', function (Blueprint $table) {
            $table->id();
            $table->string('id_orden')->unique()->nullable();
            $table->string('cliente');           // Nombre libre (sin FK, ya que puede ser diferente)
            $table->string('dispositivo');       // Tipo de dispositivo electrónico
            $table->string('marca')->nullable();
            $table->text('descripcion_problema');
            $table->enum('tipo', ['preventivo', 'correctivo'])->default('correctivo');
            $table->decimal('costo', 10, 2)->default(0);
            $table->enum('estado', ['pendiente', 'terminado'])->default('pendiente');
            $table->date('fecha_entrada');
            $table->date('fecha_salida')->nullable();
            $table->foreignId('tecnico_id')->constrained('tecnicos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electronicas');
    }
};
