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
        Schema::create('bd_conocimientos', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('id_incidencia')->nullable();
            $table->text('descripcion_problema')->nullable();
            $table->date('fecha_incidencia')->nullable();
            $table->text('comentario_resolucion')->nullable();
            $table->string('empleado_resolutor', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_conocimientos');
    }
};