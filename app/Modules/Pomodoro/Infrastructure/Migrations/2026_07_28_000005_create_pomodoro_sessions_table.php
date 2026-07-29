<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `mission_id` sin FK a propósito: Missions (Fase 6) todavía no existe.
 * Cuando exista, agregar la constraint en una migración nueva — no se
 * inventa el esquema de esa tabla acá.
 *
 * `started_at`/`paused_at`/`ended_at` son `dateTime()`, no `timestamp()`:
 * probado en navegador, no en teoría — con `timestamp()` (que en
 * MySQL/MariaDB se convierte según el `time_zone` de la sesión de la
 * conexión) `paused_at` volvía 5 horas adelantado respecto a `started_at`
 * pese a haberse guardado segundos después. `habit_completions.completed_at`
 * ya usaba `dateTime()` (sin conversión de zona horaria nunca) — se sigue
 * ese mismo patrón acá para no repetir el error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pomodoro_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('mission_id')->nullable();
            $table->unsignedTinyInteger('planned_minutes');
            $table->dateTime('started_at');
            $table->dateTime('paused_at')->nullable();
            $table->unsignedInteger('total_paused_seconds')->default(0);
            $table->dateTime('ended_at')->nullable();
            $table->string('status', 12)->default('running');
            $table->unsignedSmallInteger('focus_minutes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'idx_pomodoro_user_status');
            $table->index(['user_id', 'created_at'], 'idx_pomodoro_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pomodoro_sessions');
    }
};
