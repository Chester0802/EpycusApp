<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 120);
            $table->enum('category', ['estudio', 'sueno', 'ejercicio', 'alimentacion', 'otro']);
            $table->json('frequency');
            $table->string('icon', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active'], 'idx_user_active');
        });

        Schema::create('habit_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained('habits')->onDelete('cascade');
            $table->foreignId('user_id');
            $table->date('completed_for');
            $table->dateTime('completed_at');
            $table->boolean('is_late')->default(false);
            $table->smallInteger('xp_awarded')->unsigned()->default(0);
            $table->boolean('was_capped')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['habit_id', 'completed_for'], 'uq_habit_day');
            $table->index(['user_id', 'completed_for'], 'idx_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habit_completions');
        Schema::dropIfExists('habits');
    }
};
