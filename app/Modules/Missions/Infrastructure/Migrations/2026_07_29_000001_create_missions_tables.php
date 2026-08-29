<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->enum('priority', ['baja', 'normal', 'alta'])->default('normal');
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->smallInteger('days_early_or_late')->nullable();
            $table->boolean('is_overdue')->default(false);
            $table->unsignedSmallInteger('xp_awarded')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'due_date']);
            $table->index(['is_overdue', 'due_date']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('subtasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mission_id');
            $table->string('title', 160);
            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['mission_id', 'sort_order']);
            $table->foreign('mission_id')->references('id')->on('missions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtasks');
        Schema::dropIfExists('missions');
    }
};
