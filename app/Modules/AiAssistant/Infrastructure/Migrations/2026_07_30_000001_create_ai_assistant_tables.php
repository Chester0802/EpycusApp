<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 128)->default('Conversación de apoyo');
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->onDelete('cascade');
            $table->string('role', 16); // 'user', 'assistant', 'system'
            $table->text('content');
            $table->string('category', 32)->nullable(); // 'academic', 'wellbeing', 'productivity', 'general'
            $table->unsignedTinyInteger('rating')->nullable(); // 1 to 5
            $table->timestamps();

            $table->index('conversation_id', 'idx_ai_messages_conv');
        });

        Schema::create('ai_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'date'], 'uq_ai_quotas_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_quotas');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
