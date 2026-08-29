<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('focus_minutes')->default(25)->after('max_seats');
            $table->unsignedTinyInteger('break_minutes')->default(5)->after('focus_minutes');
            $table->unsignedTinyInteger('cycles')->default(4)->after('break_minutes');
            $table->unsignedTinyInteger('current_cycle')->default(0)->after('cycles');
            $table->string('phase', 20)->default('idle')->after('current_cycle');
            $table->dateTime('phase_started_at')->nullable()->after('phase');
            $table->dateTime('phase_ends_at')->nullable()->after('phase_started_at');
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->index(['state', 'phase'], 'idx_study_room_state');
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_study_room_state');
            $table->dropColumn([
                'focus_minutes', 'break_minutes', 'cycles', 'current_cycle',
                'phase', 'phase_started_at', 'phase_ends_at',
            ]);
        });
    }
};
