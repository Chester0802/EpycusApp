<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->enum('category', ['technical', 'soft', 'language', 'creative', 'physical', 'other'])->default('technical');
            $table->string('icon', 40)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('current_level')->default(1);
            $table->unsignedInteger('current_xp')->default(0);
            $table->unsignedInteger('target_xp')->default(100);
            $table->unsignedInteger('total_minutes_practiced')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });

        Schema::create('personal_skill_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained('personal_skills')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('duration_minutes');
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('xp_earned')->default(0);
            $table->date('logged_at');
            $table->timestamps();

            $table->index(['skill_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_skill_logs');
        Schema::dropIfExists('personal_skills');
    }
};
