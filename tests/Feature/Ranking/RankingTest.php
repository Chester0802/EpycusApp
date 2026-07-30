<?php

declare(strict_types=1);

namespace Tests\Feature\Ranking;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_ranking_page(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('ranking.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Ranking/Index')
            ->has('ranking')
            ->has('ownPosition')
        );
    }
}
