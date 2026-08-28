<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Presentation\Controllers;

use App\Modules\StudyGroups\Application\DTOs\CreateSessionDTO;
use App\Modules\StudyGroups\Application\DTOs\SendMessageDTO;
use App\Modules\StudyGroups\Application\Mappers\StudyGroupMapper;
use App\Modules\StudyGroups\Application\UseCases\AdvancePhaseUseCase;
use App\Modules\StudyGroups\Application\UseCases\ConfigureRoomUseCase;
use App\Modules\StudyGroups\Application\UseCases\CreateStudySessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\JoinSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\LeaveSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\PollSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\SendMessageUseCase;
use App\Modules\StudyGroups\Application\UseCases\StartGroupPomodoroUseCase;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Infrastructure\Models\ChatMessageModel;
use App\Shared\Domain\Exceptions\ConflictException;
use App\Shared\Domain\Exceptions\DomainException;
use App\Shared\Domain\Exceptions\ForbiddenException;
use App\Shared\Domain\Exceptions\NotFoundException;
use App\Shared\Domain\Exceptions\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Inertia\Response;

final class StudyGroupController extends Controller
{
    public function __construct(
        private CreateStudySessionUseCase $createSession,
        private JoinSessionUseCase $joinSession,
        private LeaveSessionUseCase $leaveSession,
        private SendMessageUseCase $sendMessage,
        private PollSessionUseCase $pollSession,
        private StartGroupPomodoroUseCase $startGroupPomodoro,
        private ConfigureRoomUseCase $configureRoom,
        private AdvancePhaseUseCase $advancePhase,
        private StudySessionRepositoryInterface $repository,
        private StudyGroupMapper $mapper,
    ) {}

    public function index(): Response
    {
        $userId = (int) auth()->id();

        // Obtener solo las salas maestras (id 1 y 2)
        $masterSessions = \App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel::whereIn('id', [1, 2])->get();

        $formattedSessions = $masterSessions->map(function ($session) {
            $participantsCount = $this->repository->getParticipants($session->id)->count();
            return [
                'id' => $session->id,
                'name' => $session->name,
                'state' => $session->state,
                'participants_count' => $participantsCount,
                'max_seats' => $session->max_seats,
                // Hardcoding image for UI presentation
                'image' => $session->id === 1 ? '/assets/images/rooms/cafeteria.webp' : '/assets/images/rooms/biblioteca.webp'
            ];
        })->values()->all();

        return inertia('StudyGroups/Index', [
            'sessions' => $formattedSessions,
            'activeSessions' => $this->getActiveSessionsData($userId),
        ]);
    }

    public function show(int $id): Response|RedirectResponse
    {
        $userId = (int) auth()->id();
        $session = $this->repository->findById($id);

        if (! $session || $session->state === 'closed') {
            return redirect()->route('study-groups.index')
                ->with('error', 'La sesión no existe o ya se cerró.');
        }

        if (! $this->repository->isUserInSession($id, $userId)) {
            return redirect()->route('study-groups.index')
                ->with('error', 'No perteneces a esta sesión.');
        }

        $messages = $this->repository->getRecentMessages($id, 50);
        $participants = $this->repository->getParticipants($id);
        $lastMessageId = $messages->last()->id ?? 0;

        return inertia('StudyGroups/Show', [
            'session' => $this->mapper->toSessionArray($session),
            'messages' => $messages->map(fn (ChatMessageModel $m) => $this->mapper->toMessageArray($m))->values()->all(),
            'participants' => $this->mapParticipants($participants),
            'lastMessageId' => $lastMessageId,
            'userId' => $userId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'max_seats' => 'integer|min:2|max:5',
            'focus_minutes' => 'integer|min:5|max:120',
            'break_minutes' => 'integer|min:1|max:30',
            'cycles' => 'integer|min:1|max:20',
        ]);

        $this->createSession->execute(new CreateSessionDTO(
            hostId: (int) auth()->id(),
            name: $validated['name'],
            maxSeats: (int) ($validated['max_seats'] ?? 5),
            focusMinutes: (int) ($validated['focus_minutes'] ?? 25),
            breakMinutes: (int) ($validated['break_minutes'] ?? 5),
            cycles: (int) ($validated['cycles'] ?? 4),
        ));

        return redirect()->route('study-groups.index')
            ->with('success', 'Sesión de estudio creada. ¡Invita a tus compañeros!');
    }

    public function join(int $id): RedirectResponse
    {
        try {
            $this->joinSession->execute($id, (int) auth()->id());
        } catch (DomainException $e) {
            return redirect()->route('study-groups.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('study-groups.show', $id)
            ->with('success', 'Te has unido a la sesión.');
    }

    public function leave(int $id): RedirectResponse
    {
        try {
            $this->leaveSession->execute($id, (int) auth()->id());
        } catch (DomainException $e) {
            return redirect()->route('study-groups.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('study-groups.index')
            ->with('success', 'Has salido de la sesión.');
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:500',
        ]);

        try {
            $message = $this->sendMessage->execute(new SendMessageDTO(
                userId: (int) auth()->id(),
                sessionId: $id,
                body: $validated['body'],
            ));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], $this->domainCode($e));
        }

        return response()->json($message, 201);
    }

