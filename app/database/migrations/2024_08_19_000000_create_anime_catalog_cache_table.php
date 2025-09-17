<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anime_catalog_cache', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->unsignedInteger('page');
            $table->json('anime_ids')->nullable();
            $table->date('cached_date')->nullable();
            $table->boolean('has_next_page')->default(false);
            $table->timestamps();

            $table->unique(['category', 'page']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anime_catalog_cache');
    }
};
