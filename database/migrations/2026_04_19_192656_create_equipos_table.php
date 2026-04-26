<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('marca');
            $table->string('modelo');
            $table->string('serie')->unique();
            $table->text('observacion')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Relación con la tabla clientes (se mantiene igual)
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
