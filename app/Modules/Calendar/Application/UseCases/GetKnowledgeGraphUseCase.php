<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Application\UseCases;

use App\Modules\AiAssistant\Application\UseCases\CheckQuotaUseCase;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\UserKnowledgeGraphModel;

final readonly class GetKnowledgeGraphUseCase
{
    public function __construct(
        private CheckQuotaUseCase $checkQuota,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(int $userId): array
    {
        $graph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
        $quota = $this->checkQuota->execute($userId);

        $courses = CourseModel::where('user_id', $userId)
            ->with(['note'])
            ->get();

        $palette = [
            '#6366f1', // Índigo Eléctrico
            '#10b981', // Esmeralda Brillante
            '#f59e0b', // Ámbar Neón
            '#ec4899', // Fucsia Neón
            '#06b6d4', // Cian Eléctrico
            '#a855f7', // Púrpura Intenso
            '#f97316', // Naranja Vivo
            '#3b82f6', // Azul Real
        ];

        $courseColorMap = [];
        $coursesList = [];

        foreach ($courses->values() as $index => $c) {
            $assignedColor = $this->resolveCourseColor($c->color, $index, $palette);
            $courseColorMap[$c->id] = $assignedColor;

            $coursesList[] = [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $assignedColor,
                'has_notes' => $c->note !== null,
            ];
        }

        if (! $graph || empty($graph->nodes)) {
            return [
                'has_graph' => false,
                'nodes' => [],
                'edges' => [],
                'stats' => [
                    'total_concepts' => 0,
                    'total_connections' => 0,
                    'courses_count' => count($coursesList),
                ],
                'last_generated_at' => null,
                'quota' => $quota,
                'courses' => $coursesList,
            ];
        }

        // Asignar a cada nodo el color vibrante de su curso
        $normalizedNodes = array_map(function ($node) use ($courseColorMap, $palette) {
            $cId = $node['course_id'] ?? null;
            if ($cId && isset($courseColorMap[$cId])) {
                $node['color'] = $courseColorMap[$cId];
            } else {
                $node['color'] = $palette[0];
            }

            return $node;
        }, $graph->nodes ?? []);

        return [
            'has_graph' => true,
            'nodes' => $normalizedNodes,
            'edges' => $graph->edges ?? [],
            'stats' => $graph->stats ?? [
                'total_concepts' => count($normalizedNodes),
                'total_connections' => count($graph->edges ?? []),
                'courses_count' => count($coursesList),
            ],
            'last_generated_at' => $graph->last_generated_at?->toIso8601String(),
            'quota' => $quota,
            'courses' => $coursesList,
        ];
    }

    /**
     * @param array<int, string> $palette
     */
    private function resolveCourseColor(?string $color, int $index, array $palette): string
    {
        $namedMap = [
            'primary' => $palette[$index % count($palette)],
            'accent' => '#a855f7',
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'secondary' => '#64748b',
        ];

        if (! $color || trim($color) === '') {
            return $palette[$index % count($palette)];
        }

        $trimmed = trim($color);

        if (str_starts_with($trimmed, '#') || str_starts_with($trimmed, 'rgb') || str_starts_with($trimmed, 'hsl')) {
            return $trimmed;
        }

        return $namedMap[strtolower($trimmed)] ?? $palette[$index % count($palette)];
    }
}
