<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivational_quotes', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('author');
            $table->boolean('is_verified')->default(true); // true: documentada, false: atribuida
            $table->timestamps();
        });

        Schema::create('usage_tips', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 30);
            $table->text('content');
            $table->timestamps();

            $table->index('module_key');
        });

        Schema::create('user_quote_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quote_id')->constrained('motivational_quotes')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_tip_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tip_id')->constrained('usage_tips')->onDelete('cascade');
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tip_views');
        Schema::dropIfExists('user_quote_views');
        Schema::dropIfExists('usage_tips');
        Schema::dropIfExists('motivational_quotes');
    }
};
