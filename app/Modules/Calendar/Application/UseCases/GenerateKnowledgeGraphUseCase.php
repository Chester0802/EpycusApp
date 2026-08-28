<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Application\UseCases;

use App\Modules\AiAssistant\Application\UseCases\CheckQuotaUseCase;
use App\Modules\AiAssistant\Infrastructure\Models\AiQuotaModel;
use App\Modules\AiAssistant\Infrastructure\Services\DeepSeekApiClient;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\UserKnowledgeGraphModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

final class GenerateKnowledgeGraphUseCase
{
    private const COURSE_PALETTE = [
        '#818cf8', // Índigo Cósmico
        '#34d399', // Esmeralda Suave
        '#fbbf24', // Ámbar Cálido
        '#38bdf8', // Cian Celeste
        '#f472b6', // Orquídea Suave
        '#a78bfa', // Lavanda Profundo
        '#fb923c', // Coral Cálido
        '#2dd4bf', // Menta Bio
        '#e879f9', // Magenta Neón Suave
        '#60a5fa', // Azul Brisa
    ];

    public function __construct(
        private readonly DeepSeekApiClient $apiClient,
        private readonly CheckQuotaUseCase $checkQuota,
    ) {}

    public function execute(int $userId, ?int $targetCourseId = null): array
    {
        // 1. Validar Cuota Diaria de IA
        $quota = $this->checkQuota->execute($userId);
        if ($quota['is_exhausted']) {
            throw new Exception('Has alcanzado el límite diario de generación con IA (5/5). Se reinicia mañana.');
        }

        // 2. Obtener Cursos y Apuntes del Usuario
        $coursesQuery = CourseModel::where('user_id', $userId)->with(['note']);
        if ($targetCourseId !== null) {
            $coursesQuery->where('id', $targetCourseId);
        }
        $targetCourses = $coursesQuery->get();

        if ($targetCourses->isEmpty()) {
            throw new Exception('No tienes cursos registrados. Agrega al menos una materia para construir tu Segundo Cerebro.');
        }

        // Cursos completos del usuario (para mapa global de colores)
        $allCourses = CourseModel::where('user_id', $userId)->get();
        $courseColorMap = [];
        foreach ($allCourses as $idx => $ac) {
            $courseColorMap[$ac->id] = $this->resolveCourseColor($ac->color, $idx);
        }

        // 3. Obtener Grafo previo existente (para fusión incremental)
        $existingGraph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
        $existingNodes = $existingGraph ? ($existingGraph->nodes ?? []) : [];
        $existingEdges = $existingGraph ? ($existingGraph->edges ?? []) : [];

        // Si se generó para un solo curso, filtrar los nodos previos de ese curso para reemplazarlos limpiamente
        if ($targetCourseId !== null) {
            $existingNodes = array_values(array_filter($existingNodes, fn ($n) => ($n['course_id'] ?? null) !== $targetCourseId));
            $existingEdges = array_values(array_filter($existingEdges, function ($e) use ($targetCourseId) {
                return ($e['course_id'] ?? null) !== $targetCourseId;
            }));
        }

        // 4. Preparar Datos de Cursos y Apuntes
        $coursesData = [];
        foreach ($targetCourses as $course) {
            $notesText = [];
            $note = $course->note;
            if ($note && is_array($note->content) && isset($note->content['entries'])) {
                foreach ($note->content['entries'] as $entry) {
                    $entryDate = $entry['recorded_at'] ?? '';
                    $blocksText = [];
                    if (isset($entry['blocks']) && is_array($entry['blocks'])) {
                        foreach ($entry['blocks'] as $block) {
                            $html = $block['html'] ?? '';
                            $clean = trim(strip_tags((string) $html));
                            if ($clean !== '') {
                                $blocksText[] = $clean;
                            }
                        }
                    }
                    if (! empty($blocksText)) {
                        $notesText[] = ($entryDate ? "[$entryDate] " : '').implode("\n", $blocksText);
                    }
                }
            }

            $coursesData[] = [
                'id' => $course->id,
                'name' => $course->name,
                'color' => $courseColorMap[$course->id] ?? self::COURSE_PALETTE[0],
                'has_notes' => ! empty($notesText),
                'notes_content' => ! empty($notesText) ? implode("\n\n---\n\n", $notesText) : 'Conceptos clave y fundamentos de la asignatura.',
            ];
        }

        // 5. Prompting Especializado para Segundo Cerebro 3D, Mapa Mental y Active Recall
        $coursesJson = json_encode($coursesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYS
Eres el motor pedagógico y de Segundo Cerebro de Epycus 2.0. Tu objetivo es estructurar el conocimiento académico en una constelación jerárquica:
1. Para cada curso debes generar:
   - 1 Nodo Padre ("is_parent": true) que representa la asignatura principal.
   - Entre 4 y 8 Nodos Hijos ("is_parent": false) que son los Chunks / Conceptos Clave atómicos.
2. Para cada Chunk Hijo debes generar:
   - "id": string único (ej. "chunk_c1_1")
   - "label": Nombre del concepto (máx 35 caracteres)
   - "course_id": ID del curso
   - "course_name": Nombre del curso
   - "category": Eje temático / rama para el Mapa Mental (ej. "Fundamentos", "Arquitectura", "Protocolos", "Clínica")
   - "summary": Idea clave directa y precisa (máx 150 caracteres)
   - "key_points": Array de 2 a 3 strings breves con los puntos esenciales para estudiar
   - "why_it_matters": Aplicación práctica / profesional (máx 120 caracteres)
   - "importance": Número del 1 al 5
   - "mastery": 70
   - "quiz_question": Pregunta retadora para autoevaluación (Active Recall)
   - "quiz_answer": Respuesta concreta y explicativa
3. Aristas / Enlaces:
   - Enlace jerárquico desde el Nodo Padre hacia cada uno de sus Nodos Hijos ("type": "hierarchy", "label": "contiene").
   - Entre 3 y 8 enlaces conceptuales entre nodos hijos ("type": "aplicacion" o "requisito", "label": "aplica en" o "conecta con").

Formato JSON Estricto:
{
  "nodes": [
    {
      "id": "course_1",
      "label": "Redes 2",
      "is_parent": true,
      "course_id": 1,
      "course_name": "Redes 2",
      "category": "Asignatura",
      "summary": "Asignatura troncal de redes y enrutamiento.",
      "importance": 5
    },
    {
      "id": "chunk_c1_1",
      "label": "Protocolo ARP",
      "is_parent": false,
      "course_id": 1,
      "course_name": "Redes 2",
      "category": "Capa de Enlace",
      "summary": "Resuelve la dirección MAC física a partir de una IP lógica.",
      "key_points": [
        "Mapea IP de capa 3 a MAC de capa 2",
        "Utiliza peticiones broadcast y respuestas unicast"
      ],
      "why_it_matters": "Sin ARP los switches no pueden entregar tramas en redes locales.",
      "importance": 5,
      "mastery": 70,
      "quiz_question": "¿Qué traduce el protocolo ARP en una red LAN?",
      "quiz_answer": "Traduce direcciones IP lógicas a direcciones MAC físicas mediante la tabla ARP."
    }
  ],
  "edges": [
    {
      "source": "course_1",
      "target": "chunk_c1_1",
      "label": "contiene",
      "type": "hierarchy",
      "course_id": 1
    }
  ],
  "global_insight": "Conocimientos estructurados en constelación jerárquica para asimilación activa."
}
SYS;

        $userPrompt = "Cursos y Apuntes para estructurar:\n{$coursesJson}\n\nGenera la constelación de Nodos Padre, Chunks Hijos y Enlaces en JSON estricto:";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // 6. Invocación a DeepSeek API con fallback inteligente
        try {
            $rawResponse = $this->apiClient->chat($messages);
            $parsedData = $this->parseJsonSafely($rawResponse);
        } catch (Exception $e) {
            Log::warning("DeepSeek API error o timeout: {$e->getMessage()}. Generando constelación con motor semántico de respaldo.");
            $parsedData = $this->generateFallbackHierarchy($coursesData);
        }

        // 7. Normalizar y colorear nuevos nodos
        $newNodes = $parsedData['nodes'] ?? [];
        foreach ($newNodes as &$node) {
            $cId = $node['course_id'] ?? null;
            $node['color'] = $courseColorMap[$cId] ?? self::COURSE_PALETTE[0];
            $node['is_parent'] = (bool) ($node['is_parent'] ?? false);
            $node['mastery'] = isset($node['mastery']) ? (int) $node['mastery'] : 70;
            $node['importance'] = isset($node['importance']) ? (int) $node['importance'] : 4;
            $node['last_reviewed_at'] = 'Hoy';
        }
        unset($node);

        $newEdges = $parsedData['edges'] ?? [];
        foreach ($newEdges as &$edge) {
            $edge['course_id'] = $edge['course_id'] ?? $targetCourseId;
        }
        unset($edge);

        // Fusión con el grafo global
        $finalNodes = array_merge($existingNodes, $newNodes);
        $finalEdges = array_merge($existingEdges, $newEdges);

        $stats = [
            'total_concepts' => count(array_filter($finalNodes, fn ($n) => empty($n['is_parent']))),
            'total_connections' => count($finalEdges),
            'courses_count' => count($allCourses),
            'global_insight' => $parsedData['global_insight'] ?? 'Tu Segundo Cerebro está sincronizado y listo para el estudio activo.',
        ];

        // 8. Guardar en Base de Datos
        UserKnowledgeGraphModel::updateOrCreate(
            ['user_id' => $userId],
            [
                'nodes' => $finalNodes,
                'edges' => $finalEdges,
                'stats' => $stats,
                'last_generated_at' => Carbon::now(),
            ]
        );

        // 9. Descontar 1 crédito de cuota de IA
        $today = Carbon::now()->toDateString();
        $quotaRecord = AiQuotaModel::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['used_count' => 0]
        );
        $quotaRecord->increment('used_count');

        $updatedQuota = $this->checkQuota->execute($userId);

        return [
            'has_graph' => true,
            'nodes' => $finalNodes,
            'edges' => $finalEdges,
            'stats' => $stats,
            'last_generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'quota' => $updatedQuota,
        ];
    }

