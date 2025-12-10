<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('watch_progress', function (Blueprint $table) {
            $table->unsignedInteger('time')->default(0)->after('episode_number');
        });
    }

    public function down(): void
    {
        Schema::table('watch_progress', function (Blueprint $table) {
            $table->dropColumn('time');
        });
    }
};
