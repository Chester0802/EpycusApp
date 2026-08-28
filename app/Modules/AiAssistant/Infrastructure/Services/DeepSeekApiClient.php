<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeepSeekApiClient
{
    private string $apiKey;

    private string $baseUrl;

    private string $model;

    private int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.deepseek.api_key') ?: env('DEEPSEEK_API_KEY') ?: 'sk-3521af985349450d8ba4a155afacd867');
        $this->baseUrl = (string) config('services.deepseek.base_url', 'https://api.deepseek.com');
        $this->model = (string) (config('services.deepseek.model') ?: env('DEEPSEEK_MODEL') ?: 'deepseek-chat');
        $this->timeout = (int) config('services.deepseek.timeout', 180);
    }

    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): string
    {
        if (empty($this->apiKey)) {
            Log::warning('DeepSeek API Key no configurada. Usando respuesta simulada de respaldo.');

            return '¡Hola! Soy tu asistente de estudio EpyIA. ¿En qué te puedo ayudar hoy con tus hábitos, misiones o sesiones de estudio?';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout($this->timeout)
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $this->extractContent($data);
            }

            Log::error('Error HTTP en DeepSeek API: '.$response->status().' - '.$response->body());
            throw new Exception('Error al comunicarse con DeepSeek API: '.$response->status());
        } catch (Exception $e) {
            Log::error('Excepción al invocar DeepSeek API: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Extrae el texto de la respuesta. En modelos de razonamiento (deepseek-v4, reasoner)
     * el texto puede venir en `message.content` o en `message.reasoning_content`, y a
     * veces `content` viene vacío mientras el texto real está en `reasoning_content`.
     */
    private function extractContent(array $data): string
    {
        $message = $data['choices'][0]['message'] ?? [];

        $content = isset($message['content']) ? (string) $message['content'] : '';
        if (trim($content) !== '') {
            return $content;
        }

        $reasoning = isset($message['reasoning_content']) ? (string) $message['reasoning_content'] : '';
        if (trim($reasoning) !== '') {
            return $reasoning;
        }

        Log::warning('DeepSeek API devolvió respuesta vacía: '.json_encode($data));

        return 'No recibí una respuesta adecuada del asistente.';
    }
}
