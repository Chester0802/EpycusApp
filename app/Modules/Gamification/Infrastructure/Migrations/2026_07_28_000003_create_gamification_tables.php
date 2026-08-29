<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombres de índice más largos que en docs/05-BASE-DATOS.md (`idx_xp`,
 * `idx_user_date`, `uq_idempotency` ahí; acá con prefijo de tabla) a
 * propósito: SQLite (lo que usan los tests, `phpunit.xml`) exige nombres de
 * índice únicos en TODA la base, no solo por tabla como MySQL/MariaDB.
 * `habit_completions` ya tenía un índice llamado `idx_user_date` — probado
 * en la práctica, no en teoría: la suite completa fallaba con "index
 * idx_user_date already exists" hasta corregir esto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedTinyInteger('current_level')->default(1);
            $table->unsignedTinyInteger('current_phase')->default(1);
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->unsignedTinyInteger('grace_days_left')->default(3);
            $table->char('grace_month', 7)->nullable();
            // No está en docs/05-BASE-DATOS.md original — se agregó acá y se
            // documentó ahí mismo. Sin esta columna no se puede distinguir
            // "ya until gasté un día de gracia para ayer, esperando que hoy
            // cumpla" de "hoy es un hueco nuevo, hay que evaluar si hay
            // gracia disponible" — ambos casos ven un hueco entre
            // last_activity_on y "ayer" igual de largo, pero deben
            // resolverse distinto. Ver EvaluateStreaksUseCase.
            $table->date('grace_pending_since')->nullable();
            $table->date('last_activity_on')->nullable();
            $table->unsignedInteger('coins')->default(0);
            $table->timestamps();

            $table->index('total_xp', 'idx_user_progress_xp');
        });

        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedSmallInteger('amount');
            $table->unsignedSmallInteger('base_amount');
            $table->decimal('multiplier', 3, 2)->default(1.00);
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id');
            $table->boolean('was_capped')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'source_type', 'source_id'], 'uq_xp_transactions_idempotency');
            $table->index(['user_id', 'created_at'], 'idx_xp_transactions_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
        Schema::dropIfExists('user_progress');
    }
};
