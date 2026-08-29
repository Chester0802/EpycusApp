<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Services;

use App\Modules\Identity\Infrastructure\Models\EpaResponseModel;
use Carbon\Carbon;

final class EpaDiagnosticPresenter
{
    /**
     * Transforma una respuesta EPA en un arreglo enriquecido con diagnóstico e interpretación psicométrica.
     *
     * @param EpaResponseModel|object|null $response
     * @return array<string, mixed>|null
     */
    public static function present(mixed $response): ?array
    {
        if (! $response) {
            return null;
        }

        $totalScore = (int) ($response->total_score ?? 0);
        $completedAt = $response->completed_at ?? null;

        $completedAtFormatted = 'Fecha no registrada';
        if ($completedAt) {
            try {
                $completedAtFormatted = Carbon::parse($completedAt)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM, YYYY');
            } catch (\Throwable) {
                $completedAtFormatted = (string) $completedAt;
            }
        }

        $interpretation = self::interpretScore($totalScore);

        return [
            'total_score' => $totalScore,
            'max_score' => 32,
            'min_score' => 8,
            'percentage' => (int) round(($totalScore / 32) * 100),
            'phase' => $response->phase ?? 'pretest',
            'day_label' => ($response->phase ?? 'pretest') === 'pretest' ? 'Día 1' : 'Día 66',
            'milestone_title' => ($response->phase ?? 'pretest') === 'pretest'
                ? 'Punto de partida del Reto de los 66 Días'
                : 'Evaluación de Cierre del Reto',
            'completed_at' => $completedAt ? Carbon::parse($completedAt)->toIso8601String() : null,
            'completed_at_formatted' => $completedAtFormatted,
            'level' => $interpretation['level'],
            'level_label' => $interpretation['level_label'],
            'level_color' => $interpretation['level_color'],
            'procrastination_level' => $interpretation['procrastination_level'],
            'description' => $interpretation['description'],
            'recommendations' => $interpretation['recommendations'],
            'items' => [
                'item_2' => (int) ($response->item_2 ?? 0),
                'item_5' => (int) ($response->item_5 ?? 0),
                'item_7' => (int) ($response->item_7 ?? 0),
                'item_10' => (int) ($response->item_10 ?? 0),
                'item_11' => (int) ($response->item_11 ?? 0),
                'item_12' => (int) ($response->item_12 ?? 0),
                'item_13' => (int) ($response->item_13 ?? 0),
                'item_14' => (int) ($response->item_14 ?? 0),
            ],
        ];
    }

    /**
     * @return array{level: string, level_label: string, level_color: string, procrastination_level: string, description: string, recommendations: array<int, string>}
     */
    public static function interpretScore(int $score): array
    {
        // Escala Likert de 4 puntos (1 a 4) x 8 ítems = 8 a 32 puntos
        if ($score >= 25) {
            return [
                'level' => 'high',
                'level_label' => 'Alta Autorregulación Académica',
                'level_color' => 'success',
                'procrastination_level' => 'Baja o Mínima Procrastinación (25-32 pts)',
                'description' => '¡Excelente nivel de autorregulación! Demuestras una notable constancia, preparación anticipada para tus evaluaciones y alta disciplina para sostener el ritmo de estudio.',
                'recommendations' => [
                    'Mantén tus bloques Pomodoro para no sobrecargarte y cuidar tu descanso.',
                    'Aprovecha el desglose de misiones para avanzar en proyectos de alta complejidad.',
                    'Usa el diario de bienestar para monitorear tu balance emocional y estrés en semanas de exámenes.',
                ],
            ];
        }

        if ($score >= 17) {
            return [
                'level' => 'moderate',
                'level_label' => 'Autorregulación Académica Moderada',
                'level_color' => 'warning',
                'procrastination_level' => 'Procrastinación Ocasional / Media (17-24 pts)',
                'description' => 'Cuentas con buen cumplimiento, pero en ocasiones postergas el inicio de tareas clave o repasos anticipados, acumulando picos de estrés de último minuto.',
                'recommendations' => [
                    'Comienza tus sesiones de estudio con un temporizador Pomodoro de 25 minutos con música Lo-Fi.',
                    'Divide las tareas pesadas en subtareas pequeñas para reducir la fricción inicial.',
                    'Establece un hábito diario de repaso para consolidar el ciclo de 66 días.',
                ],
            ];
        }

        return [
            'level' => 'low',
            'level_label' => 'Baja Autorregulación Académica',
            'level_color' => 'danger',
            'procrastination_level' => 'Alta Frecuencia de Procrastinación (8-16 pts)',
            'description' => 'Presentas una marcada tendencia a postergar tus actividades académicas y dificultades para sostener rutinas continuas. ¡Estás en el lugar ideal para transformar este hábito!',
            'recommendations' => [
                'Aplica la regla de los 5 minutos: inicia una misión sin la presión de terminarla toda de inmediato.',
                'Registra tus hábitos diarios y derrota al Villano de la Semana ganando experiencia.',
                'Revisa tu progreso diario en el módulo de Bienestar para celebrar pequeñas victorias.',
            ],
        ];
    }
}
