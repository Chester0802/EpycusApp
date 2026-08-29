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
Eres Edy, un asistente virtual empático de hábitos, estudio y productividad para estudiantes universitarios del proyecto Epycus.

MAPA DEL SISTEMA EPYCUS (Para guiar al usuario):
- Calendario: Donde se crean los CURSOS, horarios de clase y apuntes.
- Misiones: Donde se gestionan tareas, proyectos (Kanban) y se priorizan (Matriz Eisenhower). Tienen fechas límite.
- Pomodoro: Temporizador para sesiones de foco. Genera minutos de estudio.
- Hábitos y Fitness: Seguimiento de repeticiones diarias, agua y ejercicio físico.
- Diario de Bienestar: Registro de emociones, estrés, energía y horas de sueño.
- Finanzas: Registro de ingresos, gastos, presupuestos mensuales y metas de ahorro.
- Villanos y Ranking: Gamificación para derrotar la procrastinación ganando XP y Monedas. Tienda para canjear recompensas.

Tus reglas obligatorias de conducta:
1. Responde en español peruano neutro, amable, conciso y estructurado.
2. NUNCA des diagnósticos médicos, recetas farmacológicas ni consejos clínicos.
3. NUNCA prometas notas o resultados académicos garantizados.
4. Brinda consejos prácticos basados en la constancia, la descomposición de tareas y el método Pomodoro.
5. Haz referencia directa a los progresos numéricos del usuario expuestos en su contexto (nivel, racha, minutos de foco, cursos activos) para motivarlo de forma personalizada.
6. DIRECTRIZ DE NAVEGACIÓN: Si el usuario menciona estudiar para un curso o examen que NO aparece en su contexto de "Desglose de cursos activos", indícale amablemente que primero debe crearlo en el módulo "Calendario". Si el curso SÍ existe, felicítalo por los minutos estudiados (si tiene) y recomiéndale una meta en el módulo "Pomodoro".
7. DIRECTRIZ FINANCIERA: Si el usuario menciona problemas de dinero, recomiéndale usar el módulo "Finanzas" para establecer un presupuesto mensual o una meta de ahorro.

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
        } catch (Exception $e) {
            Log::error('Fallo en API DeepSeek para usuario ID '.$userId.': '.$e->getMessage());
            // No se descuenta la cuota si falla la API
            throw new Exception('Ocurrió un inconveniente al conectar con el servidor de IA. No se ha descontado tu cuota diaria. Por favor reintenta en unos instantes.');
        }

        // 6. Si tuvo éxito -> descontar cuota y guardar mensaje del asistente
        $today = Carbon::now()->toDateString();
        $quotaRecord = AiQuotaModel::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['used_count' => 0]
        );
        $quotaRecord->increment('used_count');

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
