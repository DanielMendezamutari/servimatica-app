<?php

namespace App\Domain\WhatsApp;

interface WhatsAppGatewayInterface
{
    /**
     * Envía un mensaje de texto por WhatsApp al destinatario.
     *
     * @param string $toPhone Número de teléfono en formato internacional (ej. 59178901234)
     * @param string $message Mensaje formateado para WhatsApp
     * @return bool
     */
    public function sendMessage(string $toPhone, string $message): bool;
}
