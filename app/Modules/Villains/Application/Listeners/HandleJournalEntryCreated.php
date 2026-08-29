<?php

declare(strict_types=1);

namespace App\Modules\Villains\Application\Listeners;

use App\Modules\Villains\Application\DTOs\ApplyDamageDTO;
use App\Modules\Villains\Application\UseCases\ApplyDamageUseCase;
use App\Modules\Wellbeing\Domain\Events\JournalEntryCreated;

final class HandleJournalEntryCreated
{
    public function __construct(
        private ApplyDamageUseCase $applyDamage,
    ) {}

    public function handle(JournalEntryCreated $event): void
    {
        $this->applyDamage->execute(new ApplyDamageDTO(
            userId: $event->entry->user_id,
            sourceType: 'journal',
            occurredAt: $event->occurredAt,
        ));
    }
}