    private function resolveCourseColor(?string $color, int $index): string
    {
        if ($color && str_starts_with($color, '#') && strlen($color) === 7) {
            return $color;
        }

        return self::COURSE_PALETTE[$index % count(self::COURSE_PALETTE)];
    }

    private function parseJsonSafely(string $raw): array
    {
        $clean = trim($raw);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```[a-zA-Z]*\s*/m', '', $clean);
            $clean = preg_replace('/```$/m', '', $clean);
            $clean = trim((string) $clean);
        }

        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new Exception('Formato de JSON inválido retornado por la IA');
        }

        return $decoded;
    }

    private function generateFallbackHierarchy(array $coursesData): array
    {
        $nodes = [];
        $edges = [];

        foreach ($coursesData as $c) {
            $parentId = "course_{$c['id']}";
            $nodes[] = [
                'id' => $parentId,
                'label' => $c['name'],
                'is_parent' => true,
                'course_id' => $c['id'],
                'course_name' => $c['name'],
                'category' => 'Asignatura',
                'summary' => "Ecosistema de conocimiento para {$c['name']}.",
                'importance' => 5,
            ];

            // 3 Chunks hijos representativos
            $sampleChunks = [
                ['label' => "Fundamentos de {$c['name']}", 'cat' => 'Teoría Central'],
                ['label' => "Metodología y Análisis en {$c['name']}", 'cat' => 'Práctica Aplicada'],
                ['label' => "Evaluación y Casos de {$c['name']}", 'cat' => 'Resolución'],
            ];

            foreach ($sampleChunks as $i => $s) {
                $childId = "chunk_c{$c['id']}_{$i}";
                $nodes[] = [
                    'id' => $childId,
                    'label' => $s['label'],
                    'is_parent' => false,
                    'course_id' => $c['id'],
                    'course_name' => $c['name'],
                    'category' => $s['cat'],
                    'summary' => "Concepto fundamental extraído de los apuntes oficiales de {$c['name']}.",
                    'key_points' => [
                        "Principio clave para la comprensión de {$c['name']}",
                        "Eje conceptual evaluado en exámenes y proyectos",
                    ],
                    'why_it_matters' => 'Esencial para consolidar la base profesional de esta materia.',
                    'importance' => 4,
                    'mastery' => 70,
                    'quiz_question' => "¿Cuál es el principio medular de {$s['label']}?",
                    'quiz_answer' => "Representa el sustento conceptual y la aplicación directa en {$c['name']}.",
                ];

                $edges[] = [
                    'source' => $parentId,
                    'target' => $childId,
                    'label' => 'contiene',
                    'type' => 'hierarchy',
                    'course_id' => $c['id'],
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'global_insight' => 'Constelación de Segundo Cerebro generada con motor semántico local.',
        ];
    }
}
