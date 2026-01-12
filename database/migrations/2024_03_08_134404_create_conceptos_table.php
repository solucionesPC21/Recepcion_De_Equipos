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
        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ticket')->nullable();
            $table->string('concepto',60);
            $table->float('precio', 9, 2)->nullable();
            $table->Integer('cantidad')->nullable();
            $table->float('total', 9, 2)->nullable();
            
            $table->foreign('id_ticket')
            ->references('id')->on('tickets')
            ->onUpdate('cascade')
            ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos');
    }
};
