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
        Schema::table('pomodoro_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('pomodoro_sessions', 'context_type')) {
                $table->string('context_type', 30)->nullable()->after('mission_id');
                // Valores: 'mission', 'reading', 'skill', 'course_project', 'habit', 'free'
            }
            if (!Schema::hasColumn('pomodoro_sessions', 'context_id')) {
                $table->unsignedBigInteger('context_id')->nullable()->after('context_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pomodoro_sessions', function (Blueprint $table) {
            $table->dropColumn(['context_type', 'context_id']);
        });
    }
};
