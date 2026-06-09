<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concepto_cajas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->timestamps();
        });

        // Conceptos predefinidos
        \DB::table('concepto_cajas')->insert([
            ['nombre' => 'Pago de mantenimiento', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Venta de repuesto',     'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Servicio técnico',       'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Compra de insumos',      'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Pago de servicios',      'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Arriendo',               'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Nómina / Pago técnico',  'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Gastos varios',           'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('concepto_cajas');
    }
};
