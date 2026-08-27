<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('daily_plan_items')) {
            DB::table('daily_plan_items')->delete();
        }
        if (Schema::hasTable('daily_routines')) {
            DB::table('daily_routines')->delete();
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No-op
    }
};
