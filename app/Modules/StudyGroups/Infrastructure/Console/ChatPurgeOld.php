<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Infrastructure\Console;

use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use Illuminate\Console\Command;

final class ChatPurgeOld extends Command
{
    protected $signature = 'chat:purge-old';

    protected $description = 'Purga mensajes de chat con más de 7 días';

    public function handle(StudySessionRepositoryInterface $repository): int
    {
        $deleted = $repository->purgeOldMessages(7);
        $this->info("Purged {$deleted} old chat messages.");

        return self::SUCCESS;
    }
}
