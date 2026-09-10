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
        Schema::create('bajas_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('cantidad');
            $table->decimal('precio_compra_unitario', 12, 2)->default(0);
            $table->decimal('costo_total_perdida', 12, 2)->default(0);
            $table->string('motivo', 50)->default('defectuoso_fabrica'); // defectuoso_fabrica, dano_taller, obsoleto, perdida_merma, otro
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['stock_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bajas_stock');
    }
};
