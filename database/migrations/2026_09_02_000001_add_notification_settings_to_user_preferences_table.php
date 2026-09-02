<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_preferences') && !Schema::hasColumn('user_preferences', 'notification_settings')) {
            Schema::table('user_preferences', function (Blueprint $table) {
                $table->json('notification_settings')->nullable()->after('notifications_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_preferences') && Schema::hasColumn('user_preferences', 'notification_settings')) {
            Schema::table('user_preferences', function (Blueprint $table) {
                $table->dropColumn('notification_settings');
            });
        }
    }
};
