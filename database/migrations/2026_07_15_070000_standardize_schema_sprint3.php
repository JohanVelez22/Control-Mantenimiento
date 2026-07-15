<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 3 — Estandarización de esquema:
 * 1. Renombrar 'activo' → 'active' en proveedores (consistencia con el resto del proyecto).
 * 2. Eliminar campo legacy 'proveedor' (texto) de stocks (redundante con proveedor_id FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Proveedores: renombrar 'activo' a 'active' para consistencia
        if (Schema::hasColumn('proveedores', 'activo') && !Schema::hasColumn('proveedores', 'active')) {
            Schema::table('proveedores', function (Blueprint $table) {
                $table->renameColumn('activo', 'active');
            });
        }

        // Actualizar índice si existe
        try {
            Schema::table('proveedores', function (Blueprint $table) {
                $table->dropIndex('idx_proveedores_activo');
            });
        } catch (\Exception $e) {
            // El índice puede no existir, ignorar
        }

        // 2. Stocks: eliminar campo texto legacy 'proveedor' (ya existe proveedor_id FK)
        if (Schema::hasColumn('stocks', 'proveedor')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropColumn('proveedor');
            });
        }
    }

    public function down(): void
    {
        // Revertir: agregar campo legacy de vuelta
        if (!Schema::hasColumn('stocks', 'proveedor')) {
            Schema::table('stocks', function (Blueprint $table) {
                $table->string('proveedor')->nullable()->after('subcategoria');
            });
        }

        // Revertir: renombrar 'active' a 'activo' en proveedores
        if (Schema::hasColumn('proveedores', 'active') && !Schema::hasColumn('proveedores', 'activo')) {
            Schema::table('proveedores', function (Blueprint $table) {
                $table->renameColumn('active', 'activo');
            });

            Schema::table('proveedores', function (Blueprint $table) {
                $table->index('activo', 'idx_proveedores_activo');
            });
        }
    }
};
