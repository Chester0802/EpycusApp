<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_redirects_to_admin_index(): void
    {
        $admin = UserModel::factory()->create([
            'email' => 'admin@epycus.es',
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@epycus.es',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.index', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_student_login_redirects_to_student_dashboard(): void
    {
        $student = UserModel::factory()->create([
            'email' => 'student@epycus.es',
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'email' => 'student@epycus.es',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($student);
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
    }

    public function test_non_admin_user_cannot_access_admin_panel(): void
    {
        $student = UserModel::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('admin.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_export_csv_datasets(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);

        $types = ['participants', 'habits_pomodoro', 'telemetry', 'epa_responses'];

        foreach ($types as $type) {
            $response = $this->actingAs($admin)->get(route('admin.export', $type));

            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        }
    }
}
