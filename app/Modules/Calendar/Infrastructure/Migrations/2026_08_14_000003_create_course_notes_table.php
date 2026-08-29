<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('content')->nullable()->comment('JSON con array de entries (entradas fechadas)');
            $table->timestamps();

            $table->unique('course_id'); // Un apunte por curso
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_notes');
    }
};
