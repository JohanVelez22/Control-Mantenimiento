<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tecnicos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('identificacion')->unique(); // DNI, Cédula o NIT
            $table->string('especialidad'); // ej. Hardware, Software, Redes
            $table->string('movil');
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnicos');
    }
};
