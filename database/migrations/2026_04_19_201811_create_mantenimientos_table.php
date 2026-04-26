<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->string('id_orden')->unique(); // Identificador único de la orden
            $table->date('fecha_entrada');
            $table->date('fecha_salida')->nullable();
            $table->enum('tipo', ['preventivo', 'correctivo']);
            $table->enum('reparacion', ['software', 'hardware']);
            $table->text('descripcion');
            $table->decimal('costo', 10, 2)->default(0);
            $table->enum('estado', ['pendiente', 'terminado'])->default('pendiente');
            
            // Relaciones
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('tecnico_id')->constrained('tecnicos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Usuario que registró
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
