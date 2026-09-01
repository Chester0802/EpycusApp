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
        @set_time_limit(240);
        @ignore_user_abort(true);

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
            throw new Exception('No tienes asignaturas registradas.');
        }

        $allCourses = CourseModel::where('user_id', $userId)->get();
        $courseColorMap = [];
        foreach ($allCourses as $idx => $ac) {
            $courseColorMap[$ac->id] = $this->resolveCourseColor($ac->color, $idx);
        }

        // 3. Obtener Grafo previo existente
        $existingGraph = UserKnowledgeGraphModel::where('user_id', $userId)->first();
        $existingNodes = $existingGraph ? ($existingGraph->nodes ?? []) : [];
        $existingEdges = $existingGraph ? ($existingGraph->edges ?? []) : [];

        // Si se generó para un solo curso, filtrar los nodos previos de ese curso
        if ($targetCourseId !== null) {
            $existingNodes = array_values(array_filter($existingNodes, fn ($n) => ($n['course_id'] ?? null) !== $targetCourseId));
            $existingEdges = array_values(array_filter($existingEdges, function ($e) use ($targetCourseId) {
                return ($e['course_id'] ?? null) !== $targetCourseId;
            }));
        }

        // 4. Preparar Datos de Cursos y VALIDACIÓN ESTRICTA (CERO APUNTES = CERO ALUCINACIÓN)
        $coursesData = [];
        foreach ($targetCourses as $course) {
            $rawSections = [];
            $note = $course->note;
            if ($note && is_array($note->content) && isset($note->content['entries'])) {
                foreach ($note->content['entries'] as $entry) {
                    if (isset($entry['blocks']) && is_array($entry['blocks'])) {
                        foreach ($entry['blocks'] as $block) {
                            $html = $block['html'] ?? '';
                            if (trim((string) $html) !== '') {
                                $parsed = $this->extractSectionsFromHtml((string) $html);
                                if (! empty($parsed)) {
                                    $rawSections = array_merge($rawSections, $parsed);
                                }
                            }
                        }
                    }
                }
            }

            if (! empty($rawSections)) {
                $coursesData[] = [
                    'id' => $course->id,
                    'name' => $course->name,
                    'color' => $courseColorMap[$course->id] ?? self::COURSE_PALETTE[0],
                    'has_notes' => true,
                    'sections' => $rawSections,
                ];
            }
        }

        // Si el usuario intentó generar un curso sin apuntes
        if (empty($coursesData)) {
            if ($targetCourseId !== null) {
                $cName = $targetCourses->first()?->name ?? 'la asignatura';
                throw new Exception("La asignatura '{$cName}' no tiene apuntes registrados todavía. Escribe tus notas oficiales antes de conectar con IA.");
            }
            throw new Exception('No tienes apuntes registrados en ninguna asignatura.');
        }

        // 5. Prompting Pedagógico con Material Real Extraído
        $coursesJson = json_encode($coursesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<SYS
Eres un Diseñador Curricular y Experto en Neurociencia del Aprendizaje. Tu tarea es estructurar con TOTAL RIGOR los conceptos clave a partir de los apuntes reales proporcionados por el estudiante para construir su Mapa Mental y Flashcards de Active Recall:

REGLAS ESTRICTAS DE ESTRUCTURACIÓN:
1. Para cada asignatura debes generar:
   - 1 NODO RAÍZ ("is_parent": true) con el nombre de la materia.
   - Entre 2 y 4 EJES TEMÁTICOS ("category") para distribuir armónicamente las ramas del Mapa Mental (ej. "Conmutación & Tabla CAM", "Direccionamiento & Protocolos", "Hardware & Capa 3").
   - 1 CHUNK ("is_parent": false) POR CADA TEMA O SECCIÓN REAL de los apuntes.

2. CADA CHUNK DEBE TENER TÍTULOS CONCISOS Y CONTENIDO SUSTANTIVO (NUNCA PALABRAS CORTADAS):
   - "id": string único (ej. "chunk_c1_1")
   - "label": Título limpio, completo y directo de 2 a 5 palabras (ej. "Decisiones Tabla CAM", "Subcapas LLC y MAC", "Campos de Trama Ethernet", "Dirección MAC", "Protocolo ARP", "Broadcast y Rendimiento", "Conmutación MAC", "Switches Modulares", "Configuración Capa 3").
   - "category": El eje temático correspondiente.
   - "summary": Idea clave directa y contundente basada en el apunte (máx 150 caracteres, sin prefijos como "Definición aceptada:").
   - "key_points": Array de 2 a 3 puntos técnicos explicativos.
   - "why_it_matters": Ejemplo práctico o caso de uso según el apunte.
   - "quiz_question": PREGUNTA DE ACTIVE RECALL DESAFIANTE sobre el mecanismo del tema (ej. "¿Qué acción toma el switch cuando la MAC destino de una trama no está en su tabla CAM?").
   - "quiz_answer": RESPUESTA CLAVE PRECISA Y CONCRETA (ej. "Realiza una inundación (flooding) replicando la trama por todos los puertos activos excepto el de origen.").
   - "importance": 5
   - "mastery": 70

3. ENLACES (EDGES):
   - Enlace jerárquico desde el Nodo Raíz a cada uno de sus Chunks ("type": "hierarchy", "label": "contiene").
   - 2 a 4 enlaces conceptuales entre chunks relacionados ("type": "relacion", "label": "conecta con").

FORMATO JSON REQUERIDO (Estricto, sin comentarios ni texto adicional):
{
  "nodes": [
    {
      "id": "course_1",
      "label": "Redes 2",
      "is_parent": true,
      "course_id": 1,
      "course_name": "Redes 2",
      "category": "Asignatura",
      "summary": "Arquitectura de conmutación y protocolos LAN."
    },
    {
      "id": "chunk_c1_1",
      "label": "Decisiones Tabla CAM",
      "is_parent": false,
      "course_id": 1,
      "course_name": "Redes 2",
      "category": "Conmutación & Tabla CAM",
      "summary": "Mecanismo del switch para asociar direcciones MAC a puertos físicos y reenviar tramas.",
      "key_points": [
        "Aprende leyendo la MAC origen de cada trama",
        "Reenvía por el puerto exacto si la MAC destino está registrada",
        "Inunda la red si la dirección de destino es desconocida"
      ],
      "why_it_matters": "Aísla el tráfico y evita colisiones en redes Ethernet.",
      "importance": 5,
      "mastery": 70,
      "quiz_question": "¿Qué acción realiza el switch cuando la MAC destino de una trama no está en su tabla CAM?",
      "quiz_answer": "Inunda la trama (flooding) por todos los puertos activos excepto el de origen."
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
  "global_insight": "Conocimiento extraído fielmente de tus apuntes de clase."
}
SYS;

        $userPrompt = "Apuntes reales del alumno:\n{$coursesJson}\n\nEstructura el Mapa Mental y Chunks en JSON:";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // 6. Invocación a DeepSeek API
        try {
            $rawResponse = $this->apiClient->chat($messages);
            $parsedData = $this->parseJsonSafely($rawResponse);
        } catch (\Throwable $e) {
            Log::warning("IA no disponible para grafo de conocimiento: {$e->getMessage()}.");
            throw new Exception('Los servidores están en mantenimiento. Disculpe.');
        }

        // 7. Normalizar y colorear nuevos nodos con IDs reales de base de datos
        $courseNameToId = [];
        foreach ($allCourses as $c) {
            $courseNameToId[mb_strtolower(trim($c->name))] = $c->id;
        }

        $newNodes = $parsedData['nodes'] ?? [];
        foreach ($newNodes as &$node) {
            if ($targetCourseId !== null) {
                $node['course_id'] = $targetCourseId;
            } else {
                $cName = mb_strtolower(trim($node['course_name'] ?? ''));
                if (isset($courseNameToId[$cName])) {
                    $node['course_id'] = $courseNameToId[$cName];
                }
            }
            $cId = $node['course_id'] ?? null;
            $node['color'] = $courseColorMap[$cId] ?? self::COURSE_PALETTE[0];
            $node['is_parent'] = (bool) ($node['is_parent'] ?? false);
            $node['mastery'] = 0; // Inicia en 0% hasta que el alumno practique las flashcards
            $node['importance'] = isset($node['importance']) ? (int) $node['importance'] : 4;
            $node['last_reviewed_at'] = 'Pendiente';
        }
        unset($node);

        $newEdges = $parsedData['edges'] ?? [];
        foreach ($newEdges as &$edge) {
            $edge['course_id'] = $edge['course_id'] ?? $targetCourseId;
        }
        unset($edge);

        // Fusión con el grafo global
        if ($targetCourseId !== null) {
            $finalNodes = array_merge($existingNodes, $newNodes);
            $finalEdges = array_merge($existingEdges, $newEdges);
        } else {
            $finalNodes = $newNodes;
            $finalEdges = $newEdges;
        }

        $stats = [
            'total_concepts' => count(array_filter($finalNodes, fn ($n) => empty($n['is_parent']))),
            'total_connections' => count($finalEdges),
            'courses_count' => count($allCourses),
            'global_insight' => $parsedData['global_insight'] ?? 'Tu Segundo Cerebro está sincronizado con tus notas oficiales.',
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
        AiQuotaModel::recordUsage($userId, 'knowledge_graph');

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

    /**
     * Parsea bloques HTML de apuntes y extrae cada sección temática con títulos limpios y concisos
     */
    private function extractSectionsFromHtml(string $html): array
    {
        $sections = [];
        
        $parts = preg_split('/<h3[^>]*>/i', $html);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') continue;

            if (str_contains($part, '</h3>')) {
                [$heading, $body] = explode('</h3>', $part, 2);
            } else {
                $heading = 'Concepto Clave';
                $body = $part;
            }

            $headingClean = trim(strip_tags($heading));
            // Limpiar prefijos redundantes
            $headingClean = preg_replace('/^(Palabra\s*\/\s*Frase:\s*|\d+\.\s*)/i', '', $headingClean);
            
            // Simplificar títulos largos de forma inteligente (sin cortar palabras)
            $titleReplacements = [
                'Decisiones de reenvío de tramas a través de la Tabla CAM' => 'Decisiones Tabla CAM',
                'Subcapas de Ethernet (LLC y MAC)' => 'Subcapas LLC y MAC',
                'Campos principales de la trama de Ethernet' => 'Campos de Trama Ethernet',
                'Propósito y características de la dirección MAC de Ethernet' => 'Dirección MAC Ethernet',
                'Propósito del protocolo ARP (Address Resolution Protocol)' => 'Protocolo ARP',
                'Cómo las solicitudes ARP afectan el rendimiento de la red y del host' => 'Rendimiento y Broadcast ARP',
                'Conceptos básicos de conmutación' => 'Conmutación (Aprender/Reenviar)',
                'Switches de configuración fija vs modulares' => 'Switches Fijos vs Modulares',
                'Configurar un switch de capa 3' => 'Switches de Capa 3',
            ];

            if (isset($titleReplacements[$headingClean])) {
                $headingClean = $titleReplacements[$headingClean];
            } else if (mb_strlen($headingClean) > 30) {
                // Acortar por palabras completas
                $words = explode(' ', $headingClean);
                $headingClean = implode(' ', array_slice($words, 0, 4));
            }

            $bodyClean = trim(strip_tags($body));
            // Limpiar texto de "Definición aceptada:"
            $bodyClean = preg_replace('/^Definición aceptada:\s*/i', '', $bodyClean);

            if ($headingClean !== '' && strlen($bodyClean) > 15) {
                $sections[] = [
                    'title' => $headingClean,
                    'content' => mb_substr($bodyClean, 0, 350),
                ];
            }
        }

        return $sections;
    }
}
