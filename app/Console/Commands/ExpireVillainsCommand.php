<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Villains\Application\UseCases\ExpireVillainUseCase;
use Illuminate\Console\Command;

final class ExpireVillainsCommand extends Command
{
    protected $signature = 'villains:expire';

    protected $description = 'Expira los villanos activos cuya fecha ya pasó (docs/03-GAMIFICACION.md §6)';

    public function handle(ExpireVillainUseCase $useCase): int
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Lima'));
        $count = $useCase->execute($now);

        $this->info("{$count} villanos expirados.");

        return self::SUCCESS;
    }
}
