<?php

declare(strict_types=1);

namespace Tests\Feature\StudyGroups;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\StudyGroups\Application\DTOs\CreateSessionDTO;
use App\Modules\StudyGroups\Application\DTOs\SendMessageDTO;
use App\Modules\StudyGroups\Application\UseCases\CreateStudySessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\JoinSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\LeaveSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\PollSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\SendMessageUseCase;
use App\Modules\StudyGroups\Application\UseCases\StartGroupPomodoroUseCase;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Domain\Exceptions\AlreadyInSessionException;
use App\Modules\StudyGroups\Domain\Exceptions\SessionFullException;
use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudyGroupSessionTest extends TestCase
{
    use RefreshDatabase;

    private function getCreatedSession(): StudySessionModel
    {
        return StudySessionModel::latest('id')->first();
    }

    public function test_user_can_create_a_session(): void
    {
        $user = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(
            hostId: $user->id,
            name: 'Estudio para parcial',
            maxSeats: 3,
        ));

        $this->assertDatabaseHas('study_sessions', [
            'host_id' => $user->id,
            'name' => 'Estudio para parcial',
            'max_seats' => 3,
            'state' => 'open',
        ]);
    }

    public function test_user_can_join_a_session(): void
    {
        $host = UserModel::factory()->create();
        $joiner = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(
            hostId: $host->id,
            name: 'Sesión de estudio',
            maxSeats: 5,
        ));

        $session = $this->getCreatedSession();

        app(JoinSessionUseCase::class)->execute($session->id, $joiner->id);

        $this->assertDatabaseHas('session_participants', [
            'session_id' => $session->id,
            'user_id' => $joiner->id,
        ]);
    }

    public function test_cannot_join_a_full_session(): void
    {
        $host = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(
            hostId: $host->id,
            name: 'Sesión llena',
            maxSeats: 2,
        ));

        $session = $this->getCreatedSession();

        $joiner1 = UserModel::factory()->create();
        app(JoinSessionUseCase::class)->execute($session->id, $joiner1->id);

        $this->expectException(SessionFullException::class);
        $joiner2 = UserModel::factory()->create();
        app(JoinSessionUseCase::class)->execute($session->id, $joiner2->id);
    }

    public function test_cannot_join_while_already_in_another_session(): void
    {
        $host = UserModel::factory()->create();
        $user = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Sesión A', maxSeats: 5));
        $sessionA = $this->getCreatedSession();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Sesión B', maxSeats: 5));
        $sessionB = $this->getCreatedSession();

        app(JoinSessionUseCase::class)->execute($sessionA->id, $user->id);

        $this->expectException(AlreadyInSessionException::class);
        app(JoinSessionUseCase::class)->execute($sessionB->id, $user->id);
    }

    public function test_user_can_leave_a_session(): void
    {
        $host = UserModel::factory()->create();
        $joiner = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Sesión', maxSeats: 5));
        $session = $this->getCreatedSession();

        app(JoinSessionUseCase::class)->execute($session->id, $joiner->id);
        app(LeaveSessionUseCase::class)->execute($session->id, $joiner->id);

        $this->assertDatabaseHas('session_participants', [
            'session_id' => $session->id,
            'user_id' => $joiner->id,
            'left_at' => now()->toDateTimeString(),
        ]);
        $this->assertFalse(
            app(StudySessionRepositoryInterface::class)->isUserInSession($session->id, $joiner->id)
        );
    }

    public function test_leaving_a_session_not_joined_is_noop(): void
    {
        $host = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Sesión', maxSeats: 5));
        $session = $this->getCreatedSession();

        $outsider = UserModel::factory()->create();

        app(LeaveSessionUseCase::class)->execute($session->id, $outsider->id);

        $this->assertDatabaseHas('study_sessions', ['id' => $session->id, 'state' => 'open']);
    }

    public function test_user_can_send_a_message(): void
    {
        $host = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Chat test', maxSeats: 5));
        $session = $this->getCreatedSession();

        $message = app(SendMessageUseCase::class)->execute(new SendMessageDTO(
            userId: $host->id,
            sessionId: $session->id,
            body: 'Hola, ¿estudian?',
        ));

        $this->assertDatabaseHas('chat_messages', [
            'session_id' => $session->id,
            'user_id' => $host->id,
            'body' => 'Hola, ¿estudian?',
        ]);
        $this->assertSame('Hola, ¿estudian?', $message['body']);
    }

    public function test_user_can_start_a_group_pomodoro(): void
    {
        $host = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Pomodoro grupal', maxSeats: 5));
        $session = $this->getCreatedSession();

        $pomodoro = app(StartGroupPomodoroUseCase::class)->execute(
            sessionId: $session->id,
            userId: $host->id,
            plannedMinutes: 25,
        );

        $this->assertSame('running', $pomodoro->status);
        $this->assertSame(25, $pomodoro->planned_minutes);
        $this->assertSame($session->id, $pomodoro->study_group_session_id);
    }

    public function test_poll_returns_active_pomodoro(): void
    {
        $host = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'Poll pomodoro', maxSeats: 5));
        $session = $this->getCreatedSession();

        app(StartGroupPomodoroUseCase::class)->execute(
            sessionId: $session->id,
            userId: $host->id,
            plannedMinutes: 25,
        );

        $result = app(PollSessionUseCase::class)->execute($session->id, 0);

        $this->assertArrayHasKey('active_pomodoro', $result);
        $this->assertSame(25, $result['active_pomodoro']['planned_minutes']);
        $this->assertSame('running', $result['active_pomodoro']['status']);
    }

    public function test_create_session_via_http(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user)->post(route('study-groups.store'), [
            'name' => 'HTTP test',
            'max_seats' => 4,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('study_sessions', [
            'host_id' => $user->id,
            'name' => 'HTTP test',
            'max_seats' => 4,
        ]);
    }

    public function test_join_session_via_http(): void
    {
        $host = UserModel::factory()->create();
        $joiner = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'HTTP join', maxSeats: 5));
        $session = $this->getCreatedSession();

        $response = $this->actingAs($joiner)->post(route('study-groups.join', $session->id));
        $response->assertRedirect();

        $this->assertDatabaseHas('session_participants', [
            'session_id' => $session->id,
            'user_id' => $joiner->id,
        ]);
    }

    public function test_send_message_via_http(): void
    {
        $host = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $host->id, name: 'HTTP msg', maxSeats: 5));
        $session = $this->getCreatedSession();

        $response = $this->actingAs($host)->postJson(
            route('study-groups.messages', $session->id),
            ['body' => 'Mensaje HTTP'],
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('chat_messages', [
            'session_id' => $session->id,
            'user_id' => $host->id,
            'body' => 'Mensaje HTTP',
        ]);
    }

    public function test_start_group_pomodoro_via_http(): void
    {
        $user = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $user->id, name: 'HTTP pomodoro', maxSeats: 5));
        $session = $this->getCreatedSession();

        $response = $this->actingAs($user)->postJson(
            route('study-groups.pomodoro.start', $session->id),
            ['planned_minutes' => 25],
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'planned_minutes', 'status']);
        $response->assertJson(['planned_minutes' => 25, 'status' => 'running']);

        $this->assertDatabaseHas('pomodoro_sessions', [
            'user_id' => $user->id,
            'study_group_session_id' => $session->id,
            'status' => 'running',
            'planned_minutes' => 25,
        ]);
    }

    public function test_poll_endpoint_returns_messages_and_active_pomodoro(): void
    {
        $user = UserModel::factory()->create();

        app(CreateStudySessionUseCase::class)->execute(new CreateSessionDTO(hostId: $user->id, name: 'Poll test', maxSeats: 5));
        $session = $this->getCreatedSession();

        app(SendMessageUseCase::class)->execute(new SendMessageDTO(
            userId: $user->id,
            sessionId: $session->id,
            body: 'Primer mensaje',
        ));

        app(StartGroupPomodoroUseCase::class)->execute(
            sessionId: $session->id,
            userId: $user->id,
            plannedMinutes: 30,
        );

        $response = $this->actingAs($user)->getJson(
            route('study-groups.poll', $session->id).'?since=0'
        );

        $response->assertStatus(200);
        $response->assertJsonStructure(['messages', 'participants', 'active_pomodoro']);
        $response->assertJsonCount(1, 'messages');
        $response->assertJsonPath('active_pomodoro.planned_minutes', 30);
    }
}
