<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            $table->enum('reward_type', ['custom', 'catalog', 'entertainment'])->default('catalog')->after('reward_id');
            $table->string('entertainment_title', 200)->nullable()->after('title');
            $table->enum('entertainment_category', ['series', 'movie', 'anime', 'videogame', 'book', 'other'])->nullable()->after('entertainment_title');
            $table->text('review_text')->nullable()->after('status');
            $table->unsignedTinyInteger('rating')->nullable()->after('review_text');
        });
    }

    public function down(): void
    {
        Schema::table('reward_redemptions', function (Blueprint $table) {
            $table->dropColumn([
                'reward_type',
                'entertainment_title',
                'entertainment_category',
                'review_text',
                'rating',
            ]);
        });
    }
};
