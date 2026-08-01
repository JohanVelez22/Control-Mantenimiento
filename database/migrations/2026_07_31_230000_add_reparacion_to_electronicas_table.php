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
        if (!Schema::hasColumn('electronicas', 'reparacion')) {
            Schema::table('electronicas', function (Blueprint $table) {
                $table->enum('reparacion', ['software', 'hardware'])->default('hardware')->after('tipo');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('electronicas', 'reparacion')) {
            Schema::table('electronicas', function (Blueprint $table) {
                $table->dropColumn('reparacion');
            });
        }
    }
};
