<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\AchievementsSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('achievements')) {
            Schema::create('achievements', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 80);
                $table->string('description');
                $table->string('category', 30)->default('general');
                $table->string('icon', 10)->default('🏆');
                $table->unsignedSmallInteger('xp_reward')->default(30);
                $table->string('wallpaper_reward_key')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('achievements', function (Blueprint $table) {
                if (!Schema::hasColumn('achievements', 'code')) {
                    $table->string('code', 40)->nullable()->unique();
                }
                if (!Schema::hasColumn('achievements', 'category')) {
                    $table->string('category', 30)->default('general');
                }
                if (!Schema::hasColumn('achievements', 'xp_reward')) {
                    $table->unsignedSmallInteger('xp_reward')->default(30);
                }
                if (!Schema::hasColumn('achievements', 'wallpaper_reward_key')) {
                    $table->string('wallpaper_reward_key')->nullable();
                }
            });
        }

        if (!Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('achievement_id')->constrained('achievements')->onDelete('cascade');
                $table->timestamp('unlocked_at')->useCurrent();

                $table->unique(['user_id', 'achievement_id'], 'uq_user_achievement');
                $table->timestamps();
            });
        }

        // Ejecutar Seeder para asegurar que los logros existan
        $seeder = new AchievementsSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
