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
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion'); // 'creado', 'actualizado', 'eliminado', 'anulado', 'login', 'logout'
            $table->string('modelo_tipo')->nullable(); // Ej: 'App\Models\Mantenimiento'
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->json('valores_antiguos')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip_direccion')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('descripcion')->nullable(); 
            $table->timestamps();
            
            $table->index(['modelo_tipo', 'modelo_id']);
            $table->index('accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
