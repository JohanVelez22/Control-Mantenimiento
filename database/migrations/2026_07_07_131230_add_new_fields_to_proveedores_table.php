<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            // Tipo de identificación
            $table->enum('tipo_identificacion', [
                'cedula_ciudadania',
                'cedula_extranjeria',
                'nit',
                'pasaporte',
                'tarjeta_identidad',
                'rut',
            ])->default('nit')->after('tipo_entidad');

            // Ubicación Colombia
            $table->string('departamento', 60)->nullable()->after('direccion');
            $table->string('municipio', 80)->nullable()->after('departamento');

            // Contacto adicional
            $table->string('telefono2')->nullable()->after('telefono');
            $table->string('contacto_nombre')->nullable()->after('telefono2');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_identificacion', 'departamento', 'municipio',
                'telefono2', 'contacto_nombre',
            ]);
        });
    }
};
