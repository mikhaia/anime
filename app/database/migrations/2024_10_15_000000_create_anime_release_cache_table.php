<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anime_release_cache', function (Blueprint $table) {
            $table->unsignedBigInteger('anime_id')->primary();
            $table->json('episodes')->nullable();
            $table->json('related')->nullable();
            $table->timestamps();

            $table->foreign('anime_id')
                ->references('id')
                ->on('anime')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime_release_cache');
    }
};
