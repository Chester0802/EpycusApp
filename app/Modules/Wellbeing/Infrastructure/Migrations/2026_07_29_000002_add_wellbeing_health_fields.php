<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unsignedTinyInteger('energy')->nullable()->after('mood_score');
            $table->unsignedTinyInteger('stress')->nullable()->after('energy');
            $table->decimal('sleep_hours', 3, 1)->nullable()->after('stress');
            $table->json('physical_activity')->nullable()->after('sleep_hours');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['energy', 'stress', 'sleep_hours', 'physical_activity']);
        });
    }
};
