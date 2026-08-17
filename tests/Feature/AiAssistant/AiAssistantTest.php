<?php

declare(strict_types=1);

namespace Tests\Feature\AiAssistant;

use App\Modules\AiAssistant\Infrastructure\Models\AiQuotaModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_ai_assistant_page(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('ai-assistant.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AiAssistant/Index')
            ->has('quota')
        );
    }

    public function test_crisis_detection_returns_containment_message(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ai-assistant.consult'), [
            'message' => 'Me siento muy mal y he pensado en suicidarme',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_crisis' => true,
            ],
        ]);

        $this->assertStringContainsString('113', $response->json('data.response'));
    }

    public function test_user_can_send_consultation_successfully(): void
    {
        $user = UserModel::factory()->create();

        Http::fake([
            'https://api.deepseek.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '¡Hola! Te recomiendo dividir tu tarea en 3 pasos Pomodoro.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.consult'), [
            'message' => '¿Cómo me organizo para estudiar examen de cálculo?',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_crisis' => false,
                'response' => '¡Hola! Te recomiendo dividir tu tarea en 3 pasos Pomodoro.',
            ],
        ]);

        $this->assertDatabaseHas('ai_quotas', [
            'user_id' => $user->id,
            'used_count' => 1,
        ]);
    }

    public function test_exhausted_quota_blocks_consultation(): void
    {
        $user = UserModel::factory()->create();
        $today = Carbon::now()->toDateString();

        AiQuotaModel::create([
            'user_id' => $user->id,
            'date' => $today,
            'used_count' => 5,
        ]);

        $response = $this->actingAs($user)->postJson(route('ai-assistant.consult'), [
            'message' => '¿Qué otro hábito me sugieres?',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_ai_context_builder_includes_active_missions_and_eisenhower_quadrants(): void
    {
        $user = UserModel::factory()->create();

        // Create Q1 mission
        \App\Modules\Missions\Infrastructure\Models\MissionModel::create([
            'user_id' => $user->id,
            'title' => 'Entrega de Proyecto Final',
            'difficulty' => 'hard',
            'priority' => 'alta',
            'eisenhower_quadrant' => 'q1',
        ]);

        // Create Q2 mission
        \App\Modules\Missions\Infrastructure\Models\MissionModel::create([
            'user_id' => $user->id,
            'title' => 'Lectura de Tesis',
            'difficulty' => 'medium',
            'priority' => 'normal',
            'eisenhower_quadrant' => 'q2',
        ]);

        $contextBuilder = app(\App\Modules\AiAssistant\Application\Services\AiContextBuilderService::class);
        $context = $contextBuilder->buildContext($user->id);

        $this->assertStringContainsString('Misiones activas: 2', $context);
        $this->assertStringContainsString('En Q1/Crisis: 1', $context);
        $this->assertStringContainsString('En Q2/Estratégico: 1', $context);
        $this->assertStringContainsString('Entrega de Proyecto Final', $context);
    }
}

