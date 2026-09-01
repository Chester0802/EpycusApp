<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->enum('habit_type', ['build', 'break'])->default('build')->after('category');
            $table->unsignedTinyInteger('max_per_week')->nullable()->after('habit_type');
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            $table->dropColumn(['habit_type', 'max_per_week']);
        });
    }
};
