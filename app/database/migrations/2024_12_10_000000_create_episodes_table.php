<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('episodes')) {
            return;
        }

        Schema::create('episodes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('anime_id');
            $table->unsignedSmallInteger('number');
            $table->string('title');
            $table->unsignedSmallInteger('duration')->nullable();
            $table->timestamps();

            $table->unique(['anime_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
