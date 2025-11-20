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
        Schema::create('detalle_incidencia', function (Blueprint $table) {
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('idincidencia');
            $table->primary(['id', 'idincidencia']);
            $table->date('fecha_inicio')->nullable();
            $table->bigInteger('cargo_id')->nullable();
            $table->bigInteger('estado_atencion')->nullable();
            $table->bigInteger('idempleado_informatica')->nullable();
            $table->text('comentarios')->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->foreign('estado_atencion')->references('id')->on('estados');
            $table->foreign('idempleado_informatica')->references('id')->on('empleados');
            $table->foreign('idincidencia')->references('id')->on('incidencias');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_incidencia');
    }
};
