<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Gamification\Application\UseCases\EvaluateStreaksUseCase;
use Illuminate\Console\Command;

/**
 * Corre una vez al día — ver el comentario completo de EvaluateStreaksUseCase
 * para el porqué no puede ser reactivo a la próxima visita del usuario.
 * Registrado en `routes/console.php`.
 */
final class EvaluateGamificationStreaksCommand extends Command
{
    protected $signature = 'gamification:evaluate-streaks';

    protected $description = 'Aplica gracia o rompe rachas de usuarios que no cumplieron ayer (docs/03-GAMIFICACION.md §5)';

    public function handle(EvaluateStreaksUseCase $useCase): int
    {
        $useCase->execute();

        $this->info('Rachas evaluadas.');

        return self::SUCCESS;
    }
}
