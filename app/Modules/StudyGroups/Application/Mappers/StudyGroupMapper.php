<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Application\Mappers;

use App\Modules\StudyGroups\Infrastructure\Models\ChatMessageModel;
use App\Modules\StudyGroups\Infrastructure\Models\StudySessionModel;

final class StudyGroupMapper
{
    /** @return array<string, mixed> */
    public function toSessionArray(StudySessionModel $session): array
    {
        return [
            'id' => $session->id,
            'host_id' => $session->host_id,
            'name' => $session->name,
            'max_seats' => $session->max_seats,
            'focus_minutes' => $session->focus_minutes,
            'break_minutes' => $session->break_minutes,
            'cycles' => $session->cycles,
            'current_cycle' => $session->current_cycle,
            'phase' => $session->phase,
            'phase_ends_at' => $session->phase_ends_at?->toIso8601String(),
            'state' => $session->state,
            'started_at' => $session->started_at?->toIso8601String(),
            'closed_at' => $session->closed_at?->toIso8601String(),
            'created_at' => $session->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    public function toMessageArray(ChatMessageModel $message): array
    {
        return [
            'id' => $message->id,
            'user_id' => $message->user_id,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
