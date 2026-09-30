<?php

namespace App\Domain\User;

final readonly class Email
{
    public string $value;
    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        if (strlen($value) > 150 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('El correo electrónico no es válido.');
        }
        $this->value = $value;
    }
}
