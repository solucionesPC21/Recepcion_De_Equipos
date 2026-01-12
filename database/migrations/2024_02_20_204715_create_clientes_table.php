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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',60);
            $table->string('telefono',10)->nullable();
            $table->string('telefono2',10)->nullable();
            $table->unsignedBigInteger('id_colonia')->nullable();
            $table->string('rfc',14)->unique()->nullable();  
            $table->timestamps();

            $table->foreign('id_colonia')
            ->references('id')->on('colonias')
            ->onUpdate('cascade')
            ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
