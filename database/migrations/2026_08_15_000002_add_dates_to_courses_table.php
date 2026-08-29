<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (! Schema::hasColumn('courses', 'starts_at')) {
                $table->date('starts_at')->nullable()->after('color');
            }
            if (! Schema::hasColumn('courses', 'ends_at')) {
                $table->date('ends_at')->nullable()->after('starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
            if (Schema::hasColumn('courses', 'ends_at')) {
                $table->dropColumn('ends_at');
            }
        });
    }
};
