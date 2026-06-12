<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Relación formal con proveedores (reemplaza el campo string 'proveedor')
            $table->foreignId('proveedor_id')
                  ->nullable()
                  ->after('proveedor')
                  ->constrained('proveedores')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['proveedor_id']);
            $table->dropColumn('proveedor_id');
        });
    }
};
