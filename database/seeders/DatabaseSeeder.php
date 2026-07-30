<?php

namespace Database\Seeders;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserModel::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'alias' => 'test-user',
            'role' => 'student',
        ]);

        UserModel::firstOrCreate(
            ['email' => 'admin@epycus.es'],
            [
                'name' => 'Investigador Principal',
                'alias' => 'AdminEpycus',
                'password' => bcrypt('admin1234'),
                'role' => 'admin',
            ]
        );

        $this->call([
            MotivationSeeder::class,
        ]);
    }
}
