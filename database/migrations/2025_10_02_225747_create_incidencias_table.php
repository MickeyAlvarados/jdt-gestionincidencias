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
        Schema::create('incidencias', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->text('descripcion_problema')->nullable();
            $table->date('fecha_incidencia')->nullable();
            $table->bigInteger('idcategoria')->nullable();
            $table->bigInteger('idempleado')->nullable();
            $table->bigInteger('estado')->nullable();
            $table->bigInteger('id_chat')->nullable();
            $table->bigInteger('prioridad')->nullable();
            $table->foreign('estado')->references('id')->on('estados');
            $table->foreign('id_chat')->references('id')->on('chat');
            $table->foreign('idcategoria')->references('id')->on('categorias');
            $table->foreign('idempleado')->references('id')->on('empleados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias');
    }
};