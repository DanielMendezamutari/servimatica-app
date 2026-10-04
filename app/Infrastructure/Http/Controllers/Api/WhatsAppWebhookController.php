<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\WhatsApp\ProcessIncomingWhatsAppMessageUseCase;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController
{
    public function __construct(
        private readonly ProcessIncomingWhatsAppMessageUseCase $processIncomingUseCase
    ) {
    }

    /**
     * Handshake o verificación de webhook (GET).
     */
    public function verify(Request $request): Response|JsonResponse
    {
        if ($request->has('hub_challenge') || $request->has('hub.challenge')) {
            $challenge = $request->input('hub_challenge', $request->input('hub.challenge'));
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json([
            'status' => 'online',
            'service' => 'Servimática WhatsApp AI Bot Webhook',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Recepción de eventos y mensajes entrantes de WhatsApp (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // 1. Extraer datos del evento (compatible con Evolution API, Baileys, UltraMsg, Z-API o Payload directo)
        $data = $payload['data'] ?? $payload;

        // Descartar mensajes enviados por el propio bot/teléfono
        $key = $data['key'] ?? [];
        if (!empty($key['fromMe']) && $key['fromMe'] === true) {
            return response()->json(['status' => 'ignored_self_message']);
        }

        if (($payload['fromMe'] ?? false) === true) {
            return response()->json(['status' => 'ignored_self_message']);
        }

        // 2. Extraer teléfono del remitente
        $rawJid = $key['remoteJid']
            ?? $data['remoteJid']
            ?? $data['from']
            ?? $data['sender']
            ?? $payload['phone']
            ?? $payload['from']
            ?? null;

        if (empty($rawJid)) {
            // Intentar formato Meta Cloud API
            $metaMsg = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
            if ($metaMsg) {
                $rawJid = $metaMsg['from'] ?? null;
            }
        }

        if (empty($rawJid)) {
            Log::info('WhatsApp Webhook: Empty sender JID', ['payload' => $payload]);
            return response()->json(['status' => 'ignored_no_sender']);
        }

        // Si es un grupo de WhatsApp (@g.us), ignorar para no saturar grupos
        if (str_contains((string) $rawJid, '@g.us')) {
            return response()->json(['status' => 'ignored_group_message']);
        }

        $phone = preg_replace('/@.*$/', '', (string) $rawJid);
        $phone = preg_replace('/\D+/', '', $phone);

        // 3. Extraer contenido del mensaje de texto
        $text = $data['message']['conversation']
            ?? $data['message']['extendedTextMessage']['text']
            ?? $data['message']['imageMessage']['caption']
            ?? $data['body']
            ?? $data['text']
            ?? $payload['message']
            ?? $payload['text']
            ?? null;

        if (empty($text) && isset($metaMsg['text']['body'])) {
            $text = $metaMsg['text']['body'];
        }

        if (empty($text) || empty(trim((string) $text))) {
            return response()->json(['status' => 'ignored_non_text_message']);
        }

        // 4. Extraer nombre de perfil
        $pushName = $data['pushName']
            ?? $payload['pushName']
            ?? $payload['name']
            ?? ($payload['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? null);

        // 5. Procesar mediante el caso de uso
        try {
            $result = $this->processIncomingUseCase->execute($phone, trim((string) $text), $pushName);

            return response()->json([
                'status' => 'processed',
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error processing WhatsApp Webhook: ' . $e->getMessage(), [
                'phone' => $phone,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal error processing message',
            ], 500);
        }
    }
}
