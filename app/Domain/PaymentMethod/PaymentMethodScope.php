<?php

namespace App\Domain\PaymentMethod;

enum PaymentMethodScope: string
{
    case SALES = 'sales';
    case PURCHASES = 'purchases';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::SALES => 'Solo Ventas (POS)',
            self::PURCHASES => 'Solo Compras a Proveedores',
            self::BOTH => 'Ventas y Compras (Global)',
        };
    }
}
