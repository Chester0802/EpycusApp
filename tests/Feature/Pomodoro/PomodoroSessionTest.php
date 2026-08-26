<?php

declare(strict_types=1);

namespace Tests\Feature\Pomodoro;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Pomodoro\Application\UseCases\AbandonPomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\CompletePomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\GetActiveSessionUseCase;
use App\Modules\Pomodoro\Application\UseCases\PausePomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\ResumePomodoroUseCase;
use App\Modules\Pomodoro\Application\UseCases\StartPomodoroUseCase;
use App\Modules\Pomodoro\Domain\Exceptions\ActiveSessionAlreadyExistsException;
use App\Modules\Pomodoro\Domain\Exceptions\PomodoroDurationTooShortException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre en particular el caso que pidió el usuario explícitamente: "le
 * pongo en iniciar, y luego salgo del navegador" — ver
 * test_a_session_left_running_past_its_time_auto_completes_on_next_check.
 */
final class PomodoroSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_user_can_start_a_session(): void
    {
        $user = UserModel::factory()->create();

        $session = app(StartPomodoroUseCase::class)->execute($user->id, 25);

        $this->assertSame('running', $session->status);
        $this->assertSame(25, $session->planned_minutes);
    }

    public function test_cannot_start_a_second_session_while_one_is_genuinely_active(): void
    {
        $user = UserModel::factory()->create();
        app(StartPomodoroUseCase::class)->execute($user->id, 25);

        $this->expectException(ActiveSessionAlreadyExistsException::class);
        app(StartPomodoroUseCase::class)->execute($user->id, 25);
    }

    public function test_completing_before_95_percent_of_planned_time_is_rejected(): void
    {
        $user = UserModel::factory()->create();
        $now = Carbon::parse('2026-09-07 09:00:00');
        Carbon::setTestNow($now);

        $session = app(StartPomodoroUseCase::class)->execute($user->id, 25);

        Carbon::setTestNow($now->addMinutes(20)); // 80% < 95%

        $this->expectException(PomodoroDurationTooShortException::class);
        app(CompletePomodoroUseCase::class)->execute($session->id, $user->id);
    }

    public function test_completing_at_or_after_95_percent_awards_xp_and_records_full_focus_minutes(): void
    {
        $user = UserModel::factory()->create();
        $now = Carbon::parse('2026-09-07 09:00:00');
        Carbon::setTestNow($now);

        $session = app(StartPomodoroUseCase::class)->execute($user->id, 25);

        Carbon::setTestNow($now->addMinutes(24)); // 96%
        $completed = app(CompletePomodoroUseCase::class)->execute($session->id, $user->id);

        $this->assertSame('completed', $completed->status);
        $this->assertSame(24, $completed->focus_minutes); // 24 min reales, no se infla a los 25 planificados
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'total_xp' => 15]);
    }

    public function test_a_session_left_running_past_its_time_auto_completes_on_next_check(): void
    {
        // El caso central pedido por el usuario: inicia un Pomodoro y
        // "sale del navegador" — nada vuelve a tocar esta sesión hasta
        // que, mucho después, la aplicación vuelve a preguntar por la
        // sesión activa (como hace PomodoroController::index() en cada
        // visita a /pomodoro).
        $user = UserModel::factory()->create();
        $now = Carbon::parse('2026-09-07 09:00:00');
        Carbon::setTestNow($now);
        app(StartPomodoroUseCase::class)->execute($user->id, 25);

        Carbon::setTestNow($now->addHours(6)); // vuelve mucho después
        $result = app(GetActiveSessionUseCase::class)->execute($user->id);

        $this->assertNull($result->session);
        $this->assertSame(25, $result->autoCompletedFocusMinutes);
        $this->assertDatabaseHas('pomodoro_sessions', ['user_id' => $user->id, 'status' => 'completed']);
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'total_xp' => 15]);

        // Y ahora sí puede iniciar una sesión nueva sin chocar con la vieja.
        $new = app(StartPomodoroUseCase::class)->execute($user->id, 25);
        $this->assertSame('running', $new->status);
    }

    public function test_pausing_excludes_paused_time_from_elapsed_and_blocks_early_completion(): void
    {
        // OJO para quien toque esto después: Carbon (mutable, no
        // CarbonImmutable) es el que usa todo el módulo Pomodoro — por
        // eso acá se usa `->copy()` antes de cada `addX()` en vez de
        // encadenar sobre `$base` directo, que lo mutaría permanentemente
        // y arruinaría los offsets de los pasos siguientes.
        $user = UserModel::factory()->create();
        $base = Carbon::parse('2026-09-07 09:00:00');
        Carbon::setTestNow($base->copy());
        $session = app(StartPomodoroUseCase::class)->execute($user->id, 25);

        Carbon::setTestNow($base->copy()->addMinutes(10)); // 10 min activos
        app(PausePomodoroUseCase::class)->execute($session->id, $user->id);

        Carbon::setTestNow($base->copy()->addMinutes(10)->addHours(3)); // pausado 3 horas — no debería contar
        app(ResumePomodoroUseCase::class)->execute($session->id, $user->id);

        // Recién pasaron 10 min activos reales; todavía no alcanza el 95%.
        try {
            app(CompletePomodoroUseCase::class)->execute($session->id, $user->id);
            $this->fail('Se esperaba que completar fallara — todavía no pasaron los 25 min activos.');
        } catch (PomodoroDurationTooShortException) {
            // esperado
        }

        Carbon::setTestNow($base->copy()->addMinutes(10)->addHours(3)->addMinutes(15)); // +15 min activos más = 25 reales
        $completed = app(CompletePomodoroUseCase::class)->execute($session->id, $user->id);
        $this->assertSame('completed', $completed->status);
    }

    public function test_abandoning_records_partial_focus_minutes_and_awards_no_xp(): void
    {
        $user = UserModel::factory()->create();
        $now = Carbon::parse('2026-09-07 09:00:00');
        Carbon::setTestNow($now);
        $session = app(StartPomodoroUseCase::class)->execute($user->id, 25);

        Carbon::setTestNow($now->addMinutes(8));
        $abandoned = app(AbandonPomodoroUseCase::class)->execute($session->id, $user->id);

        $this->assertSame('abandoned', $abandoned->status);
        $this->assertSame(8, $abandoned->focus_minutes);
        $this->assertDatabaseMissing('user_progress', ['user_id' => $user->id, 'total_xp' => 15]);
    }

    public function test_daily_cap_of_eight_pomodoro_sessions(): void
    {
        $user = UserModel::factory()->create();
        $base = Carbon::parse('2026-09-07 06:00:00');

        for ($i = 0; $i < 9; $i++) {
            Carbon::setTestNow($base->copy()->addHours($i));
            $session = app(StartPomodoroUseCase::class)->execute($user->id, 15);
            Carbon::setTestNow($base->copy()->addHours($i)->addMinutes(15));
            app(CompletePomodoroUseCase::class)->execute($session->id, $user->id);
        }

        // 8 sesiones válidas x 15 XP = 120; la 9na queda capada en 0.
        $this->assertDatabaseHas('user_progress', ['user_id' => $user->id, 'total_xp' => 120]);
    }

    public function test_user_can_view_pomodoro_index_page(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->get(route('pomodoro.index'));

        $response->assertStatus(200);
    }

    public function test_pomodoro_http_lifecycle(): void
    {
        $user = UserModel::factory()->create();
        $now = Carbon::parse('2026-09-07 10:00:00');
        Carbon::setTestNow($now);

        // Start
        $response = $this->actingAs($user)->postJson(route('pomodoro.start'), [
            'planned_minutes' => 25,
        ]);
        $response->assertStatus(200);
        $sessionId = $response->json('id');
        $this->assertNotNull($sessionId);

        // Pause
        $response = $this->actingAs($user)->postJson(route('pomodoro.pause', ['id' => $sessionId]));
        $response->assertStatus(200)->assertJson(['status' => 'paused']);

        // Resume
        $response = $this->actingAs($user)->postJson(route('pomodoro.resume', ['id' => $sessionId]));
        $response->assertStatus(200)->assertJson(['status' => 'running']);

        // Abandon
        $response = $this->actingAs($user)->postJson(route('pomodoro.abandon', ['id' => $sessionId]));
        $response->assertStatus(200)->assertJson(['status' => 'abandoned']);
    }

    public function test_pomodoro_session_associates_with_mission(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user)->post(route('missions.store'), [
            'title' => 'Misión con Pomodoro',
            'difficulty' => 'easy',
            'priority' => 'normal',
        ]);
        $mission = \App\Modules\Missions\Infrastructure\Models\MissionModel::where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->postJson(route('pomodoro.start'), [
            'planned_minutes' => 25,
            'mission_id' => $mission->id,
        ]);

        $response->assertStatus(200);
        $sessionId = $response->json('id');
        $this->assertDatabaseHas('pomodoro_sessions', [
            'id' => $sessionId,
            'user_id' => $user->id,
            'mission_id' => $mission->id,
        ]);
    }
}
