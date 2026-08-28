<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Infrastructure\Repositories;

use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Infrastructure\Models\ChatMessageModel;
use App\Modules\StudyGroups\Infrastructure\Models\SessionParticipantModel;
use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentStudySessionRepository implements StudySessionRepositoryInterface
{
    public function findById(int $id): ?StudySessionModel
    {
        return StudySessionModel::find($id);
    }

    public function findOpenSessions(int $userId): Collection
    {
        return StudySessionModel::where('state', 'open')
            ->whereRaw('(SELECT COUNT(*) FROM session_participants WHERE session_id = study_sessions.id) < study_sessions.max_seats')
            ->whereNotIn('id', function ($q) use ($userId) {
                $q->select('session_id')
                    ->from('session_participants')
                    ->where('user_id', $userId)
                    ->whereNull('left_at');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findActiveSessionsForUser(int $userId): Collection
    {
        return StudySessionModel::whereIn('id', function ($q) use ($userId) {
            $q->select('session_id')
                ->from('session_participants')
                ->where('user_id', $userId)
                ->whereNull('left_at');
        })->where('state', '!=', 'closed')->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): StudySessionModel
    {
        return StudySessionModel::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(StudySessionModel $session, array $data): StudySessionModel
    {
        $session->update($data);

        return $session->fresh();
    }

    public function participantCount(int $sessionId): int
    {
        return SessionParticipantModel::where('session_id', $sessionId)
            ->whereNull('left_at')
            ->count();
    }

    public function isUserInSession(int $sessionId, int $userId): bool
    {
        return SessionParticipantModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    public function addParticipant(int $sessionId, int $userId, string $joinedAt): void
    {
        SessionParticipantModel::updateOrCreate(
            ['session_id' => $sessionId, 'user_id' => $userId],
            ['joined_at' => $joinedAt, 'left_at' => null]
        );
    }

    public function removeParticipant(int $sessionId, int $userId, string $leftAt): void
    {
        SessionParticipantModel::where('session_id', $sessionId)
            ->where('user_id', $userId)
            ->whereNull('left_at')
            ->update(['left_at' => $leftAt]);
    }

    public function getParticipants(int $sessionId): Collection
    {
        return DB::table('session_participants')
            ->join('users', 'users.id', '=', 'session_participants.user_id')
            ->where('session_participants.session_id', $sessionId)
            ->whereNull('session_participants.left_at')
            ->select(
                'users.id',
                'users.alias',
                'users.avatar_style',
                'users.avatar_gender',
                'users.avatar_options',
                'session_participants.joined_at',
            )
            ->get();
    }

    public function saveMessage(int $sessionId, int $userId, string $body, string $createdAt): ChatMessageModel
    {
        return ChatMessageModel::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'body' => $body,
            'created_at' => $createdAt,
        ]);
    }

    public function getMessagesSince(int $sessionId, int $lastMessageId): Collection
    {
        return ChatMessageModel::with('user:id,alias')
            ->where('session_id', $sessionId)
            ->where('id', '>', $lastMessageId)
            ->orderBy('id')
            ->get();
    }

    public function getRecentMessages(int $sessionId, int $limit = 50): Collection
    {
        return ChatMessageModel::with('user:id,alias')
            ->where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();
    }

    public function purgeOldMessages(int $days = 7): int
    {
        return ChatMessageModel::where('created_at', '<', now()->subDays($days))->delete();
    }
}
