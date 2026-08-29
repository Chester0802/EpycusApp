<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `xp_awarded`/`was_capped` en `habit_completions` quedaron sin usar desde
 * que Fase 4 (Gamification) movió esa responsabilidad a `xp_transactions`
 * (fuente única de verdad). Mantener las dos columnas habría dejado dos
 * lugares con la "misma" información, uno de ellos sin actualizar nunca
 * más — justo el tipo de confusión que se quería evitar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habit_completions', function (Blueprint $table) {
            $table->dropColumn(['xp_awarded', 'was_capped']);
        });
    }

    public function down(): void
    {
        Schema::table('habit_completions', function (Blueprint $table) {
            $table->smallInteger('xp_awarded')->unsigned()->default(0);
            $table->boolean('was_capped')->default(false);
        });
    }
};
