<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ronda_pruebas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_equipo_id')->constrained('ronda_equipos')->onDelete('cascade');
            $table->string('prueba_id');
            $table->string('prueba_label');
            $table->string('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ronda_pruebas');
    }
};