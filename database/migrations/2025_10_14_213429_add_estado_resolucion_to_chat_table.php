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
        Schema::table('chat', function (Blueprint $table) {
            $table->enum('estado_resolucion', [
                'iniciado',
                'esperando_feedback_bd',
                'esperando_feedback_ia',
                'resuelto',
                'derivado'
            ])->default('iniciado')->after('fecha_chat');

            $table->enum('intento_actual', [
                'bd_conocimientos',
                'ia',
                'derivado'
            ])->nullable()->after('estado_resolucion');

            $table->unsignedBigInteger('solucion_propuesta_id')->nullable()->after('intento_actual');

            // Opcional: Foreign key si quieres mantener integridad referencial
            // $table->foreign('solucion_propuesta_id')->references('id')->on('bd_conocimientos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->dropColumn(['estado_resolucion', 'intento_actual', 'solucion_propuesta_id']);
        });
    }
};
