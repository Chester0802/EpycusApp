<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Missions\Domain\Events\MissionOverdue;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

final class MarkOverdueMissionsCommand extends Command
{
    protected $signature = 'missions:mark-overdue';

    protected $description = 'Marca como vencidas las misiones cuya fecha ya pasó';

    public function handle(Dispatcher $events): int
    {
        $today = Carbon::now()->toDateString();

        $overdue = MissionModel::query()
            ->whereNull('completed_at')
            ->whereNull('deleted_at')
            ->where('is_overdue', false)
            ->where('due_date', '<', $today)
            ->get();

        foreach ($overdue as $mission) {
            $mission->update(['is_overdue' => true]);

            $events->dispatch(new MissionOverdue(
                missionId: $mission->id,
                userId: $mission->user_id,
                title: $mission->title,
                dueDate: $mission->due_date->toDateString(),
                occurredAt: new \DateTimeImmutable,
            ));
        }

        $count = $overdue->count();
        $this->info("{$count} misiones marcadas como vencidas.");

        return self::SUCCESS;
    }
}
