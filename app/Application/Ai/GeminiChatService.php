<?php

namespace App\Application\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiChatService
{
    public function __construct(
        private readonly GeminiCatalogContextBuilder $contextBuilder
    ) {
    }

    public function getApiKey(): string
    {
        $saved = Cache::get('servimatica_whatsapp_bot_settings', []);
        return (string) ($saved['gemini_api_key'] ?? (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '')));
    }

    public function getModel(): string
    {
        $saved = Cache::get('servimatica_whatsapp_bot_settings', []);
        return (string) ($saved['gemini_model'] ?? (config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-1.5-flash')));
    }

    /**
     * Procesa una consulta conversacional con Gemini contra la base de datos real.
     *
     * @param string $message Mensaje del usuario
     * @param array $history Historial previo de mensajes [['role' => 'user'|'model', 'text' => '...']]
     * @return array ['reply' => string, 'tokens_used' => ?int, 'is_human_requested' => bool]
     */
    public function reply(string $message, array $history = []): array
    {
        $isHumanRequested = $this->detectHumanRequest($message);
        if ($isHumanRequested) {
            return [
                'reply' => "¡Comprendido! He notificado a uno de nuestros asesores de tienda en el Comercial Chiriguano para que te atienda personalmente por aquí en breve. 👨‍💻📦",
                'tokens_used' => null,
                'is_human_requested' => true,
            ];
        }

        $systemPrompt = $this->contextBuilder->buildSystemPrompt();
        $apiKey = $this->getApiKey();
        $model = $this->getModel();

        if (empty($apiKey)) {
            return [
                'reply' => $this->offlineFallbackReply($message),
                'tokens_used' => null,
                'is_human_requested' => false,
            ];
        }

        try {
            $contents = [];
            foreach ($history as $h) {
                $role = ($h['role'] === 'assistant' || $h['role'] === 'model') ? 'model' : 'user';
                $text = (string) ($h['text'] ?? $h['message'] ?? '');
                if (!empty(trim($text))) {
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $text]],
                    ];
                }
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]],
            ];

            $modelsToTry = array_unique([$model, 'gemini-3.5-flash', 'gemini-flash-latest']);

            foreach ($modelsToTry as $currentModel) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";

                $response = Http::timeout(25)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.4,
                            'topK' => 32,
                            'topP' => 0.9,
                            'maxOutputTokens' => 2048,
                            'thinkingConfig' => [
                                'thinkingBudget' => 0,
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawParts = $data['candidates'][0]['content']['parts'] ?? [];
                    $replyText = '';
                    foreach ($rawParts as $part) {
                        if (!empty($part['text'])) {
                            $replyText .= $part['text'];
                        }
                    }
                    $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? null;

                    if (!empty(trim($replyText))) {
                        return [
                            'reply' => trim($replyText),
                            'tokens_used' => $tokensUsed,
                            'is_human_requested' => false,
                        ];
                    }
                }

                Log::warning("Gemini API Non-200 or empty response for model {$currentModel}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return [
                'reply' => $this->offlineFallbackReply($message),
                'tokens_used' => null,
                'is_human_requested' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('GeminiChatService exception', ['error' => $e->getMessage()]);
            return [
                'reply' => $this->offlineFallbackReply($message),
                'tokens_used' => null,
                'is_human_requested' => false,
            ];
        }
    }

    /**
     * Detecta si el cliente solicita explícitamente ser atendido por un humano.
     */
    public function detectHumanRequest(string $message): bool
    {
        $clean = mb_strtolower(trim($message));
        $patterns = [
            'hablar con alguien',
            'hablar con un asesor',
            'hablar con una persona',
            'asesor humano',
            'persona real',
            'vendedor humano',
            'atencion humana',
            'con una persona',
            'un humano',
            'pasame con un asesor',
            'pásame con un asesor',
            'comunicarme con una persona',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($clean, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respuesta de contingencia cuando la API de Gemini no está configurada o está fuera de línea.
     */
    private function offlineFallbackReply(string $message): string
    {
        $baseUrl = $this->contextBuilder->getCatalogBaseUrl();
        return "¡Hola! Gracias por escribir a *Servimática PC* 💻✨. Puedes ver todo nuestro inventario en tiempo real con fotos 360° en nuestro catálogo: {$baseUrl} o visitarnos en el Comercial Chiriguano pasillo 2 local # 333 (Santa Cruz). ¿En qué equipo te gustaría que te asesoremos?";
    }
}
