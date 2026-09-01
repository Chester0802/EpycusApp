<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flashcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->unsignedBigInteger('reading_id')->nullable()->index();
            $table->unsignedBigInteger('skill_id')->nullable()->index();
            $table->string('node_id', 80)->nullable()->index();
            $table->enum('source', ['ai', 'manual'])->default('manual');
            $table->text('question');
            $table->text('answer');
            $table->unsignedTinyInteger('leitner_box')->default(1); // 1..5
            $table->date('next_review_at')->nullable()->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('success_streak')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'course_id', 'leitner_box']);
            $table->index(['user_id', 'next_review_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flashcards');
    }
};
