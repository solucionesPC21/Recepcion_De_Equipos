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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_tipoPago')->nullable();
            $table->float('total', 9, 2)->nullable();;
            $table->date('fecha')->nullable(); // Campo para la fecha
            $table->string('usuario',45)->nullable();

            $table->foreign('id_tipoPago')
            ->references('id')->on('tipo_pagos')
            ->onUpdate('cascade')
            ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
