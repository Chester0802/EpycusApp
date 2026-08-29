<?php

declare(strict_types=1);

namespace App\Modules\Wellbeing\Domain\Events;

use App\Modules\Wellbeing\Infrastructure\Models\JournalEntryModel;

final class JournalEntryEdited
{
    public function __construct(
        public JournalEntryModel $entry,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
