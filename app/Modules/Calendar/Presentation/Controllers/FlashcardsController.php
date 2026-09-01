<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AiAssistant\Application\UseCases\CheckQuotaUseCase;
use App\Modules\AiAssistant\Infrastructure\Models\AiQuotaModel;
use App\Modules\AiAssistant\Infrastructure\Services\DeepSeekApiClient;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\FlashcardModel;
use App\Modules\Gamification\Application\UseCases\AwardXpUseCase;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class FlashcardsController extends Controller
{
    public function __construct(
        private readonly DeepSeekApiClient $aiClient,
        private readonly CheckQuotaUseCase $checkQuota,
        private readonly AwardXpUseCase $awardXp,
    ) {}

    /**
     * Listar flashcards del curso organizadas por Cajas de Leitner.
     */
    public function index(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();
        $today = Carbon::now('America/Lima')->toDateString();

        $course = CourseModel::where('id', $courseId)->where('user_id', $userId)->firstOrFail();

        $cards = FlashcardModel::forUser($userId)
            ->forCourse($courseId)
            ->orderBy('leitner_box', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $boxCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $dueCards = [];

        foreach ($cards as $card) {
            $box = $card->leitner_box ?: 1;
            if (isset($boxCounts[$box])) {
                $boxCounts[$box]++;
            }
            if ($card->next_review_at === null || $card->next_review_at->toDateString() <= $today) {
                $dueCards[] = $card;
            }
        }

        return response()->json([
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'color' => $course->color,
            ],
            'flashcards' => $cards,
            'due_flashcards' => $dueCards,
            'stats' => [
                'total' => $cards->count(),
                'due_today' => count($dueCards),
                'mastered' => $boxCounts[5] ?? 0,
                'box_counts' => $boxCounts,
            ],
        ]);
    }

    /**
     * Crear una nueva Flashcard manual.
     */
    public function store(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();
        $course = CourseModel::where('id', $courseId)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'answer' => ['required', 'string', 'max:5000'],
            'node_id' => ['nullable', 'string', 'max:80'],
        ]);

        $flashcard = FlashcardModel::create([
            'user_id' => $userId,
            'course_id' => $course->id,
            'node_id' => $validated['node_id'] ?? null,
            'source' => 'manual',
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'leitner_box' => 1,
            'next_review_at' => Carbon::now('America/Lima')->toDateString(),
            'review_count' => 0,
            'success_streak' => 0,
        ]);

        return response()->json([
            'message' => 'Flashcard creada exitosamente.',
            'flashcard' => $flashcard,
        ], 201);
    }

    /**
     * Actualizar una Flashcard existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $flashcard = FlashcardModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'answer' => ['required', 'string', 'max:5000'],
        ]);

        $flashcard->update($validated);

        return response()->json([
            'message' => 'Flashcard actualizada exitosamente.',
            'flashcard' => $flashcard,
        ]);
    }

    /**
     * Eliminar una Flashcard.
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $flashcard = FlashcardModel::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $flashcard->delete();

        return response()->json([
            'message' => 'Flashcard eliminada.',
        ]);
    }

    /**
     * Registrar la respuesta del estudiante y aplicar algoritmo Leitner.
     */
    public function review(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $flashcard = FlashcardModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'in:easy,good,hard,fail'],
        ]);

        $previousBox = $flashcard->leitner_box;
        $flashcard->applyLeitnerReview($validated['rating']);

        // Otorgar XP por repasar
        $xpToAward = 5;
        if ($flashcard->leitner_box === 5 && $previousBox < 5) {
            $xpToAward += 10; // Bonus por dominar tarjeta (Caja 5)
        }

        $this->awardXp->execute($userId, 'flashcard', $flashcard->id, $xpToAward, 100, true);

        return response()->json([
            'message' => 'Repaso guardado.',
            'flashcard' => $flashcard,
            'xp_awarded' => $xpToAward,
        ]);
    }

    /**
     * Generar un set de Flashcards automáticamente con IA desde los apuntes del curso.
     */
    public function generateFromAi(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();
        $course = CourseModel::where('id', $courseId)->where('user_id', $userId)->with(['note'])->firstOrFail();

        // Validar cuota diaria
        $quota = $this->checkQuota->execute($userId);
        if ($quota['is_exhausted']) {
            return response()->json(['error' => 'Has alcanzado el límite diario de generaciones con IA.'], 429);
        }

        // Extraer texto de apuntes
        $noteContent = '';
        if ($course->note && !empty($course->note->content['entries'])) {
            foreach ($course->note->content['entries'] as $entry) {
                if (!empty($entry['blocks'])) {
                    foreach ($entry['blocks'] as $block) {
                        $noteContent .= ' '.strip_tags((string) ($block['html'] ?? ''));
                    }
                }
            }
        }

        $prompt = "Eres un neurocientífico experto en Active Recall y Repetición Espaciada para estudiantes universitarios.\n";
        $prompt .= "Genera exactamente 6 Flashcards de alto valor académico para el curso: {$course->name}.\n";
        if (!empty(trim($noteContent))) {
            $prompt .= "Básate principalmente en los siguientes apuntes del estudiante:\n".mb_substr($noteContent, 0, 3000)."\n\n";
        } else {
            $prompt .= "Como el estudiante no tiene apuntes extensos aún, genera conceptos clave universitarios fundamentales para este curso.\n\n";
        }
        $prompt .= "RESPONDE ÚNICAMENTE CON UN OBJETO JSON VÁLIDO CON LA SIGUIENTE ESTRUCTURA:\n";
        $prompt .= "{\n  \"flashcards\": [\n    {\n      \"question\": \"Pregunta concisa y estimulante\",\n      \"answer\": \"Respuesta clara, rigurosa y directa\"\n    }\n  ]\n}";

        try {
            $response = $this->aiClient->chat([
                ['role' => 'system', 'content' => 'Eres un tutor universitario experto que responde únicamente en formato JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $jsonString = preg_replace('/```json\s*|\s*```/', '', trim($response));
            $data = json_decode($jsonString, true);

            if (!isset($data['flashcards']) || !is_array($data['flashcards'])) {
                throw new Exception('Formato inválido devuelto por la IA.');
            }
            $cardsData = $data['flashcards'];
        } catch (\Throwable $e) {
            Log::warning('IA no disponible para flashcards ('.$e->getMessage().').');
            return response()->json(['error' => 'Los servidores están en mantenimiento. Disculpe.'], 503);
        }

        $createdCards = [];
        $today = Carbon::now('America/Lima')->toDateString();

        foreach ($cardsData as $card) {
            if (!empty($card['question']) && !empty($card['answer'])) {
                $createdCards[] = FlashcardModel::create([
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'source' => 'ai',
                    'question' => trim((string) $card['question']),
                    'answer' => trim((string) $card['answer']),
                    'leitner_box' => 1,
                    'next_review_at' => $today,
                    'review_count' => 0,
                    'success_streak' => 0,
                ]);
            }
        }

        // Registrar uso de cuota
        AiQuotaModel::recordUsage($userId, 'flashcards_generate');

        return response()->json([
            'message' => 'Se generaron '.count($createdCards).' Flashcards con éxito.',
            'flashcards' => $createdCards,
        ]);
    }

    /**
     * Generar un Simulacro de Examen con IA (6 opción múltiple + 4 respuesta abierta).
     */
    public function generateMockExam(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();
        $course = CourseModel::where('id', $courseId)->where('user_id', $userId)->with(['note'])->firstOrFail();

        $quota = $this->checkQuota->execute($userId);
        if ($quota['is_exhausted']) {
            return response()->json(['error' => 'Has alcanzado el límite diario de IA.'], 429);
        }

        $noteContent = '';
        if ($course->note && !empty($course->note->content['entries'])) {
            foreach ($course->note->content['entries'] as $entry) {
                if (!empty($entry['blocks'])) {
                    foreach ($entry['blocks'] as $block) {
                        $noteContent .= ' '.strip_tags((string) ($block['html'] ?? ''));
                    }
                }
            }
        }

        $prompt = "Eres un profesor universitario de primer nivel. Crea un SIMULACRO DE EXAMEN PARCIAL riguroso para la materia: {$course->name}.\n";
        if (!empty(trim($noteContent))) {
            $prompt .= "Apuntes de clase:\n".mb_substr($noteContent, 0, 3000)."\n\n";
        }
        $prompt .= "El examen debe contener:\n";
        $prompt .= "1. 6 preguntas de Opción Múltiple (cada una con 4 opciones A, B, C, D, la clave correcta y una explicación).\n";
        $prompt .= "2. 4 preguntas de Desarrollo / Respuesta Libre que evalúen comprensión profunda o casos prácticos con sus criterios clave de evaluación.\n\n";
        $prompt .= "RESPONDE ÚNICAMENTE CON UN JSON VÁLIDO CON ESTA ESTRUCTURA EXACTA:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Simulacro Parcial: {$course->name}\",\n";
        $prompt .= "  \"time_limit_minutes\": 20,\n";
        $prompt .= "  \"multiple_choice\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"id\": 1,\n";
        $prompt .= "      \"question\": \"Texto de la pregunta\",\n";
        $prompt .= "      \"options\": [\"Opción A\", \"Opción B\", \"Opción C\", \"Opción D\"],\n";
        $prompt .= "      \"correct_index\": 0,\n";
        $prompt .= "      \"explanation\": \"Por qué la A es correcta\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"open_questions\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"id\": 7,\n";
        $prompt .= "      \"question\": \"Pregunta de desarrollo\",\n";
        $prompt .= "      \"expected_keypoints\": \"Puntos clave esperados en la respuesta del alumno\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}";

        try {
            $response = $this->aiClient->chat([
                ['role' => 'system', 'content' => 'Eres un evaluador académico universitario que solo entrega JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $jsonString = preg_replace('/```json\s*|\s*```/', '', trim($response));
            $data = json_decode($jsonString, true);

            if (!isset($data['multiple_choice']) || !isset($data['open_questions'])) {
                throw new Exception('Formato de simulacro incompleto.');
            }
            $examData = $data;
        } catch (\Throwable $e) {
            Log::warning('IA no disponible para examen ('.$e->getMessage().').');
            return response()->json(['error' => 'Los servidores están en mantenimiento. Disculpe.'], 503);
        }

        AiQuotaModel::recordUsage($userId, 'mock_exam_generate');

        return response()->json([
            'success' => true,
            'exam' => $examData,
        ]);
    }

    /**
     * Evaluar respuestas del simulacro, calificar 0-20 y autogenerar flashcards para errores.
     */
    public function evaluateMockExam(Request $request, int $courseId): JsonResponse
    {
        $userId = (int) Auth::id();
        $course = CourseModel::where('id', $courseId)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'exam' => ['required', 'array'],
            'user_answers' => ['required', 'array'],
        ]);

        $exam = $validated['exam'];
        $userAnswers = $validated['user_answers'];

        $prompt = "Eres un jurado calificador universitario estricto pero pedagógico para el curso: {$course->name}.\n";
        $prompt .= "Evalúa el siguiente examen resuelto por el estudiante y califica con nota de 0 a 20.\n";
        $prompt .= "Examen y Respuestas del estudiante:\n".json_encode(['exam' => $exam, 'answers' => $userAnswers], JSON_UNESCAPED_UNICODE)."\n\n";
        $prompt .= "Genera retroalimentación detallada y extrae cada concepto donde el estudiante falló o tuvo respuesta insuficiente.\n";
        $prompt .= "RESPONDE SOLO EN JSON CON LA SIGUIENTE ESTRUCTURA:\n";
        $prompt .= "{\n";
        $prompt .= "  \"final_grade\": 16.5,\n";
        $prompt .= "  \"feedback_summary\": \"Excelente dominio teórico pero falto profundidad en...\",\n";
        $prompt .= "  \"questions_review\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"id\": 1,\n";
        $prompt .= "      \"is_correct\": true,\n";
        $prompt .= "      \"score\": 2.0,\n";
        $prompt .= "      \"max_score\": 2.0,\n";
        $prompt .= "      \"comment\": \"Correcto\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"failed_concepts\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"question\": \"Pregunta en la que falló para crear Flashcard\",\n";
        $prompt .= "      \"answer\": \"Respuesta correcta y concisa\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}";

        try {
            $response = $this->aiClient->chat([
                ['role' => 'system', 'content' => 'Eres un evaluador académico que responde solo en JSON estricto.'],
                ['role' => 'user', 'content' => $prompt],
            ]);

            $jsonString = preg_replace('/```json\s*|\s*```/', '', trim($response));
            $data = json_decode($jsonString, true);

            if (!isset($data['final_grade']) || !isset($data['questions_review'])) {
                throw new Exception('Evaluación incompleta devuelta por la IA.');
            }
            $evaluationData = $data;
        } catch (\Throwable $e) {
            Log::warning('IA no disponible para evaluar simulacro ('.$e->getMessage().').');
            return response()->json(['error' => 'Los servidores están en mantenimiento. Disculpe.'], 503);
        }

        // Auto-crear Flashcards en Caja 1 para las preguntas falladas
        $createdCards = [];
        $today = Carbon::now('America/Lima')->toDateString();

        if (!empty($evaluationData['failed_concepts']) && is_array($evaluationData['failed_concepts'])) {
            foreach ($evaluationData['failed_concepts'] as $concept) {
                if (!empty($concept['question']) && !empty($concept['answer'])) {
                    $createdCards[] = FlashcardModel::create([
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'source' => 'ai',
                        'question' => trim((string) $concept['question']),
                        'answer' => trim((string) $concept['answer']),
                        'leitner_box' => 1,
                        'next_review_at' => $today,
                        'review_count' => 0,
                        'success_streak' => 0,
                    ]);
                }
            }
        }

        // Otorgar XP por completar simulacro (+30 XP)
        $this->awardXp->execute($userId, 'mock_exam', $courseId, 30, 5, true);

        return response()->json([
            'evaluation' => $evaluationData,
            'autocreated_flashcards_count' => count($createdCards),
            'xp_awarded' => 30,
        ]);
    }
}
