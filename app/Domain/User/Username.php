<?php

namespace App\Domain\User;

final readonly class Username
{
    public string $value;
    public function __construct(string $value)
    {
        $value = strtolower(trim($value));
        if (!preg_match('/\A[a-z0-9]{3,50}\z/', $value)) {
            throw new \InvalidArgumentException('El alias debe tener entre 3 y 50 letras o números sin espacios.');
        }
        $this->value = $value;
    }
}
