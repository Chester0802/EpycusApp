<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->comment('1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('classroom', 60)->nullable();
            $table->timestamps();

            $table->index(['course_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
