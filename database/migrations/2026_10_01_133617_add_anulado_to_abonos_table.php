<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->boolean('anulado')->default(false)->after('descripcion');
            $table->index('anulado');
        });
    }

    public function down(): void
    {
        Schema::table('abonos', function (Blueprint $table) {
            $table->dropIndex(['anulado']);
            $table->dropColumn('anulado');
        });
    }
};
