<?php

declare(strict_types=1);

namespace App\Modules\Learning\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AiAssistant\Application\UseCases\CheckQuotaUseCase;
use App\Modules\Calendar\Application\UseCases\GenerateKnowledgeGraphUseCase;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\UserKnowledgeGraphModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LearningController extends Controller
{
    public function __construct(
        private readonly CheckQuotaUseCase $checkQuota,
        private readonly GenerateKnowledgeGraphUseCase $generateGraphUseCase,
    ) {}

    public function index(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        // 1. Cursos y apuntes del usuario
        $courses = CourseModel::where('user_id', $userId)
            ->with(['note', 'sessions'])
            ->get();

        // 2. Grafo y Chunks existentes
        $graph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
        $nodes = $graph ? ($graph->nodes ?? []) : [];
        $edges = $graph ? ($graph->edges ?? []) : [];

        // Enriquecer nodos con métricas de chunking si faltan
        $totalMastery = 0;
        $weakChunksCount = 0;
        $totalNodes = count($nodes);

        foreach ($nodes as &$node) {
            // Dominio porcentual por defecto (entre 60% y 85% si es nuevo)
            if (! isset($node['mastery'])) {
                $node['mastery'] = 70;
            }
            if (! isset($node['importance'])) {
                $node['importance'] = 4;
            }
            if (! isset($node['last_reviewed_at'])) {
                $node['last_reviewed_at'] = 'Hace 2 días';
            }
            
            // Contar conexiones
            $nodeId = $node['id'] ?? '';
            $connCount = 0;
            foreach ($edges as $edge) {
                if (($edge['source'] ?? '') === $nodeId || ($edge['target'] ?? '') === $nodeId) {
                    $connCount++;
                }
            }
            $node['connections_count'] = $connCount;
            $node['questions_count'] = ! empty($node['quiz_question']) ? 1 : 0;

            $totalMastery += (int) $node['mastery'];
            if ((int) $node['mastery'] < 60) {
                $weakChunksCount++;
            }
        }
        unset($node);

        $avgMastery = $totalNodes > 0 ? (int) round($totalMastery / $totalNodes) : 0;

        // 3. Cuota de IA
        $quota = $this->checkQuota->execute($userId);

        $coursesData = [];
        foreach ($courses as $index => $c) {
            $notesText = [];
            $note = $c->note;
            if ($note && is_array($note->content) && isset($note->content['entries'])) {
                foreach ($note->content['entries'] as $entry) {
                    $blocksText = [];
                    if (isset($entry['blocks']) && is_array($entry['blocks'])) {
                        foreach ($entry['blocks'] as $block) {
                            $clean = trim(strip_tags((string) ($block['html'] ?? '')));
                            if ($clean !== '') {
                                $blocksText[] = $clean;
                            }
                        }
                    }
                    if (! empty($blocksText)) {
                        $notesText[] = implode("\n", $blocksText);
                    }
                }
            }

            $coursesData[] = [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $c->color,
                'has_notes' => ! empty($notesText),
                'notes_count' => count($notesText),
                'notes_content' => implode("\n---\n", $notesText),
            ];
        }

        return Inertia::render('Learning/Index', [
            'courses' => $coursesData,
            'graphData' => [
                'has_graph' => $graph !== null && ! empty($nodes),
                'nodes' => $nodes,
                'edges' => $edges,
                'stats' => $graph ? ($graph->stats ?? []) : [
                    'total_concepts' => count($nodes),
                    'total_connections' => count($edges),
                ],
                'last_generated_at' => $graph?->last_generated_at?->format('d/m/Y H:i'),
                'quota' => $quota,
            ],
            'learningStats' => [
                'avgMastery' => $avgMastery,
                'totalChunks' => $totalNodes,
                'weakChunksCount' => $weakChunksCount,
                'streakDays' => 4,
            ],
        ]);
    }

    public function updateChunkMastery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_id' => 'required|string',
            'delta' => 'required|integer',
        ]);

        $userId = (int) $request->user()->id;
        $graph = UserKnowledgeGraphModel::where('user_id', $userId)->first();

        if (! $graph) {
            return response()->json(['error' => 'No hay grafo activo'], 404);
        }

        $nodes = $graph->nodes ?? [];
        $updatedMastery = 70;

        foreach ($nodes as &$n) {
            if (($n['id'] ?? '') === $validated['node_id']) {
                $current = (int) ($n['mastery'] ?? 70);
                $new = max(10, min(100, $current + $validated['delta']));
                $n['mastery'] = $new;
                $n['last_reviewed_at'] = 'Hoy';
                $updatedMastery = $new;
                break;
            }
        }
        unset($n);

        $graph->nodes = $nodes;
        $graph->save();

        return response()->json([
            'success' => true,
            'node_id' => $validated['node_id'],
            'mastery' => $updatedMastery,
        ]);
    }

    public function generateMission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'node_label' => 'required|string|max:120',
            'course_id' => 'nullable|integer',
            'summary' => 'nullable|string',
        ]);

        $userId = (int) $request->user()->id;

        $mission = MissionModel::create([
            'user_id' => $userId,
            'course_id' => $validated['course_id'] ?? null,
            'title' => "🎯 Domina: {$validated['node_label']}",
            'description' => "Refuerzo activo del chunk de aprendizaje: {$validated['summary']}. Realiza 1 bloque Pomodoro enfocado para consolidar este tema.",
            'difficulty' => 'medium',
            'priority' => 'high',
            'eisenhower_quadrant' => 'q1',
            'due_date' => Carbon::now()->toDateString(),
            'xp_awarded' => 45,
        ]);

        SubtaskModel::create([
            'mission_id' => $mission->id,
            'title' => 'Repasar Idea Clave y Concepto en Zona de Aprendizaje',
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        SubtaskModel::create([
            'mission_id' => $mission->id,
            'title' => 'Completar 1 sesión de Pomodoro (15-25 min)',
            'is_completed' => false,
            'sort_order' => 2,
        ]);

        SubtaskModel::create([
            'mission_id' => $mission->id,
            'title' => 'Autoevaluar con Active Recall hasta alcanzar >80% dominio',
            'is_completed' => false,
            'sort_order' => 3,
        ]);

        return response()->json([
            'success' => true,
            'message' => "¡Misión de refuerzo creada para {$validated['node_label']}!",
            'mission_id' => $mission->id,
        ]);
    }
}
