<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('streams', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('episode_id');
            $table->string('quality');
            $table->string('url');
            $table->timestamps();

            $table->unique(['episode_id', 'quality']);
            $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
