<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $student = UserModel::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('admin.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Index')
            ->has('metrics')
            ->has('participants')
            ->has('telemetry')
        );
    }

    public function test_admin_can_export_participants_csv(): void
    {
        $admin = UserModel::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.export', 'participants'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
