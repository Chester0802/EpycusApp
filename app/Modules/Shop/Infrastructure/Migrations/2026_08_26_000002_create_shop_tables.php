<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->unsignedInteger('cost_coins')->default(100);
            $table->string('icon', 20)->default('🎁');
            $table->string('category', 40)->default('ocio');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reward_id')->nullable()->constrained('custom_rewards')->nullOnDelete();
            $table->string('title', 160);
            $table->unsignedInteger('cost_coins')->default(100);
            $table->string('icon', 20)->default('🎁');
            $table->string('status', 20)->default('redeemed'); // redeemed, used
            $table->timestamp('redeemed_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('custom_rewards');
    }
};
