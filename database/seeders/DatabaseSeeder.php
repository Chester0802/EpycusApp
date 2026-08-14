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
        UserModel::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'alias' => 'test-user',
                'password' => bcrypt('password'),
                'role' => 'student',
            ]
        );


        UserModel::updateOrCreate(
            ['email' => 'admin@epycus.es'],
            [
                'name' => 'Investigador Principal',
                'alias' => 'Marcoadmin',
                'password' => bcrypt('Marcoadmin123@'),
                'role' => 'admin',
            ]
        );

        $this->call([
            MotivationSeeder::class,
        ]);
    }
}
