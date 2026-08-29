<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Villains\Application\DTOs\AssignVillainDTO;
use App\Modules\Villains\Application\UseCases\AssignWeeklyVillainUseCase;
use App\Modules\Villains\Domain\Contracts\VillainRepositoryInterface;
use App\Modules\Villains\Domain\ValueObjects\VillainCode;
use Illuminate\Console\Command;

final class AssignWeeklyVillainsCommand extends Command
{
    protected $signature = 'villains:assign-weekly';

    protected $description = 'Asigna un villano aleatorio a cada usuario activo para la semana (docs/03-GAMIFICACION.md §6)';

    public function handle(
        AssignWeeklyVillainUseCase $assignVillain,
        VillainRepositoryInterface $repository,
    ): int {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Lima'));
        $weekNumber = $repository->getWeekNumberForUser(0);

        if ($weekNumber < 1 || $weekNumber > 10) {
            $this->info('Fuera del período de intervención (07/09/2026 – 11/11/2026). No se asignan villanos.');

            return self::SUCCESS;
        }

        $codes = VillainCode::all();
        $count = 0;
        $users = UserModel::all();

        foreach ($users as $user) {
            $code = $codes[array_rand($codes)];

            $assignVillain->execute(new AssignVillainDTO(
                userId: $user->id,
                villainCode: $code,
                weekNumber: $weekNumber,
                now: $now,
            ));

            $count++;
        }

        $this->info("Villanos asignados a {$count} usuarios para la semana {$weekNumber}.");

        return self::SUCCESS;
    }
}
