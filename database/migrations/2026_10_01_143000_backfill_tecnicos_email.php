<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tecnicos') || ! Schema::hasColumn('tecnicos', 'email')) {
            return;
        }

        $emails = [
            '102030101' => 'andres.martinez@tecnisystemas.com',
            '102030102' => 'roberto.sanchez@tecnisystemas.com',
            '102030103' => 'luis.osorio@tecnisystemas.com',
            '102030104' => 'miguel.rojas@tecnisystemas.com',
            '102030105' => 'hector.castano@tecnisystemas.com',
        ];

        foreach ($emails as $identificacion => $email) {
            DB::table('tecnicos')
                ->where('identificacion', $identificacion)
                ->whereNull('email')
                ->update(['email' => $email]);
        }

        $byName = [
            'Andrés Felipe Martínez' => 'andres.martinez@tecnisystemas.com',
            'Roberto Sánchez' => 'roberto.sanchez@tecnisystemas.com',
            'Luis Fernando Osorio' => 'luis.osorio@tecnisystemas.com',
            'Miguel Ángel Rojas' => 'miguel.rojas@tecnisystemas.com',
            'Héctor Fabio Castaño' => 'hector.castano@tecnisystemas.com',
        ];

        foreach ($byName as $nombre => $email) {
            DB::table('tecnicos')
                ->where('nombre', $nombre)
                ->whereNull('email')
                ->update(['email' => $email]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos los emails para no borrar datos válidos
    }
};
