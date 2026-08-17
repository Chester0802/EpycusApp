<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->enum('eisenhower_quadrant', ['q1', 'q2', 'q3', 'q4'])->default('q2')->after('priority');
            $table->index(['user_id', 'eisenhower_quadrant']);
        });

        // Retrocompatibilidad: Misiones con prioridad alta o vencidas se inicializan en q1
        DB::table('missions')
            ->where('priority', 'alta')
            ->orWhere('is_overdue', true)
            ->update(['eisenhower_quadrant' => 'q1']);
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'eisenhower_quadrant']);
            $table->dropColumn('eisenhower_quadrant');
        });
    }
};
