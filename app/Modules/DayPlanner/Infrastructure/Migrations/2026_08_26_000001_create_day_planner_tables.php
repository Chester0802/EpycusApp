<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('time_block', 20)->default('morning'); // morning, afternoon, night, anytime
            $table->string('category', 40)->default('general'); // salud, estudio, personal, trabajo, otro
            $table->string('icon', 40)->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->default(15);
            $table->string('scheduled_time', 10)->nullable(); // e.g. "07:00"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5,6,7]
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'time_block', 'is_active']);
        });

        Schema::create('daily_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('plan_date');
            $table->foreignId('routine_id')->nullable()->constrained('daily_routines')->nullOnDelete();
            $table->string('title', 160);
            $table->string('category', 40)->default('general');
            $table->string('time_block', 20)->default('morning'); // morning, afternoon, night, anytime
            $table->string('scheduled_time', 10)->nullable(); // e.g. "07:30"
            $table->unsignedSmallInteger('estimated_minutes')->default(15);
            $table->string('status', 20)->default('pending'); // pending, done, skipped, postponed
            $table->string('skip_reason', 80)->nullable(); // cansancio, sin_tiempo, imprevisto, desanimo, otro
            $table->string('postponed_to_block', 20)->nullable();
            $table->unsignedTinyInteger('postponed_count')->default(0);
            $table->unsignedSmallInteger('xp_awarded')->default(0);
            $table->unsignedSmallInteger('coins_awarded')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('linked_habit_id')->nullable();
            $table->unsignedBigInteger('linked_mission_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'plan_date', 'status']);
            $table->index(['user_id', 'plan_date', 'time_block']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plan_items');
        Schema::dropIfExists('daily_routines');
    }
};
