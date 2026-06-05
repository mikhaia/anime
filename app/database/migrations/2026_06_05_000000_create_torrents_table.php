<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('torrents')) {
            return;
        }

        Schema::create('torrents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('anime_id');
            $table->string('label')->nullable();
            $table->string('quality')->nullable();
            $table->string('size')->nullable();
            $table->text('magnet')->nullable();
            $table->timestamps();

            $table->index('anime_id');
            $table->unique(['anime_id', 'magnet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('torrents');
    }
};
