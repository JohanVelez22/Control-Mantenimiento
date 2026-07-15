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
        Schema::table('factura_items', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_id')->nullable()->change();
            $table->string('descripcion')->nullable()->after('stock_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factura_items', function (Blueprint $table) {
            //
        });
    }
};
