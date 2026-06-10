<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('streams', 'cached_at')) {
            return;
        }

        Schema::table('streams', function (Blueprint $table) {
            $table->timestamp('cached_at')->nullable()->default(null)->after('url');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('streams', 'cached_at')) {
            return;
        }

        Schema::table('streams', function (Blueprint $table) {
            $table->dropColumn('cached_at');
        });
    }
};
