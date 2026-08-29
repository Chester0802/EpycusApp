<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 80);
            $table->string('description');
            $table->string('category', 30); // constancia, volumen, progresion, villanos, bienestar, puntualidad
            $table->string('icon', 10)->default('🏆');
            $table->unsignedSmallInteger('xp_reward')->default(30); // 20 - 100 XP
            $table->string('wallpaper_reward_key')->nullable();
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained('achievements')->onDelete('cascade');
            $table->timestamp('unlocked_at')->useCurrent();

            $table->unique(['user_id', 'achievement_id'], 'uq_user_achievement');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
    }
};
