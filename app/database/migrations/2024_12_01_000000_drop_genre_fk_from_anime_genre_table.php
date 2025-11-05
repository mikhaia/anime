<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('anime_genre', function (Blueprint $table) {
                $table->dropForeign('anime_genre_genre_id_foreign');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('anime_genre', function (Blueprint $table) {
                $table->foreign('genre_id')
                    ->references('id')
                    ->on('genres')
                    ->cascadeOnDelete();
            });
        }
    }
};
