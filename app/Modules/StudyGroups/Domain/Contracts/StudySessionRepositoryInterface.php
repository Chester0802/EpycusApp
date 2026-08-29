<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Domain\Contracts;

use App\Modules\StudyGroups\Infrastructure\Models\ChatMessageModel;
use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;
use Illuminate\Support\Collection;

interface StudySessionRepositoryInterface
{
    public function findById(int $id): ?StudySessionModel;

    /** @return Collection<int, StudySessionModel> */
    public function findOpenSessions(int $userId): Collection;

    /** @return Collection<int, StudySessionModel> */
    public function findActiveSessionsForUser(int $userId): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): StudySessionModel;

    /** @param array<string, mixed> $data */
    public function update(StudySessionModel $session, array $data): StudySessionModel;

    public function participantCount(int $sessionId): int;

    public function isUserInSession(int $sessionId, int $userId): bool;

    public function addParticipant(int $sessionId, int $userId, string $joinedAt): void;

    public function removeParticipant(int $sessionId, int $userId, string $leftAt): void;

    /** @return Collection<int, \stdClass> */
    public function getParticipants(int $sessionId): Collection;

    public function saveMessage(int $sessionId, int $userId, string $body, string $createdAt): ChatMessageModel;

    /** @return Collection<int, ChatMessageModel> */
    public function getMessagesSince(int $sessionId, int $lastMessageId): Collection;

    /** @return Collection<int, ChatMessageModel> */
    public function getRecentMessages(int $sessionId, int $limit = 50): Collection;

    public function purgeOldMessages(int $days = 7): int;
}
