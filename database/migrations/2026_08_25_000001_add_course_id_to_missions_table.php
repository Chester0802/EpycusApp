<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->after('user_id');
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->index(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropIndex(['user_id', 'course_id']);
            $table->dropColumn('course_id');
        });
    }
};
