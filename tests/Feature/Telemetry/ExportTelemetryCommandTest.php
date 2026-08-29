<?php

declare(strict_types=1);

namespace Tests\Feature\Telemetry;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class ExportTelemetryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_export_command_generates_csv_files(): void
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create();

        DB::table('participants')->insert([
            'user_id' => $user->id,
            'participant_code' => 'P-EXP01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('telemetry_events')->insert([
            'user_id' => $user->id,
            'event_name' => 'habit.completed',
            'event_category' => 'habits',
            'payload' => json_encode(['habit_id' => 1]),
            'intervention_day' => 1,
            'occurred_at' => now()->toIso8601String(),
            'recorded_at' => now()->toIso8601String(),
            'source' => 'web',
        ]);

        $exportDir = storage_path('app/exports');
        if (File::exists($exportDir)) {
            File::cleanDirectory($exportDir);
        }

        $this->artisan('telemetry:export', [
            '--from' => now()->subDay()->format('Y-m-d'),
            '--to' => now()->addDay()->format('Y-m-d'),
        ])->assertExitCode(0);

        $this->assertFileExists($exportDir.'/events_raw.csv');
        $this->assertFileExists($exportDir.'/daily_per_user.csv');
        $this->assertFileExists($exportDir.'/summary_per_user.csv');

        $dailyContent = File::get($exportDir.'/daily_per_user.csv');
        $this->assertStringContainsString('P-EXP01', $dailyContent);
    }
}
