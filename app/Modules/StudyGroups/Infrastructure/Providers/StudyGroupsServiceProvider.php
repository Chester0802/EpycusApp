<?php

declare(strict_types=1);

namespace App\Modules\StudyGroups\Infrastructure\Providers;

use App\Modules\StudyGroups\Application\UseCases\AdvancePhaseUseCase;
use App\Modules\StudyGroups\Application\UseCases\ConfigureRoomUseCase;
use App\Modules\StudyGroups\Application\UseCases\CreateStudySessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\JoinSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\LeaveSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\PollSessionUseCase;
use App\Modules\StudyGroups\Application\UseCases\SendMessageUseCase;
use App\Modules\StudyGroups\Domain\Contracts\StudySessionRepositoryInterface;
use App\Modules\StudyGroups\Infrastructure\Console\ChatPurgeOld;
use App\Modules\StudyGroups\Infrastructure\Repositories\EloquentStudySessionRepository;
use Illuminate\Support\ServiceProvider;

final class StudyGroupsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            StudySessionRepositoryInterface::class,
            EloquentStudySessionRepository::class,
        );

        $this->app->tag([
            CreateStudySessionUseCase::class,
            JoinSessionUseCase::class,
            LeaveSessionUseCase::class,
            SendMessageUseCase::class,
            PollSessionUseCase::class,
            StartGroupPomodoroUseCase::class,
            ConfigureRoomUseCase::class,
            AdvancePhaseUseCase::class,
        ], 'study_groups_use_cases');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ChatPurgeOld::class,
            ]);
        }
    }
}
