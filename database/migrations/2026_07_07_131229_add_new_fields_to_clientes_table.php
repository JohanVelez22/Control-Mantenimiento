<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            // Dividir nombre en primer_nombre + apellidos
            $table->string('primer_nombre')->nullable()->after('nombre');
            $table->string('apellidos')->nullable()->after('primer_nombre');

            // Tipo de identificación
            $table->enum('tipo_identificacion', [
                'cedula_ciudadania',
                'cedula_extranjeria',
                'nit',
                'pasaporte',
                'tarjeta_identidad',
                'rut',
            ])->default('cedula_ciudadania')->after('apellidos');

            // Género
            $table->enum('genero', ['masculino', 'femenino', 'indefinido'])->default('indefinido')->after('identificacion');

            // Tipo de cliente
            $table->enum('tipo_cliente', ['cliente', 'tecnico'])->default('cliente')->after('genero');

            // Ubicación Colombia
            $table->string('departamento', 60)->nullable()->after('direccion');
            $table->string('municipio', 80)->nullable()->after('departamento');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'primer_nombre', 'apellidos', 'tipo_identificacion',
                'genero', 'tipo_cliente', 'departamento', 'municipio',
            ]);
        });
    }
};
