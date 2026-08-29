<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            if (!Schema::hasColumn('habits', 'time_of_day')) {
                $table->string('time_of_day', 20)->default('anytime')->after('icon');
            }
            if (!Schema::hasColumn('habits', 'cue_trigger')) {
                $table->string('cue_trigger', 160)->nullable()->after('time_of_day');
            }
        });
    }

    public function down(): void
    {
        Schema::table('habits', function (Blueprint $table) {
            if (Schema::hasColumn('habits', 'cue_trigger')) {
                $table->dropColumn('cue_trigger');
            }
            if (Schema::hasColumn('habits', 'time_of_day')) {
                $table->dropColumn('time_of_day');
            }
        });
    }
};
