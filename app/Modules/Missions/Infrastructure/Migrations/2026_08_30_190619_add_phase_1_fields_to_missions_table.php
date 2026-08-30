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
        Schema::table('missions', function (Blueprint $table) {
            $table->enum('mission_type', ['academic', 'work', 'personal', 'project'])
                  ->default('academic')
                  ->after('course_id');
            $table->unsignedBigInteger('project_phase_id')
                  ->nullable()
                  ->after('mission_type');
            $table->date('planned_date')->nullable()->after('due_date');
            $table->time('planned_start')->nullable()->after('planned_date');
            $table->time('planned_end')->nullable()->after('planned_start');
            
            $table->index(['user_id', 'mission_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'mission_type']);
            
            $table->dropColumn([
                'mission_type',
                'project_phase_id',
                'planned_date',
                'planned_start',
                'planned_end'
            ]);
        });
    }
};
