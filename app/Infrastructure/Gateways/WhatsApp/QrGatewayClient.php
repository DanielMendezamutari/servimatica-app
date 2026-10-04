<?php

namespace App\Infrastructure\Gateways\WhatsApp;

use App\Domain\WhatsApp\WhatsAppGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QrGatewayClient implements WhatsAppGatewayInterface
{
    private string $gatewayUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->gatewayUrl = rtrim((string) env('WHATSAPP_GATEWAY_URL', 'http://127.0.0.1:8080'), '/');
        $this->apiKey = (string) env('WHATSAPP_GATEWAY_API_KEY', '');
    }

    public function sendMessage(string $toPhone, string $message): bool
    {
        $cleanPhone = preg_replace('/\D+/', '', $toPhone);
        if (strlen($cleanPhone) === 8) {
            $cleanPhone = '591' . $cleanPhone;
        }

        // Si no hay gateway configurado o estamos en local sin daemon de WhatsApp, logueamos el mensaje
        if (empty($this->gatewayUrl) || $this->gatewayUrl === 'http://127.0.0.1:8080') {
            Log::info("WhatsApp QrGateway [Simulated Outbound] to {$cleanPhone}: {$message}");
            return true;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'apikey' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->gatewayUrl}/message/sendText", [
                    'number' => $cleanPhone,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning("WhatsApp QrGateway failed to send message", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("WhatsApp QrGateway exception: " . $e->getMessage());
            return false;
        }
    }
}
