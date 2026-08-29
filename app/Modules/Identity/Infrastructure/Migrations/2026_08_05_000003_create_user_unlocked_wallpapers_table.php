<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_unlocked_wallpapers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('wallpaper_key', 50);
            $table->timestamps();

            $table->unique(['user_id', 'wallpaper_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_unlocked_wallpapers');
    }
};
