<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anime_genre', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anime_id');
            $table->unsignedBigInteger('genre_id');

            $table->foreign('anime_id')->references('id')->on('anime')->cascadeOnDelete();
            $table->foreign('genre_id')->references('id')->on('genres')->cascadeOnDelete();
            $table->unique(['anime_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime_genre');
    }
};
