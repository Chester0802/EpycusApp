<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pomodoro_session_subtask', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pomodoro_session_id');
            $table->unsignedBigInteger('subtask_id');
            $table->dateTime('completed_at');
            $table->timestamps();

            $table->foreign('pomodoro_session_id')->references('id')->on('pomodoro_sessions')->onDelete('cascade');
            $table->foreign('subtask_id')->references('id')->on('subtasks')->onDelete('cascade');
            $table->index(['pomodoro_session_id', 'subtask_id'], 'idx_pom_session_subtask');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pomodoro_session_subtask');
    }
};
