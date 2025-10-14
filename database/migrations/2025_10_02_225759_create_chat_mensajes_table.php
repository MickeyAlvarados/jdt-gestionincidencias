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
        Schema::create('chat_mensajes', function (Blueprint $table) {
            $table->bigInteger('id');
            $table->bigInteger('id_chat');
            $table->primary(['id']);
            $table->bigInteger('emisor')->nullable();
            $table->text('contenido_mensaje')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->foreign('emisor')->references('id')->on('empleados');
            $table->foreign('id_chat')->references('id')->on('chat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_mensajes');
    }
};