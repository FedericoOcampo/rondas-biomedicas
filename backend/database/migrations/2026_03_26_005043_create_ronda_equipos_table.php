<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ronda_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_area_id')->constrained('ronda_areas')->onDelete('cascade');
            $table->string('equipo_id');
            $table->string('equipo_nombre');
            $table->string('placa')->nullable();
            $table->string('placa2')->nullable();
            $table->boolean('no_encontrado')->default(false);
            $table->string('revision_fisica')->nullable();
            $table->string('apto_para_uso')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ronda_equipos');
    }
};