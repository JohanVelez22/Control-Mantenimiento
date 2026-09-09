<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('configuraciones') && !Schema::hasColumn('configuraciones', 'formato_factura')) {
            Schema::table('configuraciones', function (Blueprint $table) {
                $table->string('formato_factura')->default('estandar')->after('pie_pagina_factura');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('configuraciones') && Schema::hasColumn('configuraciones', 'formato_factura')) {
            Schema::table('configuraciones', function (Blueprint $table) {
                $table->dropColumn('formato_factura');
            });
        }
    }
};
