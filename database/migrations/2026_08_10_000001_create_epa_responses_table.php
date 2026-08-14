<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epa_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('participant_code');
            $table->enum('phase', ['pretest', 'postest'])->default('pretest');
            $table->unsignedTinyInteger('item_2');
            $table->unsignedTinyInteger('item_5');
            $table->unsignedTinyInteger('item_7');
            $table->unsignedTinyInteger('item_10');
            $table->unsignedTinyInteger('item_11');
            $table->unsignedTinyInteger('item_12');
            $table->unsignedTinyInteger('item_13');
            $table->unsignedTinyInteger('item_14');
            $table->unsignedSmallInteger('total_score');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['user_id', 'phase']);
            $table->index('participant_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epa_responses');
    }
};
