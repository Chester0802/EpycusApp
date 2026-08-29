<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fitness_exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('category', 40); // escritorio, fuerza, cardio, flexibilidad
            $table->string('difficulty', 20)->default('facil');
            $table->string('target_muscles', 120);
            $table->text('instructions');
            $table->unsignedInteger('default_duration_seconds')->default(45);
            $table->string('icon', 20)->default('💪');
            $table->timestamps();
        });

        Schema::create('fitness_workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('routine_name', 120);
            $table->unsignedInteger('duration_minutes')->default(15);
            $table->unsignedInteger('calories_burned')->default(70);
            $table->string('notes', 255)->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index(['user_id', 'performed_at']);
        });

        Schema::create('daily_hydration_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('glasses_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_hydration_logs');
        Schema::dropIfExists('fitness_workout_logs');
        Schema::dropIfExists('fitness_exercises');
    }
};
