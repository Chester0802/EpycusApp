<?php

declare(strict_types=1);

namespace App\Modules\Admin\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Application\UseCases\GenerateDatasetCsvUseCase;
use App\Modules\Admin\Application\UseCases\GetAdminDashboardMetricsUseCase;
use App\Modules\Admin\Application\UseCases\GetAdminDropoutUseCase;
use App\Modules\Admin\Application\UseCases\GetAdminParticipantsUseCase;
use App\Modules\Admin\Application\UseCases\GetAdminSystemHealthUseCase;
use App\Modules\Admin\Application\UseCases\GetAdminTelemetryMetricsUseCase;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminController extends Controller
{
    public function __construct(
        private readonly GetAdminDashboardMetricsUseCase $getDashboardMetrics,
        private readonly GetAdminParticipantsUseCase $getParticipants,
        private readonly GetAdminDropoutUseCase $getDropout,
        private readonly GetAdminTelemetryMetricsUseCase $getTelemetryMetrics,
        private readonly GenerateDatasetCsvUseCase $generateCsv,
        private readonly GetAdminSystemHealthUseCase $getSystemHealth,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Index', [
            'metrics' => $this->getDashboardMetrics->execute(),
            'participants' => $this->getParticipants->execute(),
            'dropout' => $this->getDropout->execute(),
            'telemetry' => $this->getTelemetryMetrics->execute(),
            'health' => $this->getSystemHealth->execute(),
        ]);
    }

    public function exportCsv(string $type): StreamedResponse
    {
        return $this->generateCsv->execute($type);
    }
}
