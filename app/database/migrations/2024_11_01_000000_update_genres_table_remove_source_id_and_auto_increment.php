<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            $table->dropColumn('source_id');
        });

        DB::statement('ALTER TABLE genres MODIFY id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE genres MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        Schema::table('genres', function (Blueprint $table) {
            $table->integer('source_id')->nullable()->after('name');
        });
    }
};
