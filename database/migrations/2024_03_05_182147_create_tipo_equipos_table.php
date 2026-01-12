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
        Schema::create('tipo_equipos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente')->nullable();
            $table->unsignedBigInteger('id_equipo')->nullable();
            $table->unsignedBigInteger('id_marca')->nullable();
            $table->string('modelo',45)->nullable();
            $table->string('ns',45)->nullable();
            $table->string('falla',240);
            $table->string('accesorio',45)->nullable();
            $table->string('usuario',45)->nullable();
            $table->date('fecha'); // Campo para la fecha
            $table->time('hora'); // Campo para la hora
            

            $table->foreign('id_cliente')
            ->references('id')->on('clientes')
            ->onUpdate('cascade')
            ->onDelete('set null');
            $table->foreign('id_equipo')
            ->references('id')->on('equipos')
            ->onUpdate('cascade')
            ->onDelete('set null');
            $table->foreign('id_marca')
            ->references('id')->on('marcas')
            ->onUpdate('cascade')
            ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_equipos');
    }
};
