<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Application\UseCases\GenerateKnowledgeGraphUseCase;
use App\Modules\Calendar\Application\UseCases\GetKnowledgeGraphUseCase;
use App\Modules\Calendar\Infrastructure\Models\UserKnowledgeGraphModel;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class KnowledgeGraphController extends Controller
{
    public function __construct(
        private readonly GetKnowledgeGraphUseCase $getKnowledgeGraph,
        private readonly GenerateKnowledgeGraphUseCase $generateKnowledgeGraph,
    ) {}

    public function show(): JsonResponse
    {
        $userId = (int) Auth::id();

        try {
            $data = $this->getKnowledgeGraph->execute($userId);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generate(): JsonResponse
    {
        $userId = (int) Auth::id();

        try {
            $data = $this->generateKnowledgeGraph->execute($userId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => '¡Grafo de conocimiento actualizado exitosamente con IA!',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function savePositions(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $positions = $request->input('positions', []);

        if (! is_array($positions) || empty($positions)) {
            return response()->json(['success' => true]);
        }

        try {
            $graph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
            if ($graph && is_array($graph->nodes)) {
                $posMap = [];
                foreach ($positions as $p) {
                    if (isset($p['id'], $p['x'], $p['y'])) {
                        $posMap[$p['id']] = ['x' => (float) $p['x'], 'y' => (float) $p['y']];
                    }
                }

                $updatedNodes = array_map(function ($node) use ($posMap) {
                    $id = $node['id'] ?? null;
                    if ($id && isset($posMap[$id])) {
                        $node['x'] = $posMap[$id]['x'];
                        $node['y'] = $posMap[$id]['y'];
                    }
                    return $node;
                }, $graph->nodes);

                $graph->update(['nodes' => $updatedNodes]);
            }

            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
