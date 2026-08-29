<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('alias', 40)->after('password');
            $table->string('role', 20)->default('participant')->after('alias');
            $table->string('career', 60)->nullable()->after('role');
            $table->string('avatar_style', 20)->nullable()->after('career');
            $table->string('avatar_gender', 1)->nullable()->after('avatar_style');
            $table->unsignedTinyInteger('cycle')->nullable()->after('avatar_gender');
            $table->string('institution_type', 20)->nullable()->after('cycle');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'alias', 'role', 'career', 'avatar_style',
                'avatar_gender', 'cycle', 'institution_type',
            ]);
        });
    }
};
