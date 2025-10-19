<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->boolean('is_ongoing')->default(false)->after('alias');
            $table->string('age_rating')->nullable()->after('is_ongoing');
        });
    }

    public function down(): void
    {
        Schema::table('anime', function (Blueprint $table) {
            $table->dropColumn(['is_ongoing', 'age_rating']);
        });
    }
};
