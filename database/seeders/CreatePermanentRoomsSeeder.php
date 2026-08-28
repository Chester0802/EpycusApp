<?php

namespace Database\Seeders;

use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;
use Illuminate\Database\Seeder;

class CreatePermanentRoomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $firstUserId = \Illuminate\Support\Facades\DB::table('users')->first()->id ?? 6;

        StudySessionModel::updateOrCreate(
            ['id' => 1],
            [
                'host_id' => $firstUserId,
                'name' => 'Cafetería',
                'max_seats' => 50,
                'focus_minutes' => 25,
                'break_minutes' => 5,
                'cycles' => 4,
                'current_cycle' => 1,
                'state' => 'open',
                'phase' => 'idle'
            ]
        );

        StudySessionModel::updateOrCreate(
            ['id' => 2],
            [
                'host_id' => $firstUserId,
                'name' => 'Biblioteca',
                'max_seats' => 50,
                'focus_minutes' => 50,
                'break_minutes' => 10,
                'cycles' => 4,
                'current_cycle' => 1,
                'state' => 'open',
                'phase' => 'idle'
            ]
        );
    }
}
