<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pomodoro_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('study_group_session_id')->nullable()->after('mission_id');

            $table->index('study_group_session_id', 'idx_pomodoro_study_group');
        });
    }

    public function down(): void
    {
        Schema::table('pomodoro_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_pomodoro_study_group');
            $table->dropColumn('study_group_session_id');
        });
    }
};
