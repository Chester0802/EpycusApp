<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('author', 200)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('type', ['book_fiction', 'book_nonfiction', 'academic_article', 'thesis', 'manual', 'other'])->default('book_nonfiction');
            $table->unsignedSmallInteger('total_pages')->nullable();
            $table->string('isbn', 30)->nullable();
            $table->string('cover_url', 500)->nullable();
            $table->enum('status', ['want_to_read', 'reading', 'finished', 'paused', 'dropped'])->default('want_to_read');
            $table->unsignedSmallInteger('current_page')->default(0);
            $table->unsignedTinyInteger('rating')->nullable(); // 1..5
            $table->unsignedBigInteger('linked_habit_id')->nullable();
            $table->date('started_at')->nullable();
            $table->date('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('reading_tags', function (Blueprint $table) {
            $table->foreignId('reading_id')->constrained('readings')->cascadeOnDelete();
            $table->string('tag', 50);
            $table->primary(['reading_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_tags');
        Schema::dropIfExists('readings');
    }
};
