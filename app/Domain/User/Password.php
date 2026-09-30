<?php

namespace App\Domain\User;

final readonly class Password
{
    public function __construct(public string $hash)
    {
    }
    public static function fromPlainText(#[\SensitiveParameter] string $value): self
    {
        if (mb_strlen($value) < 6 || strlen($value) > 72) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres y como máximo 72 bytes.');
        }
        return new self(password_hash($value, PASSWORD_BCRYPT));
    }
    public function matches(#[\SensitiveParameter] string $value): bool
    {
        return password_verify($value, $this->hash);
    }
}
