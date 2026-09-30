<?php

namespace App\Domain\User;

final readonly class PinCode
{
    public function __construct(public string $hash)
    {
    }
    public static function fromPlainText(#[\SensitiveParameter] string $value): self
    {
        if (!preg_match('/\A[0-9]{4}\z/', $value)) {
            throw new \InvalidArgumentException('El PIN debe tener exactamente 4 números.');
        }
        return new self(password_hash($value, PASSWORD_BCRYPT));
    }
    public function matches(#[\SensitiveParameter] string $value): bool
    {
        return password_verify($value, $this->hash);
    }
}
