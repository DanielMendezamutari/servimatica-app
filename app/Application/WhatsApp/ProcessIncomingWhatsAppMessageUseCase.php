<?php

namespace App\Application\WhatsApp;

use App\Application\Ai\GeminiChatService;
use App\Domain\WhatsApp\WhatsAppGatewayInterface;
use App\Infrastructure\Persistence\Eloquent\WhatsAppConversationModel;
use App\Infrastructure\Persistence\Eloquent\WhatsAppMessageModel;

class ProcessIncomingWhatsAppMessageUseCase
{
    public function __construct(
        private readonly GeminiChatService $geminiChatService,
        private readonly WhatsAppGatewayInterface $gateway
    ) {
    }

    /**
     * Procesa un mensaje entrante de WhatsApp, registra el hilo y responde automáticamente con Gemini.
     *
     * @param string $rawPhone Teléfono del remitente
     * @param string $messageText Mensaje recibido
     * @param string|null $customerName Nombre de perfil de WhatsApp si está disponible
     * @return array
     */
    public function execute(string $rawPhone, string $messageText, ?string $customerName = null): array
    {
        $cleanPhone = preg_replace('/\D+/', '', $rawPhone);
        if (strlen($cleanPhone) === 8) {
            $cleanPhone = '591' . $cleanPhone;
        }

        // 1. Obtener o crear conversación
        $conversation = WhatsAppConversationModel::firstOrCreate(
            ['phone_number' => $cleanPhone],
            [
                'status' => 'active',
                'customer_name' => $customerName,
                'last_message_at' => now(),
            ]
        );

        if ($customerName && empty($conversation->customer_name)) {
            $conversation->update(['customer_name' => $customerName]);
        }

        // 2. Registrar el mensaje entrante
        $inbound = $conversation->messages()->create([
            'direction' => 'inbound',
            'message' => $messageText,
        ]);

        $conversation->update(['last_message_at' => now()]);

        // 3. Si la conversación está pausada o en modo asesor humano activo, no responder con bot
        if ($conversation->isHumanHandoffActive() || $conversation->status === 'paused') {
            return [
                'conversation_id' => $conversation->id,
                'phone_number' => $cleanPhone,
                'replied' => false,
                'status' => $conversation->status,
                'reason' => $conversation->isHumanHandoffActive() ? 'human_agent_active' : 'conversation_paused',
            ];
        }

        // 4. Cargar contexto de mensajes recientes (últimos 6 previos a este)
        $previousMessages = $conversation->messages()
            ->where('id', '!=', $inbound->id)
            ->latest('id')
            ->take(6)
            ->get()
            ->reverse();

        $history = [];
        foreach ($previousMessages as $pm) {
            $history[] = [
                'role' => ($pm->direction === 'inbound') ? 'user' : 'model',
                'text' => $pm->message,
            ];
        }

        // 5. Consultar a Gemini con el catálogo de productos
        $aiResult = $this->geminiChatService->reply($messageText, $history);
        $replyText = $aiResult['reply'];
        $tokensUsed = $aiResult['tokens_used'];
        $isHumanRequested = $aiResult['is_human_requested'];

        // 6. Si solicitó derivación con humano, silenciar bot por 24 horas
        if ($isHumanRequested) {
            $conversation->update([
                'status' => 'human_agent',
                'human_handoff_until' => now()->addHours(24),
            ]);
        }

        // 7. Persistir mensaje de respuesta
        $conversation->messages()->create([
            'direction' => 'outbound',
            'message' => $replyText,
            'tokens_used' => $tokensUsed,
        ]);

        // 8. Enviar respuesta al cliente vía WhatsApp Gateway
        $sent = $this->gateway->sendMessage($cleanPhone, $replyText);

        return [
            'conversation_id' => $conversation->id,
            'phone_number' => $cleanPhone,
            'replied' => true,
            'sent_via_gateway' => $sent,
            'reply' => $replyText,
            'is_human_requested' => $isHumanRequested,
            'tokens_used' => $tokensUsed,
        ];
    }
}
