<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Ai\GeminiChatService;
use App\Infrastructure\Persistence\Eloquent\WhatsAppConversationModel;
use App\Infrastructure\Persistence\Eloquent\WhatsAppMessageModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WhatsAppBotController
{
    private const SETTINGS_CACHE_KEY = 'servimatica_whatsapp_bot_settings';

    public function __construct(
        private readonly GeminiChatService $geminiChatService
    ) {
    }

    /**
     * Obtiene la configuración actual del bot y estadísticas operativas.
     */
    public function getSettings(): JsonResponse
    {
        $saved = Cache::get(self::SETTINGS_CACHE_KEY, []);

        $envApiKey = (string) (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));
        $apiKey = $saved['gemini_api_key'] ?? $envApiKey;
        $maskedKey = !empty($apiKey) ? substr($apiKey, 0, 4) . '...' . substr($apiKey, -4) : '';

        $settings = [
            'is_active' => (bool) ($saved['is_active'] ?? true),
            'gemini_model' => (string) ($saved['gemini_model'] ?? env('GEMINI_MODEL', 'gemini-1.5-flash')),
            'has_gemini_key' => !empty($apiKey),
            'gemini_api_key_masked' => $maskedKey,
            'gateway_url' => (string) ($saved['gateway_url'] ?? env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:8080')),
            'human_handoff_hours' => (int) ($saved['human_handoff_hours'] ?? 24),
        ];

        $stats = [
            'total_conversations' => WhatsAppConversationModel::count(),
            'active_conversations' => WhatsAppConversationModel::where('status', 'active')->count(),
            'human_agent_conversations' => WhatsAppConversationModel::where('status', 'human_agent')
                ->where('human_handoff_until', '>', now())
                ->count(),
            'total_messages' => WhatsAppMessageModel::count(),
        ];

        return response()->json([
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    /**
     * Actualiza la configuración operativa del bot.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_active' => 'nullable|boolean',
            'gemini_model' => 'nullable|string|in:gemini-3.8-flash,gemini-3.7-flash,gemini-3.5-flash,gemini-1.5-flash,gemini-1.5-pro',
            'gemini_api_key' => 'nullable|string',
            'gateway_url' => 'nullable|url',
            'human_handoff_hours' => 'nullable|integer|min:1|max:72',
        ]);

        $current = Cache::get(self::SETTINGS_CACHE_KEY, []);

        if (array_key_exists('is_active', $validated)) {
            $current['is_active'] = (bool) $validated['is_active'];
        }

        if (!empty($validated['gemini_model'])) {
            $current['gemini_model'] = $validated['gemini_model'];
        }

        if (isset($validated['gemini_api_key']) && !empty(trim($validated['gemini_api_key']))) {
            $current['gemini_api_key'] = trim($validated['gemini_api_key']);
        }

        if (isset($validated['gateway_url'])) {
            $current['gateway_url'] = $validated['gateway_url'];
        }

        if (isset($validated['human_handoff_hours'])) {
            $current['human_handoff_hours'] = (int) $validated['human_handoff_hours'];
        }

        Cache::forever(self::SETTINGS_CACHE_KEY, $current);

        return response()->json([
            'message' => 'Configuración del Bot de WhatsApp guardada exitosamente.',
            'settings' => $current,
        ]);
    }

    /**
     * Simulador de chat en vivo contra el catálogo real de la base de datos sin necesidad de teléfono físico.
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant,model',
            'history.*.text' => 'required_with:history|string',
        ]);

        $message = (string) $request->input('message');
        $history = (array) $request->input('history', []);

        $result = $this->geminiChatService->reply($message, $history);

        return response()->json([
            'reply' => $result['reply'],
            'tokens_used' => $result['tokens_used'],
            'is_human_requested' => $result['is_human_requested'],
        ]);
    }

    /**
     * Listado de conversaciones registradas por WhatsApp.
     */
    public function conversations(Request $request): JsonResponse
    {
        $query = WhatsAppConversationModel::query()
            ->with(['messages' => fn($q) => $q->latest('id')->take(1)])
            ->orderByDesc('last_message_at');

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(15);

        // Anotar estado de handoff activo
        $items = collect($conversations->items())->map(function ($conv) {
            $data = $conv->toArray();
            $data['is_human_handoff_active'] = $conv->isHumanHandoffActive();
            return $data;
        });

        return response()->json([
            'data' => $items,
            'current_page' => $conversations->currentPage(),
            'last_page' => $conversations->lastPage(),
            'total' => $conversations->total(),
        ]);
    }

    /**
     * Reactivar el bot para una conversación que fue derivada a un asesor humano.
     */
    public function resetHandoff(int $id): JsonResponse
    {
        $conversation = WhatsAppConversationModel::findOrFail($id);

        $conversation->update([
            'status' => 'active',
            'human_handoff_until' => null,
        ]);

        return response()->json([
            'message' => 'El bot ha sido reactivado para este cliente.',
            'conversation' => $conversation,
        ]);
    }
}
