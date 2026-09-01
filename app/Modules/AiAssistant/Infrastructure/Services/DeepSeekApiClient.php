<?php

declare(strict_types=1);

namespace App\Modules\AiAssistant\Infrastructure\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DeepSeekApiClient
{
    private string $geminiApiKey;
    private string $geminiModel;
    private string $groqApiKey;
    private string $groqModel;
    private string $openrouterApiKey;
    private string $openrouterModel;
    private string $deepseekApiKey;
    private string $deepseekBaseUrl;
    private string $deepseekModel;
    private int $timeout;

    public function __construct()
    {
        $this->geminiApiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY') ?: '');
        $this->geminiModel = (string) (config('services.gemini.model') ?: env('GEMINI_MODEL') ?: 'gemini-1.5-flash');

        $this->groqApiKey = (string) (config('services.groq.api_key') ?: env('GROQ_API_KEY') ?: '');
        $this->groqModel = (string) (config('services.groq.model') ?: env('GROQ_MODEL') ?: 'llama-3.3-70b-versatile');

        $this->openrouterApiKey = (string) (config('services.openrouter.api_key') ?: env('OPENROUTER_API_KEY') ?: '');
        $this->openrouterModel = (string) (config('services.openrouter.model') ?: env('OPENROUTER_MODEL') ?: 'minimax/minimax-m3:free');

        $this->deepseekApiKey = (string) (config('services.deepseek.api_key') ?: env('DEEPSEEK_API_KEY') ?: '');
        $this->deepseekBaseUrl = (string) config('services.deepseek.base_url', 'https://api.deepseek.com');
        $this->deepseekModel = (string) (config('services.deepseek.model') ?: env('DEEPSEEK_MODEL') ?: 'deepseek-chat');

        $this->timeout = (int) config('services.deepseek.timeout', 45);
    }

    /**
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): string
    {
        // 1. Prioridad: Google Gemini (100% Gratis - 1,500 req/día)
        if (!empty($this->geminiApiKey)) {
            try {
                return $this->callGemini($messages);
            } catch (Exception $e) {
                Log::warning('Fallo llamada a Gemini API: ' . $e->getMessage() . '. Intentando siguiente proveedor...');
            }
        }

        // 2. Prioridad: OpenRouter (Modelos 100% Gratis)
        if (!empty($this->openrouterApiKey)) {
            try {
                return $this->callOpenRouter($messages);
            } catch (Exception $e) {
                Log::warning('Fallo llamada a OpenRouter API: ' . $e->getMessage() . '. Intentando siguiente proveedor...');
            }
        }

        // 3. Prioridad: Groq Cloud (100% Gratis - Llama 3.3 70B)
        if (!empty($this->groqApiKey)) {
            try {
                return $this->callGroq($messages);
            } catch (Exception $e) {
                Log::warning('Fallo llamada a Groq API: ' . $e->getMessage() . '. Intentando siguiente proveedor...');
            }
        }

        // 4. Prioridad: DeepSeek API
        if (!empty($this->deepseekApiKey)) {
            try {
                return $this->callDeepSeek($messages);
            } catch (Exception $e) {
                Log::warning('Fallo llamada a DeepSeek API: ' . $e->getMessage());
                throw $e;
            }
        }

        Log::warning('Ninguna API Key de IA configurada o todos los proveedores fallaron.');
        throw new Exception('Los servidores están en mantenimiento. Disculpe.');
    }

    /**
     * Llamada nativa a Google Gemini API (100% Free - 1500 req/día)
     */
    private function callGemini(array $messages): string
    {
        $systemText = '';
        $contents = [];

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = (string) ($msg['content'] ?? '');

            if ($role === 'system') {
                $systemText .= ($systemText !== '' ? "\n\n" : '') . $content;
            } else {
                $geminiRole = $role === 'assistant' ? 'model' : 'user';
                $contents[] = [
                    'role' => $geminiRole,
                    'parts' => [['text' => $content]],
                ];
            }
        }

        if (empty($contents) && !empty($systemText)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $systemText]],
            ];
            $systemText = '';
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 4096,
            ],
        ];

        if (!empty($systemText)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemText]],
            ];
        }

        $modelsToTry = array_unique([
            $this->geminiModel,
            'gemini-3.5-flash-lite',
            'gemma-4-31b-it',
            'gemma-4-26b-a4b-it',
        ]);

        foreach ($modelsToTry as $modelName) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . urlencode($this->geminiApiKey);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(25)
                    ->post($url, $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if (trim($text) !== '') {
                        return $text;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini fallo con modelo {$modelName}: " . $e->getMessage());
            }
        }

        throw new Exception('Google Gemini no pudo responder con ninguno de los modelos disponibles.');
    }

    /**
     * Llamada a OpenRouter (Modelos 100% Gratuitos con rotación rápida)
     */
    private function callOpenRouter(array $messages): string
    {
        $modelsToTry = array_unique([
            $this->openrouterModel,
            'inclusionai/ling-3.0-flash-fin:free',
            'minimax/minimax-m3:free',
        ]);

        foreach ($modelsToTry as $model) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->openrouterApiKey,
                    'HTTP-Referer' => 'https://epycus.es',
                    'X-Title' => 'Epycus App',
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(35)
                    ->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => 0.7,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $this->extractContent($data);
                    if (trim($content) !== '' && $content !== 'No recibí una respuesta adecuada del asistente.') {
                        return $content;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("OpenRouter fallo con modelo {$model}: " . $e->getMessage());
            }
        }

        throw new Exception('OpenRouter no pudo responder con ninguno de los modelos gratuitos disponibles.');
    }

    /**
     * Llamada a Groq Cloud (Llama 3.3 70B)
     */
    private function callGroq(array $messages): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->groqApiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->groqModel,
                'messages' => $messages,
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $this->extractContent($data);
        }

        throw new Exception('Error Groq API (' . $response->status() . '): ' . $response->body());
    }

    /**
     * Llamada a DeepSeek
     */
    private function callDeepSeek(array $messages): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->deepseekApiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->post(rtrim($this->deepseekBaseUrl, '/') . '/chat/completions', [
                'model' => $this->deepseekModel,
                'messages' => $messages,
                'stream' => false,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return $this->extractContent($data);
        }

        throw new Exception('Error DeepSeek API (' . $response->status() . '): ' . $response->body());
    }

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

        return 'No recibí una respuesta adecuada del asistente.';
    }
}
