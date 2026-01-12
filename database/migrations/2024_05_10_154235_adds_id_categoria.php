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
        Schema::table('nombreconcepto', function (Blueprint $table) {
            $table->unsignedBigInteger('id_categoria')->nullable();

            $table->foreign('id_categoria')
            ->references('id')->on('categorias')
            ->onUpdate('cascade')
            ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nombreconcepto', function (Blueprint $table) {
            //
        });
    }
};
