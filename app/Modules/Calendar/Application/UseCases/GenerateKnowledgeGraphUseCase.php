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

    public function execute(int $userId): array
    {
        // 1. Validar Cuota Diaria de IA
        $quota = $this->checkQuota->execute($userId);
        if ($quota['is_exhausted']) {
            throw new Exception('Has alcanzado el límite diario de generación con IA (5/5). Se reinicia mañana.');
        }

        // 2. Obtener Cursos y Apuntes del Usuario
        $courses = CourseModel::where('user_id', $userId)
            ->with(['note'])
            ->get();

        if ($courses->isEmpty()) {
            throw new Exception('No tienes cursos registrados. Agrega al menos una materia para construir tu Segundo Cerebro.');
        }

        // 3. Obtener Grafo previo existente (para fusión y expansión incremental)
        $existingGraph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
        $existingNodes = $existingGraph ? ($existingGraph->nodes ?? []) : [];

        // Mapeo de posiciones previas
        $previousPosMap = [];
        foreach ($existingNodes as $en) {
            if (isset($en['id'], $en['x'], $en['y'])) {
                $previousPosMap[$en['id']] = ['x' => $en['x'], 'y' => $en['y']];
            }
            if (isset($en['label'], $en['x'], $en['y'])) {
                $previousPosMap[mb_strtolower(trim($en['label']))] = ['x' => $en['x'], 'y' => $en['y']];
            }
        }

        // 4. Preparar Datos de Cursos y Apuntes
        $coursesData = [];
        $courseColorMap = [];

        foreach ($courses as $index => $course) {
            $assignedColor = $this->resolveCourseColor($course->color, $index);
            $courseColorMap[$course->id] = $assignedColor;

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

            $hasNotes = ! empty($notesText);
            $coursesData[] = [
                'id' => $course->id,
                'name' => $course->name,
                'color' => $assignedColor,
                'has_notes' => $hasNotes,
                'notes_count' => count($notesText),
                'notes_content' => $hasNotes
                    ? mb_substr(implode("\n---\n", $notesText), 0, 1800)
                    : 'Sin apuntes registrados aún por el estudiante.',
            ];
        }

        // 5. Construir Prompt para DeepSeek con Fusión Incremental y Valor Pedagógico
        $coursesJson = json_encode($coursesData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $existingNodesSummary = ! empty($existingNodes)
            ? json_encode(array_map(fn ($n) => ['id' => $n['id'], 'label' => $n['label'], 'course' => $n['course_name'] ?? ''], $existingNodes), JSON_UNESCAPED_UNICODE)
            : 'Ninguno (generación inicial).';

        $systemPrompt = <<<SYS
Eres un motor avanzado de Grafos de Conocimiento ("Segundo Cerebro") universitario y pedagógico para la plataforma Epycus.
Tu tarea es analizar los cursos y apuntes del estudiante universitario y construir o expandir un GRAFO DE CONOCIMIENTO interconectado estilo Obsidian de alto valor pedagógico.

REGLAS DE SALIDA:
1. Debes responder EXCLUSIVAMENTE con un JSON válido, sin bloques de markdown (```json), sin texto antes ni después.
2. Extrae entre 10 y 24 nodos (conceptos / temas clave) distribuidos entre los cursos proporcionados:
   - Para cursos con apuntes ricos (ej. Redes): extrae los conceptos técnicos exactos explicados en sus notas.
   - Para cursos de idiomas (ej. Inglés): si no tienen apuntes aún, sugiere conceptos fundamentales reales como "Vocabulario Técnico", "Gramática & Estructura", "Comprensión de Lectura", adaptados a la carrera.
   - Para cursos de tesis/investigación: sugiere temas de metodología de investigación científica.
3. Para cada nodo debes definir:
   - "id": string único (ej. "node_1", "node_2")
   - "label": Nombre claro y conciso del concepto (máx 28 caracteres)
   - "course_id": ID numérico del curso al que pertenece
   - "course_name": Nombre del curso
   - "summary": Explicación concisa y clara del concepto (máx 140 caracteres)
   - "why_it_matters": Por qué es fundamental en la formación profesional / vida real (máx 130 caracteres)
   - "importance": número del 1 al 5 (relevancia curricular)
   - "category": una de ["fundamental", "practico", "evaluacion"]
   - "quiz_question": Una pregunta rápida para autoevaluación (Active Recall)
   - "quiz_answer": Respuesta concisa a la pregunta
4. Extrae entre 10 y 30 enlaces (edges / conexiones temáticas) que conecten conceptos del mismo curso y conexiones interdisciplinarias inteligentes entre distintos cursos.
5. Para cada enlace debes definir:
   - "source": id del nodo origen
   - "target": id del nodo destino
   - "label": Verbo o relación corta (ej. "aplica en", "base de", "complementa", "prerrequisito de", "resuelve")
   - "type": una de ["requisito", "aplicacion", "interdisciplinario"]
   - "strength": número del 1 al 3
6. Estructura exacta del JSON requerido:
{
  "nodes": [
    {
      "id": "node_1",
      "label": "Protocolo ARP",
      "course_id": 1,
      "course_name": "Redes 2",
      "summary": "Resuelve la dirección MAC física a partir de una IP lógica.",
      "why_it_matters": "Sin ARP, los switches no pueden entregar paquetes en redes LAN locales.",
      "importance": 5,
      "category": "fundamental",
      "quiz_question": "¿Qué traduce el protocolo ARP en una red local?",
      "quiz_answer": "Traduce direcciones IP lógicas a direcciones MAC físicas."
    }
  ],
  "edges": [
    {
      "source": "node_1",
      "target": "node_2",
      "label": "se registra en",
      "type": "aplicacion",
      "strength": 3
    }
  ],
  "global_insight": "Una frase inspiradora sobre las conexiones de estudio del usuario."
}
SYS;

        $userPrompt = "Cursos y Apuntes actuales del estudiante:\n{$coursesJson}\n\nNodos existentes en el grafo:\n{$existingNodesSummary}\n\nGenera el Grafo de Conocimiento interconectado enriquecido en JSON:";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // 6. Invocación a DeepSeek API con fallback inteligente
        try {
            $rawResponse = $this->apiClient->chat($messages);
            $parsedData = $this->parseJsonSafely($rawResponse);
        } catch (Exception $e) {
            Log::warning("DeepSeek API no respondió a tiempo para usuario {$userId}: {$e->getMessage()}. Usando motor semántico de respaldo.");
            $parsedData = $this->generateFallbackGraph($coursesData);
        }

        // 7. Asignar colores, categorías y preservar posiciones existentes
        $nodes = $parsedData['nodes'] ?? [];
        foreach ($nodes as &$node) {
            $cId = $node['course_id'] ?? null;
            $node['color'] = $courseColorMap[$cId] ?? self::COURSE_PALETTE[0];
            $node['category'] = $node['category'] ?? 'fundamental';
            $node['why_it_matters'] = $node['why_it_matters'] ?? 'Concepto clave para tu desarrollo profesional.';
            $node['quiz_question'] = $node['quiz_question'] ?? '¿Cuál es el propósito central de este concepto?';
            $node['quiz_answer'] = $node['quiz_answer'] ?? ($node['summary'] ?? 'Revisa tus apuntes para consolidar este tema.');

            // Preservar coordenadas espaciales si ya existían
            $nId = $node['id'] ?? '';
            $nLabel = mb_strtolower(trim($node['label'] ?? ''));
            if (isset($previousPosMap[$nId])) {
                $node['x'] = $previousPosMap[$nId]['x'];
                $node['y'] = $previousPosMap[$nId]['y'];
            } elseif (isset($previousPosMap[$nLabel])) {
                $node['x'] = $previousPosMap[$nLabel]['x'];
                $node['y'] = $previousPosMap[$nLabel]['y'];
            }
        }
        unset($node);

        $edges = $parsedData['edges'] ?? [];
        foreach ($edges as &$edge) {
            $edge['type'] = $edge['type'] ?? 'aplicacion';
            $edge['strength'] = $edge['strength'] ?? 2;
        }
        unset($edge);

        $stats = [
            'total_concepts' => count($nodes),
            'total_connections' => count($edges),
            'courses_count' => count($courses),
            'global_insight' => $parsedData['global_insight'] ?? 'Tus conocimientos están interconectados y en constante evolución.',
        ];

        // 8. Guardar en Base de Datos
        $graph = UserKnowledgeGraphModel::updateOrCreate(
            ['user_id' => $userId],
            [
                'nodes' => $nodes,
                'edges' => $edges,
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
            'nodes' => $nodes,
            'edges' => $edges,
            'stats' => $stats,
            'last_generated_at' => $graph->last_generated_at?->format('d/m/Y H:i'),
            'quota' => $updatedQuota,
            'courses' => $coursesData,
        ];
    }

    private function resolveCourseColor(?string $color, int $index): string
    {
        if (empty($color)) {
            return self::COURSE_PALETTE[$index % count(self::COURSE_PALETTE)];
        }

        $trimmed = trim($color);
        if (str_starts_with($trimmed, '#') || str_starts_with($trimmed, 'rgb') || str_starts_with($trimmed, 'hsl')) {
            return $trimmed;
        }

        $namedColors = [
            'primary' => '#818cf8',
            'accent' => '#a78bfa',
            'success' => '#34d399',
            'warning' => '#fbbf24',
            'danger' => '#f87171',
            'secondary' => '#94a3b8',
        ];

        return $namedColors[mb_strtolower($trimmed)] ?? self::COURSE_PALETTE[$index % count(self::COURSE_PALETTE)];
    }

    private function parseJsonSafely(string $rawResponse): array
    {
        $clean = trim($rawResponse);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/i', '', (string) $clean);
        $clean = trim((string) $clean);

        $data = json_decode($clean, true);

        if (! is_array($data) || empty($data['nodes'])) {
            if (preg_match('/\{[\s\S]*"nodes"[\s\S]*\}/', $clean, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        if (! is_array($data) || empty($data['nodes'])) {
            throw new Exception('El formato de respuesta de la IA no es un JSON de grafo válido.');
        }

        return $data;
    }

    private function generateFallbackGraph(array $coursesData): array
    {
        $nodes = [];
        $edges = [];
        $nodeIndex = 1;

        foreach ($coursesData as $c) {
            $cId = $c['id'];
            $cName = $c['name'];
            $nameLower = mb_strtolower($cName);

            $n1 = "node_{$nodeIndex}";
            $nodeIndex++;
            $n2 = "node_{$nodeIndex}";
            $nodeIndex++;
            $n3 = "node_{$nodeIndex}";
            $nodeIndex++;

            if (str_contains($nameLower, 'ingl') || str_contains($nameLower, 'idiom') || str_contains($nameLower, 'leng')) {
                // Mapeo semántico para Cursos de Idiomas
                $nodes[] = [
                    'id' => $n1,
                    'label' => "Vocabulario Técnico ({$cName})",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Términos especializados y léxico profesional en {$cName}.",
                    'why_it_matters' => "Permite leer manuales de ingeniería, RFCs y documentación internacional sin barreras.",
                    'importance' => 5,
                    'category' => 'fundamental',
                    'quiz_question' => "¿Por qué es crucial dominar el vocabulario técnico en {$cName}?",
                    'quiz_answer' => "Para interpretar documentación técnica internacional y comunicarse en proyectos globales.",
                ];

                $nodes[] = [
                    'id' => $n2,
                    'label' => "Comprensión y Lectura",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Estrategias de lectura rápida y comprensión de textos en {$cName}.",
                    'why_it_matters' => "Facilita la asimilación de artículos científicos y estándares de la industria.",
                    'importance' => 4,
                    'category' => 'practico',
                    'quiz_question' => "¿Qué habilidad permite analizar documentación técnica en {$cName}?",
                    'quiz_answer' => "La comprensión lectora enfocada en la identificación de conceptos y especificaciones.",
                ];

                $nodes[] = [
                    'id' => $n3,
                    'label' => "Redacción Profesional",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Estructuración de correos, reportes técnicos y síntesis en {$cName}.",
                    'why_it_matters' => "Indispensable para reportar incidencias y elaborar resúmenes de proyectos.",
                    'importance' => 3,
                    'category' => 'evaluacion',
                    'quiz_question' => "¿Cómo se evidencia el dominio formal de {$cName}?",
                    'quiz_answer' => "A través de la redacción precisa de informes técnicos y comunicación formal.",
                ];
            } elseif (str_contains($nameLower, 'tesis') || str_contains($nameLower, 'investig') || str_contains($nameLower, 'proyect')) {
                // Mapeo semántico para Tesis e Investigación
                $nodes[] = [
                    'id' => $n1,
                    'label' => "Planteamiento del Problema",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Definición del tema, pregunta central y objetivos de {$cName}.",
                    'why_it_matters' => "Es el cimiento sobre el cual se construye toda la investigación de grado.",
                    'importance' => 5,
                    'category' => 'fundamental',
                    'quiz_question' => "¿Qué define el alcance inicial de una investigación en {$cName}?",
                    'quiz_answer' => "El planteamiento claro del problema y los objetivos delimitados.",
                ];

                $nodes[] = [
                    'id' => $n2,
                    'label' => "Marco Teórico & Antecedentes",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Revisión bibliográfica y estado del arte en {$cName}.",
                    'why_it_matters' => "Garantiza que la investigación tenga sustento científico y rigor académico.",
                    'importance' => 4,
                    'category' => 'practico',
                    'quiz_question' => "¿Cuál es el rol del marco teórico en {$cName}?",
                    'quiz_answer' => "Respaldar los conceptos y metodologías mediante literatura científica validada.",
                ];

                $nodes[] = [
                    'id' => $n3,
                    'label' => "Metodología y Resultados",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Diseño experimental, recolección de datos y análisis en {$cName}.",
                    'why_it_matters' => "Demuestra con evidencia empírica las conclusiones del proyecto.",
                    'importance' => 4,
                    'category' => 'evaluacion',
                    'quiz_question' => "¿Cómo se validan las hipótesis en {$cName}?",
                    'quiz_answer' => "A través de la aplicación rigurosa de la metodología y el análisis de resultados.",
                ];
            } else {
                // Materias Técnicas / Generales
                $nodes[] = [
                    'id' => $n1,
                    'label' => "Fundamentos de {$cName}",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Principios teóricos y arquitectura base de {$cName}.",
                    'why_it_matters' => "Base conceptual indispensable para el desarrollo profesional.",
                    'importance' => 5,
                    'category' => 'fundamental',
                    'quiz_question' => "¿Cuál es el pilar teórico principal de {$cName}?",
                    'quiz_answer' => "Los conceptos base y la arquitectura de la materia.",
                ];

                $nodes[] = [
                    'id' => $n2,
                    'label' => "Aplicación Práctica: {$cName}",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Implementación, configuración y casos de uso en {$cName}.",
                    'why_it_matters' => "Permite resolver problemas reales de la industria.",
                    'importance' => 4,
                    'category' => 'practico',
                    'quiz_question' => "¿Cómo se traslada la teoría de {$cName} a la práctica?",
                    'quiz_answer' => "Mediante proyectos aplicados y resolución de casos de estudio.",
                ];

                $nodes[] = [
                    'id' => $n3,
                    'label' => "Evaluación y Métricas ({$cName})",
                    'course_id' => $cId,
                    'course_name' => $cName,
                    'summary' => "Criterios de validación, pruebas y diagnóstico en {$cName}.",
                    'why_it_matters' => "Garantiza el correcto funcionamiento y optimización.",
                    'importance' => 3,
                    'category' => 'evaluacion',
                    'quiz_question' => "¿Qué métricas determinan el éxito en {$cName}?",
                    'quiz_answer' => "La efectividad, estabilidad y cumplimiento de requerimientos.",
                ];
            }

            $edges[] = ['source' => $n1, 'target' => $n2, 'label' => 'se aplica en', 'type' => 'aplicacion', 'strength' => 3];
            $edges[] = ['source' => $n2, 'target' => $n3, 'label' => 'evaluado en', 'type' => 'requisito', 'strength' => 2];
        }

        if (count($coursesData) >= 2) {
            $edges[] = ['source' => 'node_2', 'target' => 'node_4', 'label' => 'interdisciplinario con', 'type' => 'interdisciplinario', 'strength' => 2];
        }
        if (count($coursesData) >= 3) {
            $edges[] = ['source' => 'node_5', 'target' => 'node_7', 'label' => 'interdisciplinario con', 'type' => 'interdisciplinario', 'strength' => 2];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'global_insight' => 'Tus materias forman una red conectada de aprendizaje continuo.',
        ];
    }
}