    public function poll(int $id, Request $request): JsonResponse
    {
        $lastMessageId = (int) $request->query('since', 0);

        if (! $this->repository->isUserInSession($id, (int) auth()->id())) {
            return response()->json(['messages' => [], 'participants' => []], 403);
        }

        $data = $this->pollSession->execute($id, $lastMessageId);

        return response()->json($data);
    }

    public function startPomodoro(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'planned_minutes' => 'required|integer|min:15|max:50',
        ]);

        try {
            $session = $this->startGroupPomodoro->execute(
                sessionId: $id,
                userId: (int) auth()->id(),
                plannedMinutes: (int) $validated['planned_minutes'],
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], $this->domainCode($e));
        }

        return response()->json([
            'id' => $session->id,
            'planned_minutes' => $session->planned_minutes,
            'started_at' => $session->started_at->toIso8601String(),
            'status' => $session->status,
        ]);
    }

    public function configure(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'focus_minutes' => 'required|integer|min:5|max:120',
            'break_minutes' => 'required|integer|min:1|max:30',
            'cycles' => 'required|integer|min:1|max:20',
        ]);

        try {
            $this->configureRoom->execute(
                sessionId: $id,
                userId: (int) auth()->id(),
                focusMinutes: (int) $validated['focus_minutes'],
                breakMinutes: (int) $validated['break_minutes'],
                cycles: (int) $validated['cycles'],
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], $this->domainCode($e));
        }

        return response()->json(['configured' => true]);
    }

    public function advance(int $id): JsonResponse
    {
        try {
            $result = $this->advancePhase->execute($id, (int) auth()->id());
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], $this->domainCode($e));
        }

        return response()->json($result);
    }

    private function domainCode(DomainException $e): int
    {
        return match (true) {
            $e instanceof NotFoundException => 404,
            $e instanceof ValidationException => 422,
            $e instanceof ForbiddenException => 403,
            $e instanceof ConflictException => 409,
            default => 400,
        };
    }

    /** @return array<string, mixed> */
    private function formatSessions(int $userId): array
    {
        $openSessions = $this->repository->findOpenSessions($userId);
        $userActive = $this->repository->findActiveSessionsForUser($userId);

        $format = fn ($session) => [
            'id' => $session->id,
            'name' => $session->name,
            'host_id' => $session->host_id,
            'max_seats' => $session->max_seats,
            'focus_minutes' => $session->focus_minutes,
            'break_minutes' => $session->break_minutes,
            'cycles' => $session->cycles,
            'current_cycle' => $session->current_cycle,
            'phase' => $session->phase,
            'phase_ends_at' => $session->phase_ends_at?->toIso8601String(),
            'state' => $session->state,
            'participant_count' => $this->repository->participantCount($session->id),
            'created_at' => $session->created_at?->toIso8601String(),
        ];

        return [
            'open' => $openSessions->map($format)->values()->all(),
            'active' => $userActive->map($format)->values()->all(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function getActiveSessionsData(int $userId): array
    {
        $active = $this->repository->findActiveSessionsForUser($userId);
        if ($active->isEmpty()) {
            return [];
        }

        return $active->map(fn ($session) => [
            'id' => $session->id,
            'name' => $session->name,
            'phase' => $session->phase,
            'current_cycle' => $session->current_cycle,
            'cycles' => $session->cycles,
            'phase_ends_at' => $session->phase_ends_at?->toIso8601String(),
            'state' => $session->state,
            'participant_count' => $this->repository->participantCount($session->id),
        ])->all();
    }

    /**
     * @param  Collection<int, \stdClass>  $participants
     * @return array<int, array<string, mixed>>
     */
    private function mapParticipants($participants): array
    {
        $result = [];
        foreach ($participants as $p) {
            $result[] = [
                'id' => $p->id,
                'alias' => $p->alias,
                'avatar_style' => $p->avatar_style ?? 'base',
                'avatar_gender' => $p->avatar_gender ?? 'm',
                'avatar_options' => isset($p->avatar_options) ? (is_array($p->avatar_options) ? $p->avatar_options : json_decode((string) $p->avatar_options, true)) : null,
                'joined_at' => $p->joined_at,
            ];
        }

        return $result;
    }

    public function move(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'pos_x' => 'required|numeric|min:0|max:100',
            'pos_y' => 'required|numeric|min:0|max:100',
        ]);

        $userId = (int) auth()->id();

        \Illuminate\Support\Facades\DB::table('session_participants')
            ->where('session_id', $id)
            ->where('user_id', $userId)
            ->update([
                'pos_x' => $validated['pos_x'],
                'pos_y' => $validated['pos_y'],
            ]);

        return response()->json(['success' => true]);
    }
}
