<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('event_name', 64);
            $table->string('event_category', 32);
            $table->json('payload')->nullable();
            $table->char('session_uuid', 36)->nullable();
            $table->smallInteger('intervention_day')->unsigned()->nullable();
            $table->dateTime('occurred_at', 3);
            $table->dateTime('recorded_at', 3)->useCurrent();
            $table->enum('source', ['web', 'backend'])->default('web');

            $table->index(['user_id', 'occurred_at'], 'idx_user_time');
            $table->index(['event_name', 'occurred_at'], 'idx_event');
            $table->index('intervention_day', 'idx_day');
            $table->index(['event_category', 'occurred_at'], 'idx_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_events');
    }
};
