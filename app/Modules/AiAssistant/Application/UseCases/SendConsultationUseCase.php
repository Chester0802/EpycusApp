<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Application\UseCases;

use App\Modules\AiAssistant\Application\Services\AiContextBuilderService;
use App\Modules\AiAssistant\Domain\ValueObjects\CrisisDetector;
use App\Modules\AiAssistant\Infrastructure\Models\AiConversationModel;
use App\Modules\AiAssistant\Infrastructure\Models\AiMessageModel;
use App\Modules\AiAssistant\Infrastructure\Models\AiQuotaModel;
use App\Modules\AiAssistant\Infrastructure\Services\DeepSeekApiClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

final class SendConsultationUseCase
{
    public function __construct(
        private readonly CheckQuotaUseCase $checkQuota,
        private readonly AiContextBuilderService $contextBuilder,
        private readonly DeepSeekApiClient $apiClient,
    ) {}

    public function execute(int $userId, string $message, ?int $conversationId = null): array
    {
        $message = trim($message);
        if (empty($message)) {
            throw new Exception('El mensaje no puede estar vacío.');
        }

        // 1. Verificación de crisis (Protocolo de ética & contención)
        if (CrisisDetector::isCrisis($message)) {
            $containment = CrisisDetector::containmentMessage();

            $conversation = $this->getOrCreateConversation($userId, $conversationId);
            AiMessageModel::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
                'category' => 'crisis',
            ]);

            $assistantMsg = AiMessageModel::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $containment,
                'category' => 'crisis',
            ]);

            return [
                'conversation_id' => $conversation->id,
                'user_message' => $message,
                'response' => $containment,
                'message_id' => $assistantMsg->id,
                'is_crisis' => true,
                'quota' => $this->checkQuota->execute($userId),
            ];
        }

        // 2. Verificación de cuota diaria
        $quotaStatus = $this->checkQuota->execute($userId);
        if ($quotaStatus['is_exhausted']) {
            throw new Exception('Has alcanzado tu límite diario de 5 consultas. Tu cuota se reiniciará a las 00:00.');
        }

        // 3. Crear / Obtener conversación
        $conversation = $this->getOrCreateConversation($userId, $conversationId);

        // Guardar mensaje del usuario
        AiMessageModel::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
            'category' => 'general',
        ]);

        // 4. Preparar Prompt de Sistema y Contexto de Métricas (sin PII)
        $userContext = $this->contextBuilder->buildContext($userId);

        $systemPrompt = <<<SYS
Eres Edy, el tutor y copiloto virtual de hábitos, estudio y bienestar para estudiantes universitarios del proyecto Epycus.

MAPA INTEGRAL DE MÓDULOS DE EPYCUS:
- Calendario & Time-Blocking: Planificador 24h, horarios de clases universitarias, feriados oficiales, bloc de apuntes integrado y Segundo Cerebro 3D (constelación de nodos conceptuales).
- Cursos, Flashcards & Simulacros: Sílabos, simulador de notas y ponderados (0-20), repaso espaciado con Flashcards (método Leitner) y simulacros de examen con IA.
- Misiones & Kanban: Gestión de entregables y proyectos por fases con Matriz de Eisenhower (Q1 Hacer YA, Q2 Planificar/Clave, Q3 Minimizar, Q4 Descartar).
- Pomodoro & Salas 2D: Temporizador sincronizado individual o en salas de estudio multijugador 2D con música ambiental y sonidos binaurales.
- Hábitos, Hidratación & Fitness: Seguimiento diario de hábitos atómicos, meta de 8 vasos de agua, registro de entrenamientos y pausas activas.
- Diario de Bienestar: Registro emocional diario (1-5), nivel de estrés, energía, horas de sueño y notas reflexivas.
- Finanzas Estudiantiles: Registro de ingresos/gastos, presupuestos con semáforo de alerta y metas de ahorro.
- Gamificación & Recompensas: Credencial universitaria digital, 50 niveles de carrera, villanos de la procrastinación y Tienda de Autocuidado (canje de monedas).

Tus reglas obligatorias de conducta:
1. Responde en español peruano neutro, empático, motivador, claro y estructurado con viñetas cuando sea apropiado.
2. NUNCA des diagnósticos médicos, recetas farmacológicas ni consejos clínicos.
3. NUNCA prometas notas o resultados académicos garantizados.
4. Brinda consejos prácticos basados en la ciencia del aprendizaje: descomposición de tareas, técnica Feynman, repetición espaciada y bloques de foco Pomodoro.
5. Integra proactivamente los datos del contexto del estudiante (nivel, racha, minutos de foco, clases de hoy, misiones Q1 y evaluaciones próximas) para personalizar tus respuestas.
6. Si el estudiante te pide organizar su día o no sabe por dónde empezar, analiza sus clases de hoy, sus misiones en Q1 y su plan diario para darle un orden de prioridades claro.
7. DIRECTRIZ DE NAVEGACIÓN: Si el usuario menciona estudiar para una materia que no tiene registrada en sus cursos, sugiérele crearla en "Calendario" para poder activar sus apuntes, flashcards y simulacros.

{$userContext}
SYS;

        // Historial reciente (últimos 6 mensajes)
        $recentMessages = AiMessageModel::where('conversation_id', $conversation->id)
            ->orderBy('id', 'desc')
            ->take(6)
            ->get()
            ->reverse();

        $formattedMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($recentMessages as $msg) {
            $formattedMessages[] = [
                'role' => $msg->role,
                'content' => $msg->content,
            ];
        }

        // 5. Invocación a la API de DeepSeek
        try {
            $responseText = $this->apiClient->chat($formattedMessages);
        } catch (\Throwable $e) {
            Log::error('Fallo en API de IA para usuario ID '.$userId.': '.$e->getMessage());
            // No se descuenta la cuota si falla la API
            throw new Exception('Los servidores están en mantenimiento. Disculpe.');
        }

        // 6. Si tuvo éxito -> descontar cuota y guardar mensaje del asistente
        AiQuotaModel::recordUsage($userId, 'consultation');

        $assistantMsg = AiMessageModel::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $responseText,
            'category' => 'general',
        ]);

        return [
            'conversation_id' => $conversation->id,
            'user_message' => $message,
            'response' => $responseText,
            'message_id' => $assistantMsg->id,
            'is_crisis' => false,
            'quota' => $this->checkQuota->execute($userId),
        ];
    }

    private function getOrCreateConversation(int $userId, ?int $conversationId): AiConversationModel
    {
        if ($conversationId !== null) {
            $conversation = AiConversationModel::where('id', $conversationId)
                ->where('user_id', $userId)
                ->first();
            if ($conversation !== null) {
                return $conversation;
            }
        }

        return AiConversationModel::create([
            'user_id' => $userId,
            'title' => 'Conversación '.Carbon::now()->format('d/m H:i'),
        ]);
    }
}
