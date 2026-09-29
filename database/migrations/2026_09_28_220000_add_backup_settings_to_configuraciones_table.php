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
        if (Schema::hasTable('configuraciones')) {
            Schema::table('configuraciones', function (Blueprint $table) {
                if (! Schema::hasColumn('configuraciones', 'backup_automatico')) {
                    $table->boolean('backup_automatico')->default(true);
                }
                if (! Schema::hasColumn('configuraciones', 'backup_frecuencia')) {
                    $table->string('backup_frecuencia', 20)->default('daily');
                }
                if (! Schema::hasColumn('configuraciones', 'backup_hora')) {
                    $table->string('backup_hora', 10)->default('02:00');
                }
                if (! Schema::hasColumn('configuraciones', 'backup_dia_semana')) {
                    $table->string('backup_dia_semana', 20)->default('sunday');
                }
                if (! Schema::hasColumn('configuraciones', 'backup_dia_mes')) {
                    $table->unsignedTinyInteger('backup_dia_mes')->default(1);
                }
                if (! Schema::hasColumn('configuraciones', 'backup_max_copias')) {
                    $table->unsignedInteger('backup_max_copias')->default(10);
                }
                if (! Schema::hasColumn('configuraciones', 'backup_tipo_incluido')) {
                    $table->string('backup_tipo_incluido', 20)->default('db');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('configuraciones')) {
            Schema::table('configuraciones', function (Blueprint $table) {
                $columns = [
                    'backup_automatico',
                    'backup_frecuencia',
                    'backup_hora',
                    'backup_dia_semana',
                    'backup_dia_mes',
                    'backup_max_copias',
                    'backup_tipo_incluido',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('configuraciones', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
