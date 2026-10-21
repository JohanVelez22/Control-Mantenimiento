<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('electronicas', 'equipo_id')) {
            if (app()->environment('testing') || DB::table('electronicas')->count() === 0) {
                DB::table('electronicas')->truncate();
            }

            Schema::table('electronicas', function (Blueprint $table) {
                if (Schema::hasColumn('electronicas', 'cliente')) {
                    $table->dropColumn(['cliente', 'dispositivo', 'marca']);
                }
                $table->foreignId('equipo_id')->after('id_orden')->constrained('equipos')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::table('electronicas', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
            $table->dropColumn('equipo_id');
            $table->string('cliente')->after('id_orden');
            $table->string('dispositivo')->after('cliente');
            $table->string('marca')->nullable()->after('dispositivo');
        });
    }
};
