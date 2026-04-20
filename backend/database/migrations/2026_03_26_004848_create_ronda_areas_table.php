<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ronda_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_id')->constrained('rondas')->onDelete('cascade');
            $table->string('area_id');
            $table->string('area_nombre');
            $table->boolean('no_realizada')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ronda_areas');
    }
};