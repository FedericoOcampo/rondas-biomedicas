<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rondas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->enum('estado', ['borrador', 'guardado', 'completado'])->default('borrador');
            $table->string('firma_responsable_nombre');
            $table->text('firma_responsable_imagen');
            $table->string('firma_jefe_nombre');
            $table->text('firma_jefe_imagen');
            $table->string('firma_lider_nombre')->nullable();
            $table->text('firma_lider_imagen')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rondas');
    }
};