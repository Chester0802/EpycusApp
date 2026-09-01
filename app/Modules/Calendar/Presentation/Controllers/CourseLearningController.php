<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Application\UseCases\GenerateKnowledgeGraphUseCase;
use App\Modules\Calendar\Infrastructure\Models\UserKnowledgeGraphModel;
use App\Modules\Missions\Infrastructure\Models\MissionModel;
use App\Modules\Missions\Infrastructure\Models\SubtaskModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CourseLearningController extends Controller
{
    public function __construct(
        private readonly GenerateKnowledgeGraphUseCase $generateGraphUseCase,
    ) {}

    public function generateGraph(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) $request->user()->id;

        try {
            $data = $this->generateGraphUseCase->execute($userId, $courseId);
            
            // The use case returns data for the entire graph, but we only want to return the updated course part
            // Or we can just return success and let the frontend reload the page/inertia props
            return response()->json([
                'success' => true,
                'message' => 'Grafo generado exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function updateChunkMastery(Request $request, int $courseId): JsonResponse
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
            if (($n['id'] ?? '') === $validated['node_id'] && ($n['course_id'] ?? null) == $courseId) {
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

    public function generateMission(Request $request, int $courseId): JsonResponse
    {
        $validated = $request->validate([
            'node_label' => 'required|string|max:120',
            'summary' => 'nullable|string',
        ]);

        $userId = (int) $request->user()->id;

        $mission = MissionModel::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'title' => "🎯 Domina: {$validated['node_label']}",
            'description' => "Refuerzo activo del chunk de aprendizaje: {$validated['summary']}. Realiza 1 bloque Pomodoro enfocado para consolidar este tema.",
            'difficulty' => 'medium',
            'priority' => 'high',
            'eisenhower_quadrant' => 'q1',
            'due_date' => Carbon::now()->toDateString(),
            'xp_awarded' => 45,
            'mission_type' => 'academic'
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
