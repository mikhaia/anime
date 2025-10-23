<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relates', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('anime_id');
            $table->string('title');
            $table->string('title_english');
            $table->string('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relates');
    }
};
