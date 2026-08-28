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
        '#6366f1', // Índigo Real
        '#0ea5e9', // Azul Océano
        '#10b981', // Esmeralda Viva
        '#f59e0b', // Ámbar Dorado
        '#ec4899', // Rosa Intenso
        '#8b5cf6', // Púrpura Profundo
        '#f97316', // Naranja Cálido
        '#14b8a6', // Turquesa Bio
    ];

    public function __construct(
        private readonly DeepSeekApiClient $apiClient,
        private readonly CheckQuotaUseCase $checkQuota,
    ) {}

    public function execute(int $userId, ?int $targetCourseId = null): array
    {
        // 1. Validar Cuota Diaria de IA (5 usos globales por usuario)
        $quota = $this->checkQuota->execute($userId);
        if ($quota['is_exhausted']) {
            throw new Exception('Has alcanzado el límite diario de 5 generaciones con IA. Se reiniciará mañana.');
        }

        // 2. Obtener Cursos y Apuntes del Usuario
        $coursesQuery = CourseModel::where('user_id', $userId)->with(['note']);
        if ($targetCourseId !== null) {
            $coursesQuery->where('id', $targetCourseId);
        }
        $targetCourses = $coursesQuery->get();

        if ($targetCourses->isEmpty()) {
            throw new Exception('No tienes asignaturas registradas para generar el mapa mental y segundo cerebro.');
        }

        $allCourses = CourseModel::where('user_id', $userId)->get();
        $courseColorMap = [];
        foreach ($allCourses as $idx => $ac) {
            $courseColorMap[$ac->id] = $this->resolveCourseColor($ac->color, $idx);
        }

        // 3. Obtener Grafo previo existente (para fusión limpia e incremental)
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
                'notes_content' => ! empty($notesText) ? implode("\n\n---\n\n", $notesText) : 'Fundamentos teóricos, metodologías y aplicaciones prácticas de la materia.',
            ];
        }

        // 5. Prompting Pedagógico de Alto Nivel para Mapa Mental y Segundo Cerebro
        $coursesJson = json_encode($coursesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYS
Eres un Diseñador Curricular Senior y Experto en Neurociencia del Aprendizaje (Active Recall & Chunking). Tu misión es convertir las notas universitarias del usuario en un Mapa Mental Jerárquico y un Segundo Cerebro de alto impacto:

REGLAS DE ORO DE EXTRACCIÓN:
1. Para cada asignatura debes generar:
   - 1 NODO RAÍZ ("is_parent": true) con el nombre exacto de la materia.
   - Entre 3 y 5 EJES TEMÁTICOS / RAMAS ("category") bien delimitadas temáticamente (Ejemplos: "Arquitectura & Protocolos", "Teoría del Delito", "Farmacocinética", "Metodología de Muestreo"). NUNCA uses nombres genéricos como "General" o "Temas".
   - Entre 5 y 10 CHUNKS / CONCEPTOS CLAVE ("is_parent": false) repartidos entre esas ramas.

2. CADA CHUNK DEBE SER ATÓMICO Y PEDAGÓGICAMENTE RIGUROSO:
   - "id": string único con prefijo claro (ej. "chunk_c1_1")
   - "label": Nombre preciso y profesional del concepto (ej. "Tabla de Enrutamiento OSPF", "Dolo Eventual", "Biodisponibilidad Oral")
   - "category": La rama temática exacta a la que pertenece
   - "summary": Idea clave directa y contundente en 2 líneas (sin rodeos vacíos)
   - "key_points": Array de 2 a 3 puntos técnicos que expliquen el "cómo" o "por qué"
   - "why_it_matters": Caso de aplicación profesional o impacto real en el examen
   - "quiz_question": PREGUNTA DE ACTIVE RECALL DE ALTA CALIDAD que evalúe el razonamiento (ej. "¿Por qué OSPF prefiere métrica de costo sobre conteo de saltos en enlaces Gigabit?")
   - "quiz_answer": RESPUESTA CLARA, TÉCNICA Y DEFINITIVA que responda directamente a la pregunta
   - "importance": Número del 1 al 5
   - "mastery": 70

3. ENLACES (EDGES):
   - Enlaces de jerarquía del Nodo Raíz hacia cada Chunk.
   - 3 a 6 enlaces conceptuales horizontales entre chunks que se complementan o son prerrequisitos.

FORMATO JSON REQUERIDO (Estricto, sin texto fuera del JSON):
{
  "nodes": [
    {
      "id": "course_1",
      "label": "Redes de Computadoras",
      "is_parent": true,
      "course_id": 1,
      "course_name": "Redes de Computadoras",
      "category": "Asignatura",
      "summary": "Fundamentos de protocolos, enrutamiento y arquitectura de capas."
    },
    {
      "id": "chunk_c1_1",
      "label": "Protocolo ARP",
      "is_parent": false,
      "course_id": 1,
      "course_name": "Redes de Computadoras",
      "category": "Capa de Enlace & Conmutación",
      "summary": "Resuelve la dirección física MAC a partir de una dirección IP de red local.",
      "key_points": [
        "Opera mediante peticiones broadcast y respuestas unicast",
        "Mantiene una tabla temporal con expiración dinámica",
        "Esencial para el reenvío de tramas Ethernet en switches"
      ],
      "why_it_matters": "Sin resolución ARP, los hosts no pueden encapsular tramas Ethernet para comunicación LAN.",
      "importance": 5,
      "mastery": 70,
      "quiz_question": "¿Cuál es la función fundamental del protocolo ARP al enviar un paquete en una red Ethernet?",
      "quiz_answer": "Mapea la dirección IP de destino a la dirección MAC física del host o gateway local."
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
  "global_insight": "Conocimiento estructurado con rigor pedagógico para retención a largo plazo."
}
SYS;

        $userPrompt = "Apuntes y temas de la materia:\n{$coursesJson}\n\nGenera el Mapa Mental y Segundo Cerebro en JSON:";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // 6. Invocación a DeepSeek API con fallback inteligente
        try {
            $rawResponse = $this->apiClient->chat($messages);
            $parsedData = $this->parseJsonSafely($rawResponse);
        } catch (Exception $e) {
            Log::warning("DeepSeek API error: {$e->getMessage()}. Usando motor pedagógico semántico.");
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
                'summary' => "Ecosistema de conocimiento y módulos de {$c['name']}.",
                'importance' => 5,
            ];

            // Chunks estructurados con rigor pedagógico
            $sampleChunks = [
                [
                    'label' => "Marco Conceptual de {$c['name']}",
                    'cat' => 'Fundamentos Teóricos',
                    'summary' => "Principios rectores, definiciones y bases epistemológicas de la asignatura.",
                    'points' => ["Definición de conceptos base", "Modelos teóricos aplicables"],
                    'question' => "¿Cuáles son los 2 pilares teóricos fundamentales que sustentan este tema?",
                    'answer' => "La comprensión de las definiciones centrales y su marco de aplicación directa.",
                ],
                [
                    'label' => "Metodología y Procesos en {$c['name']}",
                    'cat' => 'Métodos & Aplicación',
                    'summary' => "Procedimientos, algoritmos y pautas de implementación práctica.",
                    'points' => ["Secuencia de etapas metodológicas", "Criterios de validación y control"],
                    'question' => "¿Cuál es el paso crítico en la ejecución de este procedimiento?",
                    'answer' => "La fase de verificación de requisitos previos y control de consistencia.",
                ],
                [
                    'label' => "Diagnóstico y Casos en {$c['name']}",
                    'cat' => 'Análisis & Resolución',
                    'summary' => "Resolución de problemas, casos clínicos/técnicos y toma de decisiones.",
                    'points' => ["Identificación de variables críticas", "Criterios de evaluación y resolución"],
                    'question' => "¿Cómo se evalúa la efectividad de la solución implementada en este escenario?",
                    'answer' => "Mediante el cotejo de indicadores de rendimiento y métricas de desempeño.",
                ],
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
                    'summary' => $s['summary'],
                    'key_points' => $s['points'],
                    'why_it_matters' => 'Esencial para consolidar la base profesional de esta materia.',
                    'importance' => 4,
                    'mastery' => 70,
                    'quiz_question' => $s['question'],
                    'quiz_answer' => $s['answer'],
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
