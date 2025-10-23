<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('streams', 'anime_id')) {
            return;
        }

        Schema::table('streams', function (Blueprint $table) {
            $table->unsignedInteger('anime_id')->after('episode_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('streams', 'anime_id')) {
            return;
        }

        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn('anime_id');
        });
    }
};
