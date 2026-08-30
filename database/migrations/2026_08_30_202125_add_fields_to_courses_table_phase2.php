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
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'period_id')) {
                $table->foreignId('period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            }
            if (!Schema::hasColumn('courses', 'professor')) {
                $table->string('professor')->nullable();
            }
            if (!Schema::hasColumn('courses', 'credits')) {
                $table->integer('credits')->nullable();
            }
            if (!Schema::hasColumn('courses', 'target_grade')) {
                $table->decimal('target_grade', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('courses', 'min_pass_grade')) {
                $table->decimal('min_pass_grade', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('courses', 'syllabus_path')) {
                $table->string('syllabus_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
            $table->dropColumn(['period_id', 'professor', 'credits', 'target_grade', 'min_pass_grade', 'syllabus_path']);
        });
    }
};
