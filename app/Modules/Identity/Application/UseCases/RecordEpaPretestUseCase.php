<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\UseCases;

use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use App\Modules\Identity\Application\DTOs\RecordEpaPretestDTO;
use App\Modules\Identity\Infrastructure\Models\EpaResponseModel;
use App\Modules\Telemetry\Application\DTOs\RecordTelemetryEventDTO;
use App\Modules\Telemetry\Application\UseCases\RecordEventBatchUseCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class RecordEpaPretestUseCase
{
    public function __construct(
        private AwardXpUseCase $awardXp,
        private RecordEventBatchUseCase $recordTelemetry,
    ) {}

    public function execute(RecordEpaPretestDTO $dto): EpaResponseModel
    {
        // 1. Validar rangos de los ítems (1 a 5)
        $items = [
            'item_2' => $dto->item2,
            'item_5' => $dto->item5,
            'item_7' => $dto->item7,
            'item_10' => $dto->item10,
            'item_11' => $dto->item11,
            'item_12' => $dto->item12,
            'item_13' => $dto->item13,
            'item_14' => $dto->item14,
        ];

        foreach ($items as $name => $val) {
            if ($val < 1 || $val > 5) {
                throw new InvalidArgumentException("El valor de {$name} debe estar entre 1 y 5.");
            }
        }

        // 2. Verificar que no haya completado previamente el pretest (Idempotencia)
        $exists = EpaResponseModel::query()
            ->where('user_id', $dto->userId)
            ->where('phase', 'pretest')
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('Ya has completado el test inicial EPA.');
        }

        // 3. Obtener participant_code de la tabla participants
        $participantCode = (string) DB::table('participants')
            ->where('user_id', $dto->userId)
            ->value('participant_code');

        if (empty($participantCode)) {
            $participantCode = 'P-'.str_pad((string) $dto->userId, 6, '0', STR_PAD_LEFT);
        }

        $now = Carbon::now('America/Lima');
        $totalScore = $dto->totalScore();

        // 4. Guardar respuesta en la base de datos
        /** @var EpaResponseModel $response */
        $response = DB::transaction(function () use ($dto, $participantCode, $now, $totalScore) {
            return EpaResponseModel::query()->create([
                'user_id' => $dto->userId,
                'participant_code' => $participantCode,
                'phase' => 'pretest',
                'item_2' => $dto->item2,
                'item_5' => $dto->item5,
                'item_7' => $dto->item7,
                'item_10' => $dto->item10,
                'item_11' => $dto->item11,
                'item_12' => $dto->item12,
                'item_13' => $dto->item13,
                'item_14' => $dto->item14,
                'total_score' => $totalScore,
                'completed_at' => $now,
            ]);
        });

        // 5. Otorgar +50 XP por completar la evaluación inicial
        try {
            $this->awardXp->execute(
                userId: $dto->userId,
                sourceType: 'epa_pretest',
                sourceId: (int) $response->id,
                baseXp: 50,
                dailyCap: 1000,
                countsTowardStreak: false,
            );
        } catch (\Throwable) {
            // No bloquear la respuesta de la evaluación si la experiencia falla
        }

        // 6. Registrar evento de telemetría
        try {
            $telemetryDto = new RecordTelemetryEventDTO(
                userId: $dto->userId,
                eventName: 'epa.evaluated',
                eventCategory: 'psychometrics',
                payload: [
                    'phase' => 'pretest',
                    'total_score' => $totalScore,
                ],
                sessionUuid: null,
                occurredAt: $now->toIso8601String(),
                source: 'web'
            );

            $this->recordTelemetry->execute([$telemetryDto]);
        } catch (\Throwable) {
            // Resistencia de telemetría
        }

        return $response;
    }
}
