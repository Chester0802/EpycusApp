<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20); // expense, income
            $table->decimal('amount', 10, 2);
            $table->string('category', 40);
            $table->date('date');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type']);
        });

        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('category', 40);
            $table->decimal('monthly_limit', 10, 2);
            $table->timestamps();

            $table->unique(['user_id', 'month', 'year', 'category']);
        });

        Schema::create('finance_savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->decimal('target_amount', 10, 2);
            $table->decimal('current_amount', 10, 2)->default(0);
            $table->date('target_date')->nullable();
            $table->unsignedInteger('reward_xp')->default(100);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_savings_goals');
        Schema::dropIfExists('finance_budgets');
        Schema::dropIfExists('finance_transactions');
    }
};
