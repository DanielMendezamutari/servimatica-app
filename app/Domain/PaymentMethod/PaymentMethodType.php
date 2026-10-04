<?php

namespace App\Domain\PaymentMethod;

enum PaymentMethodType: string
{
    case CASH = 'cash';
    case QR = 'qr';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Efectivo',
            self::QR => 'Pago QR',
            self::BANK_TRANSFER => 'Transferencia Bancaria',
            self::CARD => 'Tarjeta Débito/Crédito',
            self::OTHER => 'Otro Medio Digital',
        };
    }
}
